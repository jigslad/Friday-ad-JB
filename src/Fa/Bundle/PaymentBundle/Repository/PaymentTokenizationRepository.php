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
use Fa\Bundle\PaymentBundle\Entity\PaymentTokenization;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PaymentTokenizationRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'pt';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get token by user id by id.
     *
     * @param integer $userId User id.
     *
     * @return Object
     */
    public function getUserTokens($userId)
    {
        $query = $this->getBaseQueryBuilder()
            ->where(self::ALIAS.'.user = '.$userId)
            ->orderBy(self::ALIAS.'.created_at', 'DESC');
        $tokens = $query->getQuery()->getResult();

        return $tokens;
    }

    /**
     * Check is valid token.
     *
     * @param integer $userId
     * @param integer $tokenId
     *
     * @return mixed
     */
    public function isValidUserToken($userId, $tokenId)
    {
        $query = $this->getBaseQueryBuilder()
            ->where(self::ALIAS.'.id = '.$tokenId);
        $token = $query->getQuery()->getOneOrNullResult();

        if ($token && $token->getUser()->getId() == $userId) {
            return $token;
        } else {
            return false;
        }
    }

    /**
     * Get token by subscription id.
     *
     * @param integer $userId         User id.
     * @param integer $subscriptionId Subscription id.
     *
     * @return mixed
     */
    public function getTokenBySubscriptionId($userId, $subscriptionId)
    {
        $token = null;
        if ($subscriptionId) {
            $query = $this->getBaseQueryBuilder()
            ->where(self::ALIAS.'.subscription_id = '.$subscriptionId)
            ->andWhere(self::ALIAS.'.user = '.$userId)
            ->setMaxResults(1)
            ->orderBy(self::ALIAS.'.id', 'DESC');
            $token = $query->getQuery()->getOneOrNullResult();
        }

        return $token;
    }

    /**
     *
     * @param integer $userId         User id.
     * @param integer $subscriptionId Subscription id.
     * @param string  $cardNumber     Last 4 digit card number.
     * @param string  $cardHolderName Card holder name.
     * @param string  $cardType       Card type.
     * @param string  $paymentMethod  Payment method.
     * @param array   $billTo         Billing array.
     *
     * @return integer
     */
    public function addNewToken($userId, $subscriptionId, $cardNumber, $cardHolderName, $cardType, $paymentMethod, $billTo)
    {
        $paymentTokenization = new PaymentTokenization();
        $paymentTokenization->setUser($this->_em->getReference('FaUserBundle:User', $userId));
        $paymentTokenization->setSubscriptionId($subscriptionId);
        $paymentTokenization->setCardNumber($cardNumber);
        $paymentTokenization->setCardHolderName($cardHolderName);
        if ($cardType) {
            $paymentTokenization->setCardType($cardType);
        } else {
            $paymentTokenization->setCardType($this->getCreditCardType($cardNumber));
        }
        $paymentTokenization->setPaymentMethod($paymentMethod);
        if (is_array($billTo) && count($billTo)) {
            $paymentTokenization->setValue(serialize(array('billto' => $billTo)));
        }

        $this->_em->persist($paymentTokenization);
        $this->_em->flush();

        return $paymentTokenization->getId();
    }

    /**
     * Validate credit card types.
     *
     * @param string $cardnumber
     *
     * return boolean
     */
    public function validateCreditCardTypes($cardnumber)
    {

        $isValid = false;

        // Remove any spaces from the credit card number
        $cardNo = str_replace (' ', '', $cardnumber);

        $allowedCardTypes = $this->_em->getRepository('FaPaymentBundle:PaymentCyberSource')->getAllowedCardTypeOptions();
        $cards            = $this->getCardInfoArray();

        foreach ($allowedCardTypes as $key => $cardname) {
            // Establish card type
            $cardType = -1;
            for ($i=0; $i<sizeof($cards); $i++) {
                // See if it is this card (ignoring the case of the string)
                if (strtolower($cardname) == strtolower($cards[$i]['name'])) {
                    $cardType = $i;
                    break;
                }
            }

            // If card type not found, report an error
            if ($cardType == -1) {
                return false;
            }

            // Load an array with the valid prefixes for this card
            $prefix = explode(',',$cards[$cardType]['prefixes']);

            // Now see if any of them match what we have in the card number
            $PrefixValid = false;
            for ($i=0; $i<sizeof($prefix); $i++) {
                $exp = '/^' . $prefix[$i] . '/';
                if (preg_match($exp,$cardNo)) {
                    $PrefixValid = true;
                    break;
                }
            }

            // If it isn't a valid prefix there's no point at looking at the length
            if ($PrefixValid) {
                $isValid = true;
                break;
            }
        }

        return $isValid;
    }

    /**
     * Get credit card type.
     *
     * @param string $cardnumber
     *
     * return string
     */
    public function getCreditCardType($cardnumber)
    {

        $cardType = null;

        // Remove any spaces from the credit card number
        $cardNo = str_replace (' ', '', $cardnumber);

        $allowedCardTypes = $this->_em->getRepository('FaPaymentBundle:PaymentCyberSource')->getAllowedCardTypeOptions();
        $cards            = $this->getCardInfoArray();

        foreach ($allowedCardTypes as $key => $cardname) {
            // Establish card type
            $cardType = -1;
            for ($i=0; $i<sizeof($cards); $i++) {
                // See if it is this card (ignoring the case of the string)
                if (strtolower($cardname) == strtolower($cards[$i]['name'])) {
                    $cardType = $i;
                    break;
                }
            }

            // If card type not found, report an error
            if ($cardType == -1) {
                return null;
            }

            // Load an array with the valid prefixes for this card
            $prefix = explode(',',$cards[$cardType]['prefixes']);

            // Now see if any of them match what we have in the card number
            $PrefixValid = false;
            for ($i=0; $i<sizeof($prefix); $i++) {
                $exp = '/^' . $prefix[$i] . '/';
                if (preg_match($exp,$cardNo)) {
                    $PrefixValid = true;
                    break;
                }
            }

            // If it isn't a valid prefix there's no point at looking at the length
            if ($PrefixValid) {
                $cardType = $key;
                break;
            }
        }

        return $cardType;
    }

    /**
     * Get cards information array with allowed formats.
     *
     * @return array
     */
    public function getCardInfoArray()
    {

        // Define the cards we support. You may add additional card types.

        //  Name:      As in the selection box of the form - must be same as user's
        //  Length:    List of possible valid lengths of the card number for the card
        //  prefixes:  List of possible prefixes for the card
        //  checkdigit Boolean to say whether there is a check digit

        // Don't forget - all but the last array definition needs a comma separator!

        $cards = array (
                    array (
                        'name' => 'American Express',
                        'length' => '15',
                        'prefixes' => '34,37',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Diners Club Carte Blanche',
                        'length' => '14',
                        'prefixes' => '300,301,302,303,304,305',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Diners Club',
                        'length' => '14,16',
                        'prefixes' => '36,38,54,55',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Discover',
                        'length' => '16',
                        'prefixes' => '6011,622,64,65',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Diners Club Enroute',
                        'length' => '15',
                        'prefixes' => '2014,2149',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'JCB',
                        'length' => '16',
                        'prefixes' => '35',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Maestro',
                        'length' => '12,13,14,15,16,18,19',
                        'prefixes' => '5018,5020,5038,6304,6759,6761,6762,6763',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'MasterCard',
                        'length' => '16',
                        'prefixes' => '51,52,53,54,55',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Solo',
                        'length' => '16,18,19',
                        'prefixes' => '6334,6767',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'Switch',
                        'length' => '16,18,19',
                        'prefixes' => '4903,4905,4911,4936,564182,633110,6333,6759',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'VISA',
                        'length' => '13,16',
                        'prefixes' => '4',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'VISA Electron',
                        'length' => '13,16',
                        'prefixes' => '417500,4917,4913,4508,4844',
                        'checkdigit' => true
                    ),
                    array (
                        'name' => 'LaserCard',
                        'length' => '16,17,18,19',
                        'prefixes' => '6304,6706,6771,6709',
                        'checkdigit' => true
                    )
            );

        return $cards;
    }
}
