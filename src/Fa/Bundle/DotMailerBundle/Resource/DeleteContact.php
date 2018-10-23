<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Resource;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;
use Fa\Bundle\DotMailerBundle\Resource\DotMailerRequestBuild;
use Fa\Bundle\DotMailerBundle\Resource\ResourceInterface;

/**
 * This controller is used to create contact on dotmailer.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DeleteContact extends DotMailerRequestBuild implements ResourceInterface
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get dot mailer resource.
     */
    public function getDotMailerResource()
    {
        return ResourceInterface::CONTACTS;
    }

    /**
     * Get http method.
     */
    public function getHttpMethod()
    {
        return 'GET';
    }

    /**
     * Get customer request method.
     */
    public function getCustomRequest() {
        return 'DELETE';
    }

    /**
     * Data array.
     *
     * @param string $data
     */
    public function setDataToSubmit($data)
    {
        foreach ($data as $key => $value) {
            $this->dataToSubmit .= $value.'/';
        }

        $this->dataToSubmit = rtrim($this->dataToSubmit, '/');
    }

    /**
     * Get container.
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function deleteContact()
    {
        try {
            // Send request to dot mailer.
            $this->sendRequest();

            // Check for httpCode.
            if ($this->getHttpcode() == '204') {
                return true;
            }
        } catch (\Exception $e) {
            // Send failure email
        }

        return false;
    }
}
