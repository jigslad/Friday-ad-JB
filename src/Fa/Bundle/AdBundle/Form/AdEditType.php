<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * AdEditType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdEditType extends AdPostType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * Ad instance
     *
     * @var $ad
     */
    protected $ad;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->ad = isset($options['data']['ad']) ? $options['data']['ad'] : null;

        $builder
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $ad          = $this->ad;
        $form        = $event->getForm();
        $categoryId  = $ad->getCategory()->getId();
        $adStatusId  = $ad->getStatus()->getId();
        $verticalObj = $this->getVerticalObject($ad);
        $metaData    = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;

        // check whether to fill data from moderation or not.
        $ad = $this->getAdObjectWithModeratedData($ad);

        $this->addCategroyPaaFieldsForm($form, $categoryId, $ad, true, $verticalObj);

        $form->add('paa_ordered_fields', HiddenType::class, array('data' => implode(',', $this->orderedFields), 'mapped' => false));
        //$form->add('photo_error', 'text', array('mapped' => false));

        if (in_array($adStatusId, $this->em->getRepository('FaAdBundle:Ad')->getRepostButtonInEditAdStatus())) {
            $form->add('save', SubmitType::class, array('label' => 'Save and repost'));
        } elseif ($adStatusId == EntityRepository::AD_STATUS_DRAFT_ID) {
            $form->add('save', SubmitType::class, array('label' => 'Save and publish'));
        } else {
            $form->add('save', SubmitType::class, array('label' => 'Save'));
        }

        // Ad specific phone number field for business user.
        //$this->addBusinessAdField($form, $ad);
    }

    /**
     * Callbak method for POST_SUBMIT form event
     *
     * @param object $event Event instance
     */
    public function postSubmit(FormEvent $event)
    {
        $ad   = $this->ad;
        $form = $event->getForm();

        if ($form->isValid()) {
            $this->saveAdOrSendForModeration($ad);
        }
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $this->ad;
        $form = $event->getForm();

        $this->validatePrice($form);
        $this->validateAdLocation($form);
        $this->validateDescription($form);
        $this->validateBusinessAdField($form);
        $this->validateYoutubeField($form);
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => null,
                'translation_domain' => 'frontend-ad-edit',
            )
        );
    }

    /**
     * Get ad vertical repository.
     *
     * @param integer $categoryId Category Id.
     *
     * @return object
     */
    protected function getVerticalRepository()
    {
        $repoName = 'FaAdBundle:Ad'.CommonManager::getCategoryClassNameById($this->getRootCategoryId(), true);
        return $this->em->getRepository($repoName);
    }

    /**
     * Get ad status which needs to direct save in edit, no need send for moderation.
     *
     * @return array
     */
    protected function getDirectSaveInEditAdStatus()
    {
        return $this->em->getRepository('FaAdBundle:Ad')->getDirectSaveInEditAdStatus();
    }

    /**
     * Get ad object with set moderated data.
     *
     * @param object $ad Ad object.
     *
     * @return object
     */
    protected function getAdObjectWithModeratedData($ad)
    {
        // check whether to fill data from moderation or not where moderation request send or not.
        $adModerate = $this->em->getRepository('FaAdBundle:AdModerate')->findByAdIdAndModerationQueueFilter($ad->getId(), array(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT, AdModerateRepository::MODERATION_QUEUE_STATUS_MANUAL_MODERATION, AdModerateRepository::MODERATION_QUEUE_STATUS_SEND));
        if ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_IN_MODERATION_ID || ($adModerate && $adModerate->getStatus()->getId() == EntityRepository::AD_STATUS_IN_MODERATION_ID) || ($adModerate && $adModerate->getModerationQueue() == 0)) {
            if ($adModerate && $adModerate->getValue()) {
                $this->setModerationValue(unserialize($adModerate->getValue()));
            }
        }

        // fill ad object from moderation
        if (count($this->moderationValue) > 0 && isset($this->moderationValue['ad'][0])) {
            $ad = $this->em->getRepository('FaAdBundle:Ad')->setObjectFromModerationData($this->moderationValue['ad'][0]);
        }

        return $ad;
    }

    /**
     * Save ad or send for modertaion.
     *
     * @param object $ad Ad object.
     *
     * @return object
     */
    protected function saveAdOrSendForModeration($ad)
    {
        $data                 = $this->request->get($this->getName());
        $data['user_id']      = $ad->getUser()->getId();
        $data['category_id']  = $ad->getCategory()->getId();
        $data['ad_status_id'] = $ad->getStatus()->getId();
        $adPostManager = $this->container->get('fa_ad.manager.ad_post');
        if (in_array($ad->getStatus()->getId(), $this->getDirectSaveInEditAdStatus())) {
            $adPostManager->saveAd($data, $ad->getId(), true);
        } else {
            $adPostManager->sendAdForModeration($ad, $data);
        }
    }

    /**
     * Validate price.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('price')) {
            if ($form->get('price')->getData() == '') {
                $form->get('price')->addError(new FormError('Value should not be blank.'));
            } else {
                if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData(), $matches)) {
                    $form->get('price')->addError(new FormError('Price is invalid.'));
                }
            }
        }
    }
}
