<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\FrontendBundle\View;

use Pagerfanta\PagerfantaInterface;
use Pagerfanta\View\Template\TemplateInterface;
use Fa\Bundle\FrontendBundle\View\Template\FoundationGooglePagerTemplate;
use Pagerfanta\View\ViewInterface;

/**
 * This is used for pagination.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class FoundationGooglePagerView implements ViewInterface
{
    private $template;

    private $pagerfanta;
    private $proximity;

    private $currentPage;
    private $nbPages;

    private $startPage;
    private $endPage;

    private $addToEndPage;

    private $seoPager = true;

    /**
     * Create default template.
     *
     * @return \Fa\Bundle\FrontendBundle\View\Template\FoundationGooglePagerTemplate
     */
    protected function createDefaultTemplate()
    {
        return new FoundationGooglePagerTemplate();
    }

    /**
     * Get name.
     *
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'foundation_google_pager';
    }

    /**
     * Constructor.
     *
     * @param TemplateInterface $template Template interface.
     */
    public function __construct(TemplateInterface $template = null)
    {
        $this->template = $template ?: $this->createDefaultTemplate();
    }

    /**
     * Render.
     *
     * @param string $pagerfanta
     * @param string $routeGenerator
     * @param string $options
     */
    public function render(PagerfantaInterface $pagerfanta, $routeGenerator, array $options = array())
    {
        $this->initializePagerfanta($pagerfanta);
        $this->initializeOptions($options);

        $this->configureTemplate($routeGenerator, $options);

        return $this->generate();
    }

    /**
     * Initialize pager fanta.
     *
     * @param PagerfantaInterface $pagerfanta Pager fanta interface.
     */
    private function initializePagerfanta(PagerfantaInterface $pagerfanta)
    {
        $this->pagerfanta = $pagerfanta;

        $this->currentPage = $pagerfanta->getCurrentPage();
        $this->nbPages = $pagerfanta->getNbPages();
    }

    /**
     * Initialize options.
     *
     * @param array $options Options array.
     */
    private function initializeOptions($options)
    {
        $this->proximity = isset($options['proximity']) ?
        (int) $options['proximity'] :
        $this->getDefaultProximity();

        $this->addToEndPage = isset($options['addToEndPage']) ? (int) $options['addToEndPage'] : 0;
        $this->seoPager     = isset($options['seoPager']) ? $options['seoPager'] : true;
    }

    /**
     * Get default proximity.
     *
     * @return number
     */
    protected function getDefaultProximity()
    {
        return 4;
    }

    /**
     * Configure template.
     *
     * @param object $routeGenerator Router generator.
     * @param array  $options        Options array.
     */
    private function configureTemplate($routeGenerator, $options)
    {
        $this->template->setRouteGenerator($routeGenerator);
        $this->template->setOptions($options);
    }

    /**
     * Generate pagination.
     */
    private function generate()
    {
        $pages = $this->generatePages();

        return $this->generateContainer($pages);
    }

    /**
     * Generate container.
     *
     * @param string $pages Pges string.
     */
    private function generateContainer($pages)
    {
        return str_replace('%pages%', $pages, $this->template->container());
    }

    /**
     * Generate pages.
     *
     * @return string
     */
    private function generatePages()
    {
        $this->calculateStartAndEndPage();

        return $this->previous().
        $this->pages().
        $this->next();
    }

    /**
     * Calculate start and and page.
     */
    private function calculateStartAndEndPage()
    {
        $startPage = $this->currentPage - $this->proximity;
        $endPage = $this->currentPage + $this->proximity+$this->addToEndPage;

        if ($this->startPageUnderflow($startPage)) {
            $endPage = $this->calculateEndPageForStartPageUnderflow($startPage, $endPage);
            $startPage = 1;
        }
        if ($this->endPageOverflow($endPage)) {
            $startPage = $this->calculateStartPageForEndPageOverflow($startPage, $endPage);
            $endPage = $this->nbPages;
        }

        $this->startPage = $startPage;
        $this->endPage = $endPage;
    }

    /**
     * Get start page under flow.
     *
     * @param integer $startPage
     *
     * @return boolean
     */
    private function startPageUnderflow($startPage)
    {
        return $startPage < 1;
    }

    /**
     * Get end page overflow.
     *
     * @param integer $endPage End page.
     *
     * @return boolean
     */
    private function endPageOverflow($endPage)
    {
        return $endPage > $this->nbPages;
    }

    /**
     * Calculate end page.
     *
     * @param integer $startPage Start page.
     * @param integer $endPage   End page.
     *
     * @return integer
     */
    private function calculateEndPageForStartPageUnderflow($startPage, $endPage)
    {
        return min($endPage + (1 - $startPage), $this->nbPages);
    }

    /**
     * Calculate start page.
     *
     * @param integer $startPage Start page.
     * @param integer $endPage   End page.
     *
     * @return integer
     */
    private function calculateStartPageForEndPageOverflow($startPage, $endPage)
    {
        return max($startPage - ($endPage - $this->nbPages), 1);
    }

    /**
     * Previous.
     */
    private function previous()
    {
        if ($this->pagerfanta->hasPreviousPage()) {
            return $this->template->previousEnabled($this->pagerfanta->getPreviousPage());
        }

        return $this->template->previousDisabled();
    }

    /**
     * Get pages.
     *
     * @return string
     */
    private function pages()
    {
        $pages = '';

        foreach (range($this->startPage, $this->endPage) as $page) {
            $pages .= $this->page($page);
        }

        return $pages;
    }

    /**
     * Get page.
     *
     * @param integer $page
     */
    private function page($page)
    {
        if ($page == $this->currentPage) {
            return $this->template->current($page);
        }

        return $this->template->page($page);
    }

    /**
     * Go to last link.
     *
     * @param number $n
     *
     * @return number
     */
    private function toLast($n)
    {
        return $this->pagerfanta->getNbPages() - ($n - 1);
    }

    /**
     * Get next link.
     */
    private function next()
    {
        if ($this->pagerfanta->hasNextPage()) {
            return $this->template->nextEnabled($this->pagerfanta->getNextPage());
        }

        return $this->template->nextDisabled();
    }
}
