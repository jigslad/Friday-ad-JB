<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\PaymentBundle\Entity\PaymentPaypal;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PaymentPaypalRepository extends EntityRepository
{
    const ALIAS = 'pp';

    const SUCCESS_ACK = 'Success';

    const AP_COMPLETED = 'COMPLETED';

    /**
     * Add new paypal record.
     *
     * @param object $paymentObj Payment object.
     *
     * @return integer
     */
    public function addPaymentRecord($paymentObj)
    {
        $paymentValue        = unserialize($paymentObj->getValue());
        $paypalResponse      = (isset($paymentValue['paypal_do_response']) ? $paymentValue['paypal_do_response'] : (isset($paymentValue['paypal_adaptive_pay_response']) ? $paymentValue['paypal_adaptive_pay_response'] : null));
        $deliveryAddressInfo = isset($paymentValue['delivery_address_info']) ? $paymentValue['delivery_address_info'] : null;
        $paymentPaypal       = new PaymentPaypal();
        $paymentPaypal->setPayment($paymentObj);
        if (is_array($paypalResponse)) {
            if (isset($paypalResponse['TOKEN'])) {
                $paymentPaypal->setExpressToken($paypalResponse['TOKEN']);
            }
            if (isset($paypalResponse['PAYERID'])) {
                $paymentPaypal->setPayerId($paypalResponse['PAYERID']);
            }
            if (isset($paypalResponse['payKey'])) {
                $paymentPaypal->setExpressToken($paypalResponse['payKey']);
            }
            $paymentPaypal->setValue(serialize($paypalResponse));
        }
        if (!empty($paypalResponse) && isset($paypalResponse['ipAddress'])) {
            $paymentPaypal->setIp($paypalResponse['ipAddress']);
        }

        $this->_em->persist($paymentPaypal);
        $this->_em->flush();

        //update user address book.
        if (is_array($deliveryAddressInfo) && !empty($deliveryAddressInfo)) {
            $this->_em->getRepository('FaUserBundle:UserAddressBook')->addUserAddress($paymentObj->getUser(), $deliveryAddressInfo);
        }

        return $paymentPaypal->getId();
    }
}
