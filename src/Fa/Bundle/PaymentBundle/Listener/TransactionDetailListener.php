<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\PaymentBundle\Entity\TransactionDetail;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class TransactionDetailListener
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
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Pre remove.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity TransactionDetail object.
     */
    public function preRemove(TransactionDetail $entity, LifecycleEventArgs $event)
    {
        $this->updateGrandTotal($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity TransactionDetail object.
     */
    public function postPersist(TransactionDetail $entity, LifecycleEventArgs $event)
    {
        $this->updateGrandTotal($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity TransactionDetail object.
     */
    public function postUpdate(TransactionDetail $entity, LifecycleEventArgs $event)
    {
        $this->updateGrandTotal($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaPaymentBundle:TransactionDetail')->getTableName();
    }

    /**
     * Update transaction total.
     *
     * @param object $entity Entity object.
     * @return string
     */
    private function updateGrandTotal($entity)
    {
        $updateVatValue = true;
        if ($entity->getTransaction()) {
            if ($entity->getTransaction()->getCart() && $entity->getTransaction()->getCart()->getIsBuyNow()) {
                $updateVatValue = false;
            }
            $transactionId = $entity->getTransaction()->getId();
            $total = null;
            $discountAmount = 0;
            $value = null;
            $transactionDetailRes = $this->container->get('doctrine')->getManager()->getRepository('FaPaymentBundle:TransactionDetail')->getTotalByTransactionId($transactionId);
            if (isset($transactionDetailRes['total'])) {
                $total = $transactionDetailRes['total'];
            }
            if (isset($transactionDetailRes['discount_amount'])) {
                $discountAmount = $transactionDetailRes['discount_amount'];
            }
            if (isset($transactionDetailRes['value'])) {
                $value = $transactionDetailRes['value'];
            }
            $this->container->get('doctrine')->getManager()->getRepository('FaPaymentBundle:Transaction')->updateTotalByTransactionId($entity->getTransaction(), $total, $discountAmount, $value, $updateVatValue);
            $this->container->get('doctrine')->getManager()->refresh($entity->getTransaction());
            if ($entity->getTransaction()->getCart()) {
                $total = null;
                $discountAmount = 0;
                $value = null;
                $cartId = $entity->getTransaction()->getCart()->getId();
                $transactionRes = $this->container->get('doctrine')->getManager()->getRepository('FaPaymentBundle:Transaction')->getTotalByCartId($cartId);
                if (isset($transactionRes['total'])) {
                    $total = $transactionRes['total'];
                }
                if (isset($transactionRes['discount_amount'])) {
                    $discountAmount = $transactionRes['discount_amount'];
                }
                if (isset($transactionRes['value'])) {
                    $value = $transactionRes['value'];
                }
                $this->container->get('doctrine')->getManager()->getRepository('FaPaymentBundle:Cart')->updateTotalByCartId($entity->getTransaction()->getCart(), $total, $discountAmount, $value);
            }
        }
    }
}
