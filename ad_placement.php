<?php

use \Facebook\InstantArticles\Elements\Image;
use \Facebook\InstantArticles\Elements\Interactive;
use \Facebook\InstantArticles\Elements\TextContainer;

class Kiia_Ad {
    private $wordsPerImage = 70;
    private $wordsPerAd = 350;

    public function __construct() {
        add_action('instant_articles_transformed_element',
                   [$this, 'processInstantArticle']);
    }

    public function processInstantArticle($article) {
        $words = 0;

        foreach ($article->getChildren() as $node) {
            $words += $this->countNodeWords($node);
            if ($words >= $this->wordsPerAd
                && $this->maybeInsertAd($article, $node)
            ) {
                $words = 0;
            }
        }

        return $article;
    }

    private function maybeInsertAd($article, $node) {
        if ($node instanceof TextContainer
            && $node != $this->getLastChild($article)
        ) {
            return false;
        }

        $ad = $this->getAd($article);
        if (!$ad) {
            return false;
        }

        $ad = clone $ad;
        $ad->disableDefaultForReuse();

        $this->insertChildAfter($ad, $article, $node);

        return true;
    }

    private function getAd($article) {
        $header = $article->getHeader();

        foreach ($header->getAds() as $ad) {
            return $ad;
        }
    }

    private function getNodeText($node) {
        $text = '';
        if ($node instanceof TextContainer) {
            foreach ($node->getTextChildren() as $child) {
                if (is_object($child)) {
                    $text .= $this->getNodeText($child);
                } else {
                    $text .= $child;
                }
            }
            return $text;
        } else {
            return null;
        }
    }

    private function getWords($text) {
        $words = preg_split('/[\pZ\pC]+/', $text);
        $words = preg_grep('/[\pL\pN]/', $words);
        return $words;
    }

    private function countWords($text) {
        return sizeof($this->getWords($text));
    }

    private function countNodeWords($node) {
        if ($node instanceof Image
            || $node instanceof Interactive
        ) {
            return $this->wordsPerImage;
        }

        if (($text = $this->getNodeText($node)) !== null) {
            return $this->countWords($text);
        }

        return 0;
    }

    private function getLastChild($element) {
        $children = $element->getChildren();
        return $children ? $children[sizeof($children) - 1] : null;
    }

    private function insertChildAfter($child, $article, $after) {
        $edit = function($child, $article, $after) {
            $i = -1;
            foreach ($article->children as $node) {
                $i++;
                if ($node !== $after) {
                    continue;
                }
                array_splice($article->children, $i+1, 0, [$child]);
                break;
            }
        };
        $edit = Closure::bind($edit, null, $article);
        $edit($child, $article, $after);
    }
}

new Kiia_Ad;
