<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\FrontendBundle\View\Template;

use Pagerfanta\View\Template\Template;

/**
 * This is used for paging template.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class FoundationGooglePagerTemplate extends Template
{
    /**
     * Default options.
     */
    protected static $defaultOptions = array(
        'prev_message'        => '&#171;',
        'next_message'        => '&#187;',
        'dots_message'        => '&hellip;',
        'active_suffix'       => '',
        'css_container_class' => 'pagination',
        'css_prev_class'      => 'arrow pager-prev',
        'css_next_class'      => 'arrow pager-next',
        'css_disabled_class'  => 'unavailable',
        'css_dots_class'      => 'unavailable',
        'css_active_class'    => 'current'
    );

    /**
     * Constructor.
     *
     * @param $seoPager
     */
    public function __construct($seoPager = false)
    {
        parent::__construct();
        $this->seoPager = $seoPager;
    }

    /**
     * Container.
     *
     * (non-PHPdoc)
     * @see \Pagerfanta\View\Template\TemplateInterface::container()
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
     * @param string $page
     */
    public function page($page)
    {
        $text = $page;

        return $this->pageWithText($page, $text);
    }

    /**
     * Page with text.
     *
     * @param integer $page Page.
     * @param string  $text Text.
     */
    public function pageWithText($page, $text)
    {
        $class = null;

        return $this->pageWithTextAndClass($page, $text, $class);
    }

    /**
     * Get page with text and class.
     *
     * @param integer $page  Page.
     * @param string  $text  Text.
     * @param string  $class Class.
     * @param string  $rel   Rel.
     */
    private function pageWithTextAndClass($page, $text, $class, $rel = null)
    {
        $href    = $this->generateRoute($page);

        if ($this->option('seoPager')) {
            $matches  = array();
            $url_info = parse_url($href);
            $path = null;
            $query = null;
            $pageno = null;

            if (isset($url_info['path'])) {
                preg_match('/page-\d+/', $url_info['path'], $matches);

                if (isset($matches[0])) {
                    $path = str_replace($matches[0], '', $url_info['path']);
                    $path = preg_replace('/\/{2,}/', '/', $path);
                    $pageno = str_replace('page-', '', $matches[0]);
                    if (isset($url_info['query'])) {
                        $query = str_replace($matches[0], '', $url_info['query']);
                    }
                }

                if (isset($url_info['query'])) {
                    preg_match('/page=\d+/', $url_info['query'], $matches);

                    if (isset($matches[0])) {
                        $query = str_replace($matches[0], '', $url_info['query']);
                        $pageno = str_replace('page=', '', $matches[0]);
                    }
                } else {
                    if (isset($matches[0])) {
                        $pageno = str_replace('page-', '', $matches[0]);
                    }
                }

                if ($query != '') {
                    $href = $path.'page-'.$pageno.'/'.'?'.$query;
                } else {
                    $href = $path.'page-'.$pageno.'/';
                }
                if ($pageno == 1) {
                    $href =   str_replace('/page-1/', '/', $href);
                }

                $href = rtrim($href, '&');
            }
        }

        return $this->linkLi($class, $href, $text, $rel);
    }

    /**
     * Previous disabled.
     */
    public function previousDisabled()
    {
        $class = $this->previousDisabledClass();
        $text = $this->option('prev_message');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Give previous disabled class.
     *
     * @return string
     */
    private function previousDisabledClass()
    {
        return $this->option('css_prev_class').' '.$this->option('css_disabled_class');
    }

    /**
     * Previous enabled.
     *
     * @param integer $page Page.
     */
    public function previousEnabled($page)
    {
        $text = $this->option('prev_message');
        $class = $this->option('css_prev_class');

        return $this->pageWithTextAndClass($page, $text, $class, 'prev');
    }

    /**
     * Next disabled.
     */
    public function nextDisabled()
    {
        $class = $this->nextDisabledClass();
        $text = $this->option('next_message');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Get next disabled class.
     *
     * @return string
     */
    private function nextDisabledClass()
    {
        return $this->option('css_next_class').' '.$this->option('css_disabled_class');
    }

    /**
     * Next enabled.
     *
     * @param integer $page Page.
     */
    public function nextEnabled($page)
    {
        $text = $this->option('next_message');
        $class = $this->option('css_next_class');

        return $this->pageWithTextAndClass($page, $text, $class, 'next');
    }

    /**
     * First.
     */
    public function first()
    {
        return $this->page(1);
    }

    /**
     * Last.
     *
     * @param integer $page Page.
     */
    public function last($page)
    {
        return $this->page($page);
    }

    /**
     * Current
     *
     * @param integer $page Page.
     */
    public function current($page)
    {
        $text = trim($page . ' ' . $this->option('active_suffix'));
        $class = $this->option('css_active_class');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Separator.
     */
    public function separator()
    {
        $class = $this->option('css_dots_class');
        $text = $this->option('dots_message');

        return $this->linkLi($class, '', $text);
    }

    /**
     * Gives li with anchor.
     *
     * @param string $class Class.
     * @param string $href  Anchor tag url.
     * @param string $text  Text of link.
     * @param string $rel   Rel value.
     *
     * @return string
     */
    private function linkLi($class, $href, $text, $rel = null)
    {
        $liClass = $class ? sprintf(' class="%s"', $class) : '';
        $relVal = $rel ? sprintf(' rel="%s"', $rel) : '';

        return sprintf('<li%s><a href="%s" %s>%s</a></li>', $liClass, $href, $relVal, $text);
    }

    /**
     * Gives li with span.
     *
     * @param string $class Class.
     * @param string $text  Text of link.
     *
     * @return string
     */
    private function spanLi($class, $text)
    {
        $liClass = $class ? sprintf(' class="%s"', $class) : '';

        return sprintf('<li%s><span>%s</span></li>', $liClass, $text);
    }
}
