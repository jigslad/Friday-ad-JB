<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\LexikTranslationBundle\Util\DataGrid;

use Symfony\Component\HttpFoundation\JsonResponse;

use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridFormatter as BaseDataGridFormatter;

/**
 * Data gid formatter.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DataGridFormatter extends BaseDataGridFormatter
{
    /**
     * Format a single TransUnit.
     *
     * @param array $transUnit
     *
     * @return array
     */
    protected function formatOne($transUnit)
    {
        if (is_object($transUnit)) {
            $transUnit = $this->toArray($transUnit);
        }

        $formatted = array(
            '_id'     => ('mongodb' == $this->storage) ? $transUnit['_id']->{'$id'} : $transUnit['id'],
            'domain' => $transUnit['domain'],
            'key'    => $transUnit['key'],
        );

        // add locales in the same order as in managed_locales param
        foreach ($this->localeManager->getLocales() as $locale) {
            $formatted[$locale] = '';
        }

        // then fill locales value
        foreach ($transUnit['translations'] as $translation) {
            if (in_array($translation['locale'], $this->localeManager->getLocales())) {
                $formatted[$translation['locale']] = $translation['content'];
            }
        }

        return $formatted;
    }
}
