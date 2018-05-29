<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\LexikTranslationBundle;

use Lexik\Bundle\TranslationBundle\LexikTranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Fa lexik translation bundle.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FaLexikTranslationBundle extends Bundle
{
    /**
     * Returns the bundle parent name.
     *
     * @return string The Bundle parent name it overrides or null if no parent
     *
     * @api
     */
    public function getParent()
    {
        return 'LexikTranslationBundle';
    }
}
