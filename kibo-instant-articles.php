<?php
/*
Plugin Name: Instant Articles Fixes
Description: Adds MyElementGetter with extra options for Instant Articles. Disables responsive images for Instant Articles.
Version: 1.2.0
Author: <a href="https://peschar.net/">Albert Peschar</a>
*/

// Smarter ad placement.
require_once __DIR__ . '/ad_placement.php';

// Add a MyElementGetter that allows HTML code to be prepended.
add_action('plugins_loaded', 'kiia_load');

function kiia_load() {
    if (class_exists('\\Facebook\\InstantArticles\\Transformer\\Getters\\AbstractGetter')) {
        require_once __DIR__ . '/MyElementGetter.php';
    }
}

// Undo WP responsive images in Instant Articles.
add_action('instant_articles_parsed_document', 'kiia_fix_responsive_img');

function kiia_fix_responsive_img($doc) {
    if (!($doc instanceof DOMDocument)) {
        return $doc;
    }
    foreach ($doc->getElementsByTagName('img') as $img) {
        kiia_process_img($img);
    }
    return $doc;
}

function kiia_process_img($img) {
    if (!($srcset = $img->getAttribute('srcset'))) {
        return;
    }
    $src = null;
    $size = null;
    foreach(explode(', ', $srcset) as $def) {
        $def = explode(' ', $def, 2);
        if (sizeof($def) != 2 || !$def[0] || !$def[1]) {
            continue;
        }
        if ($src === null || (float) $def[1] > (float) $size) {
            list($src, $size) = $def;
        }
    }
    if ($src) {
        $img->setAttribute('src', $src);
    }
}

// Add a meta box to set published status.
add_action('add_meta_boxes', 'kiia_add_meta_box');
add_action('save_post', 'kiia_save_meta_box', 0, 2);
add_action('save_post', 'kiia_save_post', 0, 2);
add_action('option_instant-articles-option-publishing', 'kiia_option_ia_publishing', 10, 2);

function kiia_add_meta_box() {
    add_meta_box('kiia', 'Facebook Instant Articles', 'kiia_meta_box', 'post', 'side', 'high');
}

function kiia_meta_box($post) {
    wp_nonce_field(basename(__FILE__), 'kiia_nonce');

    $published = !!get_post_meta($post->ID, 'kiia_published', true);

    ?>
        <p>
            <label>
                <input type=checkbox name=kiia_published value=1 <?php if($published) echo 'checked'; ?>>
                Publiceren als Instant Article
            </label>
        </p>
    <?php
}

function kiia_save_meta_box($post_id, $post) {
    $is_autosave = wp_is_post_autosave($post_id);
    $is_revision = wp_is_post_revision($post_id);
    $is_valid_nonce = (isset($_POST['kiia_nonce']) && wp_verify_nonce($_POST['kiia_nonce'], basename(__FILE__)));
    $is_post = $post->post_type == 'post';

    if ($is_autosave || $is_revision || !$is_valid_nonce || !$is_post) {
        return;
    }

    update_post_meta($post_id, 'kiia_published', !empty($_POST['kiia_published']));
}

function kiia_save_post($post_id, $post) {
    if (get_post_meta($post_id, 'kiia_published', true)) {
        return;
    }

    $GLOBALS['kiia_prevent_publish'] = true;
}

function kiia_option_ia_publishing($value, $option) {
    if (empty($GLOBALS['kiia_prevent_publish'])) {
        return $value;
    }

    $data = @json_decode($value, true);

    if (!is_array($data)) {
        $data = array();
    }

    $data['dev_mode'] = '1';

    return json_encode($data);
}
