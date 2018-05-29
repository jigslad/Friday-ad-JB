<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdminBundle\View;

use Pagerfanta\View\TwitterBootstrap3View;
use Fa\Bundle\AdminBundle\View\Template\FoundationPagerTemplate;

/**
 * Foundation pager view.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FoundationPagerView extends TwitterBootstrap3View
{

    /**
     * Create default template.
     *
     * @return object
     */
    protected function createDefaultTemplate()
    {
        return new FoundationPagerTemplate();
    }

    /**
     * Get name.
     *
     * @return object
     */
    public function getName()
    {
        return 'foundation_pager_admin';
    }
}
