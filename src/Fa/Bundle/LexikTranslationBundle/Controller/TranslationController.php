<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\LexikTranslationBundle\Controller;

use Lexik\Bundle\TranslationBundle\Controller\TranslationController as BaseTranslationController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;

/**
 * Translation controller.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class TranslationController extends BaseTranslationController implements ResourceAuthorizationController
{
    /**
     * Display the translation grid.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gridAction()
    {
        return $this->render(
            'LexikTranslationBundle:Translation:grid.html.twig',
            array(
                'layout'      => $this->container->getParameter('lexik_translation.base_layout'),
                'inputType'   => $this->container->getParameter('lexik_translation.grid_input_type'),
                'locales'     => $this->getManagedLocales(),
                'localeNames' => $this->getManagedLocaleNames(),
                'heading'     => $this->get('translator')->trans('Translations'),
            )
        );
    }

    /**
     * Returns managed locale names.
     *
     * @return array
     */
    protected function getManagedLocaleNames()
    {
        $localeNames = array();

        foreach ($this->getManagedLocales() as $locale) {
            $localeNames[] = \Locale::getDisplayName($locale);
        }

        return $localeNames;
    }
}
