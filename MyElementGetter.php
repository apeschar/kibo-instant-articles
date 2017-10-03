<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Transformer\Getters;

use Facebook\InstantArticles\Validators\Type;
use Symfony\Component\CssSelector\CssSelectorConverter;

class MyElementGetter extends AbstractGetter
{
    /**
     * @var string
     */
    protected $selector;

    /**
     * @var string
     */
    protected $prepend;

    /**
     * @var string
     */
    protected $append;

    public function createFrom($properties)
    {
        $this->withSelector($properties['selector']);

        if (!empty($properties['prepend'])) {
            $this->withPrepend($properties['prepend']);
        }

        if (!empty($properties['append'])) {
            $this->withAppend($properties['append']);
        }

        return $this;
    }

    /**
     * @param \DOMNode $node
     *
     * @return \DOMNodeList
     */
    public function findAll($node)
    {
        $domXPath = new \DOMXPath($node->ownerDocument);
        $converter = new CssSelectorConverter();
        $xpath = $converter->toXPath($this->selector);
        return $domXPath->query($xpath, $node);
    }

    /**
     * @param string $selector
     *
     * @return $this
     */
    public function withSelector($selector)
    {
        Type::enforce($selector, Type::STRING);
        $this->selector = $selector;

        return $this;
    }

    /**
     * @param string $prepend
     *
     * @return $this
     */
    public function withPrepend($prepend)
    {
        Type::enforce($prepend, Type::STRING);
        $this->prepend = $prepend;

        return $this;
    }

    /**
     * @param string $append
     *
     * @return $this
     */
    public function withAppend($append)
    {
        Type::enforce($append, Type::STRING);
        $this->append = $append;

        return $this;
    }

    public function get($node)
    {
        $elements = self::findAll($node, $this->selector);
        if (empty($elements)) {
            return null;
        }
        $element = $elements->item(0);
        $html = $element->ownerDocument->saveHTML($element);
        if ($this->prepend) {
            $html = $this->prepend . $html;
        }
        if ($this->append) {
            $html .= $this->append;
        }
        return $html;
    }
}
