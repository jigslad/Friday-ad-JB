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
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\AdBundle\Entity\Ad;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\EntityRegionCacheDoctrineCommand;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * ChangeStatusType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ChangeStatusAdminType extends AbstractType
{
    private $em;
    private $security_encoder;

    /**
     * The request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param Doctrine $doctrine
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder, RequestStack $requestStack, ContainerInterface $container)
    {
        $this->em                = $doctrine->getManager();
        $this->security_encoder  = $security_encoder;
        $this->request           = $requestStack->getCurrentRequest();
        $this->container         = $container;
        $this->translator        = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPresetData'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onSubmitData'));
        $builder->add('save', SubmitType::class);
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     *
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\AdBundle\Entity\Ad',
            )
        );
    }

    /**
     * Get name.
     *
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_ad_ad_change_status_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_ad_change_status_admin';
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function onPresetData(FormEvent $event)
    {
        $ad = $event->getData();
        $form = $event->getForm();

        if ($ad->getId()) {
            $form->add(
                'ad_status',
                EntityType::class,
                array(
                    'class' => 'FaEntityBundle:Entity',
                    'choice_label' => 'name',
                    'placeholder' => 'New Ad Status',
                    'label' => 'New Ad Status',
                    'mapped' => false,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please select new ad status.', array(), 'validators')))),
                    'query_builder' => function (EntityRepository $er) use ($ad) {
                        return $this->prepareChangeStatusQuery($er->createQueryBuilder(EntityRepository::ALIAS), $ad);
                    }
                )
            )
            ->add('return_url', HiddenType::class, array('data' => $this->request->get('return_url', null), 'mapped' => false));
        }
    }

    /**
     *
     * @param Object $qb Object.
     * @param Ad     $ad Object.
     */
    protected function prepareChangeStatusQuery($qb, Ad $ad)
    {
        $qb->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::AD_STATUS_ID)
            ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');

        switch ($ad->getStatus()->getId()) {
            case EntityRepository::AD_STATUS_LIVE_ID:
                $qb->andWhere(EntityRepository::ALIAS.'.id IN (:ids)');
                $qb->setParameter('ids', array(EntityRepository::AD_STATUS_INACTIVE_ID, EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_SOLD_ID));
                break;
            case EntityRepository::AD_STATUS_IN_MODERATION_ID:
            case EntityRepository::AD_STATUS_INACTIVE_ID:
            case EntityRepository::AD_STATUS_REJECTED_ID:
            case EntityRepository::AD_STATUS_REJECTEDWITHREASON_ID:
                $qb->andWhere(EntityRepository::ALIAS.'.id IN (:ids)');
                $qb->setParameter('ids', array(EntityRepository::AD_STATUS_LIVE_ID));
                break;
            case EntityRepository::AD_STATUS_DRAFT_ID:
            case EntityRepository::AD_STATUS_SOLD_ID:
            case EntityRepository::AD_STATUS_EXPIRED_ID:
                $qb->andWhere(EntityRepository::ALIAS.'.id IN (:ids)');
                $qb->setParameter('ids', array($ad->getStatus()->getId()));
                break;
            case EntityRepository::AD_STATUS_SCHEDULED_ADVERT_ID:
                $qb->andWhere(EntityRepository::ALIAS.'.id IN (:ids)');
                $qb->setParameter('ids', array(EntityRepository::AD_STATUS_INACTIVE_ID, EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_SOLD_ID));
                break;
            default:
                $qb->andWhere(EntityRepository::ALIAS.'.id IN (:ids)');
                $qb->setParameter('ids', array($ad->getStatus()->getId()));
                break;
        }

        return $qb;
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function onSubmitData(FormEvent $event)
    {
        $ad = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $ad->setStatus($form->get('ad_status')->getData());
        }
    }
}
