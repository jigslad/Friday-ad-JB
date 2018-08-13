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
use Fa\Bundle\PaymentBundle\Entity\PaymentCyberSource;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This repository is used for payment cyber source.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PaymentCyberSourceRepository extends EntityRepository
{
    const ALIAS = 'pcs';
    const SUCCESS_REASON_CODE = '100';
    const SUCCESS_REASON_TEXT = 'ACCEPT';
    const SUCCESS_3D_REASON_CODE = '475';
    const SUCCESS_3D_REASON_TEXT = 'REJECT';


    const VISA           = 'Visa';
    const VISA_ELECTRON  = 'Visa Electron';
    const MAESTRO        = 'Maestro';
    const MASTERCARD     = 'MasterCard';

    /**
     * Add new cyber source record.
     *
     * @param object $paymentObj Payment object.
     *
     * @return integer
     */
    public function addPaymentRecord($paymentObj)
    {
        $paymentValue        = unserialize($paymentObj->getValue());
        $cyberSourceResponse = isset($paymentValue['cyber_source_response']) ? $paymentValue['cyber_source_response'] : null;
        $billingInfo         = isset($paymentValue['billing_info']) ? $paymentValue['billing_info'] : null;
        $userAddressInfo     = isset($paymentValue['user_address_info']) ? $paymentValue['user_address_info'] : null;
        $paymentCyberSource  = new PaymentCyberSource();
        $paymentCyberSource->setPayment($paymentObj);
        if (is_object($cyberSourceResponse)) {
            $paymentCyberSource->setRequestId($cyberSourceResponse->requestID);
            $paymentCyberSource->setRequestToken($cyberSourceResponse->requestToken);
            $paymentCyberSource->setValue(serialize($cyberSourceResponse));
        }
        if (!empty($billingInfo) && isset($billingInfo['ipAddress'])) {
            $paymentCyberSource->setIp($billingInfo['ipAddress']);
        }

        $this->_em->persist($paymentCyberSource);
        $this->_em->flush();

        //update user address book.
        if (is_array($userAddressInfo) && !empty($userAddressInfo)) {
            $this->_em->getRepository('FaUserBundle:UserAddressBook')->addUserAddress($paymentObj->getUser(), $userAddressInfo);
        }
        return $paymentCyberSource->getId();
    }

    /**
     * Get card type array.
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getCardTypeOptions($container)
    {
        $translator = CommonManager::getTranslator($container);

        $cardType        = array();
        $cardType['001'] = $translator->trans('Visa', array(), 'frontend-cyber-source');
        $cardType['002'] = $translator->trans('MasterCard', array(), 'frontend-cyber-source');
        $cardType['024'] = $translator->trans('Maestro (UK Domestic)', array(), 'frontend-cyber-source');
        $cardType['033'] = $translator->trans('Visa Electron', array(), 'frontend-cyber-source');

        return $cardType;
    }

    /**
     * Get card type array.
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getAllowedCardTypeOptions()
    {

        $cardType        = array();
        $cardType['001'] = self::VISA;
        $cardType['002'] = self::MASTERCARD;
        $cardType['024'] = self::MAESTRO;
        $cardType['033'] = self::VISA_ELECTRON;

        return $cardType;
    }

    /**
     * Get card type class for css.
     *
     * @param string $cardTypeId Card type.
     *
     * @return mixed
     */
    public function getCardTypeClass($cardTypeString)
    {
        $cardType        = array();
        $cardType['001'] = 'visa';
        $cardType['002'] = 'mastercard';
        $cardType['024'] = 'maestro';
        $cardType['033'] = 'visa-electron';

        return isset($cardType[$cardTypeString]) ? $cardType[$cardTypeString] : null;
    }

    /**
     * Get payment method options.
     *
     * @param integer $userId     User id.
     * @param object  $container  Container identifier.
     * @param boolean $showAddNew Show add new.
     *
     * @return array
     */
    public function getPaymentMethodOptions($userId, $container, $showAddNew = true)
    {
        $options    = array();
        $translator = CommonManager::getTranslator($container);
        $tokens     = $this->_em->getRepository('FaPaymentBundle:PaymentTokenization')->getUserTokens($userId);

        if (count($tokens)) {
            foreach ($tokens as $token) {
                $options[$token->getId()] = '<span class="'.$this->getCardTypeClass($token->getCardType()).'">&nbsp;</span>***'.$token->getCardNumber().'&nbsp;'.$token->getCardHolderName();
            }
        }

        if ($showAddNew) {
            $options[0] = $translator->trans('Add new card', array(), 'frontend-cyber-source');
        }

        return $options;
    }
}
