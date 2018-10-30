<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdminBundle\View\Template;

use Pagerfanta\View\Template\Template;

/**
 * Foundation pager template.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FoundationPagerTemplate extends Template
{
    /**
     * Default options.
     *
     * @var array
     */
    protected static $defaultOptions = array(
        'prev_message'        => '&#171;',
        'next_message'        => '&#187;',
        'dots_message'        => '&hellip;',
        'active_suffix'       => '',
        'css_container_class' => 'pagination',
        'css_prev_class'      => 'arrow',
        'css_next_class'      => 'arrow',
        'css_disabled_class'  => 'unavailable',
        'css_dots_class'      => 'unavailable',
        'css_active_class'    => 'current'
    );

    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Container.
     *
     * @return string
     */
    public function container()
    {
        return sprintf(
            '<ul class="%s">%%pages%%</ul>',
            $this->option('css_container_class')
        );
    }

    /**
     * Page.
     *
     * @param integer $page
     *
     * @return string
     */
    public function page($page)
    {
        $text = $page;

        return $this->pageWithText($page, $text);
    }

    /**
     * Renders a given page with a specified text.
     *
     * @param integer $page
     * @param string  $text
     *
     * @return string
     */
    public function pageWithText($page, $text)
    {
        $class = null;

        return $this->pageWithTextAndClass($page, $text, $class);
    }

    /**
     * Page with text and class.
     *
     * @param integer $page
     * @param string  $text
     * @param string  $class
     *
     * @return string
     */
    private function pageWithTextAndClass($page, $text, $class)
    {
        $href = $this->generateRoute($page);

        return $this->linkLi($class, $href, $text);
    }

    /**
     * Renders the disabled state of the previous page.
     *
     * @return string
     */
    public function previousDisabled()
    {
        $class = $this->previousDisabledClass();
        $text = $this->option('prev_message');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Previous disabled class.
     *
     * @return string
     */
    private function previousDisabledClass()
    {
        return $this->option('css_prev_class').' '.$this->option('css_disabled_class');
    }

    /**
     * Renders the enabled state of the previous page.
     *
     * @param int $page
     *
     * @return string
     */
    public function previousEnabled($page)
    {
        $text = $this->option('prev_message');
        $class = $this->option('css_prev_class');

        return $this->pageWithTextAndClass($page, $text, $class);
    }

    /**
     * Renders the disabled state of the next page.
     *
     * @return string
     */
    public function nextDisabled()
    {
        $class = $this->nextDisabledClass();
        $text = $this->option('next_message');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Next disabled class.
     *
     * @return string
     */
    private function nextDisabledClass()
    {
        return $this->option('css_next_class').' '.$this->option('css_disabled_class');
    }

    /**
     * Renders the enabled state of the next page.
     *
     * @param int $page
     *
     * @return string
     */
    public function nextEnabled($page)
    {
        $text = $this->option('next_message');
        $class = $this->option('css_next_class');

        return $this->pageWithTextAndClass($page, $text, $class);
    }

    /**
     * Renders the first page.
     *
     * @return string
     */
    public function first()
    {
        return $this->page(1);
    }

    /**
     * Renders the last page.
     *
     * @param int $page
     *
     * @return string
     */
    public function last($page)
    {
        return $this->page($page);
    }

    /**
     * Renders the current page.
     *
     * @param int $page
     *
     * @return string
     */
    public function current($page)
    {
        $text = trim($page . ' ' . $this->option('active_suffix'));
        $class = $this->option('css_active_class');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Renders the separator between pages.
     *
     * @return string
     */
    public function separator()
    {
        $class = $this->option('css_dots_class');
        $text = $this->option('dots_message');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Link li.
     *
     * @param string $class
     * @param string $href
     * @param string $text
     *
     * @return string
     */
    private function linkLi($class, $href, $text)
    {
        $liClass = $class ? sprintf(' class="%s"', $class) : '';

        return sprintf('<li%s><a href="%s">%s</a></li>', $liClass, $href, $text);
    }

    /**
     * Span li.
     *
     * @param string $class
     * @param string $text
     *
     * @return string
     */
    private function spanLi($class, $text)
    {
        $liClass = $class ? sprintf(' class="%s"', $class) : '';

        return sprintf('<li%s><span>%s</span></li>', $liClass, $text);
    }
}
