<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * UserCreditAdminType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserCreditAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var object
     */
    private $em;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $categoryId = null;
        $packageSrNos = array();
        $expiresAt = null;
        $packageSrNoOptions = $this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray();
        if ($builder->getData()->getId()) {
            if ($builder->getData()->getExpiresAt()) {
                $expiresAt = date('d/m/Y', $builder->getData()->getExpiresAt());
            }
            if ($builder->getData()->getPackageSrNo()) {
                $packageSrNos = explode(',', $builder->getData()->getPackageSrNo());
                if (count($packageSrNoOptions) == count($packageSrNos)) {
                    $packageSrNos[] = '-1';
                }
            }
            $categoryId = ($builder->getData()->getCategory() ? $builder->getData()->getCategory()->getId() : null);
        }

        $builder
            ->add(
                'credit',
                TextType::class,
                array(
                    'label' => 'Number of credits',
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter the number of credits to allocate.', array(), 'validators'))),
                        new Regex(array('pattern' => "/^[0-9]+$/i", 'message' => "The value {{ value }} is not a valid integer value.")),
                    )
                )
            )
            ->add(
                'category_id',
                ChoiceType::class,
                array(
                    'label' => 'Category',
                    'choices'  => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container)),
                    'mapped'   => false,
                    'placeholder' => $this->translator->trans('Please select category.', array(), 'validators'),
                    'data' => $categoryId,
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please select category.', array(), 'validators'))),
                    )
                )
            )
            ->add(
                'package_sr_no',
                ChoiceType::class,
                array(
                    'label' => 'Package type',
                    'placeholder' => 'Any',
                    'choices' => array('All' => '-1') + array_flip($packageSrNoOptions),
                    'multiple'  => true,
                    'expanded'  => true,
                    'mapped'    => false,
                    'data' => $packageSrNos,
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please select a package type or types.', array(), 'validators'))),
                    )
                )
            )
            ->add('paid_user_only', CheckboxType::class, array('label' => 'Only usable with paid business profile', 'required' => false, 'value' => true))
            ->add(
                'expires_at',
                TextType::class,
                array(
                    'label' => 'Valid until',
                    'required' => false,
                    'attr' => array('class' => 'fdatepicker'),
                    'data' => $expiresAt,
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(EntityRepository::getStatusArray($this->container)),
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please select a status.', array(), 'validators'))),
                    )
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $userCredit = $event->getData();
        $form       = $event->getForm();

        if ($form->isValid()) {
            //set category
            if ($form->get('category_id')->getData()) {
                $userCredit->setCategory($this->em->getReference('FaEntityBundle:Category', $form->get('category_id')->getData()));
            } else {
                $userCredit->setCategory(null);
            }

            if ($form->get('expires_at')->getData()) {
                $userCredit->setExpiresAt(CommonManager::getTimeStampFromEndDate($form->get('expires_at')->getData()));
            }

            if ($form->get('package_sr_no')->getData()) {
                $packageSrNoArray = $form->get('package_sr_no')->getData();
                if (isset($packageSrNoArray[0]) && $packageSrNoArray[0] == '-1') {
                    unset($packageSrNoArray[0]);
                }

                asort($packageSrNoArray);
                $userCredit->setPackageSrNo(implode(',', $packageSrNoArray));
            } else {
                $userCredit->setPackageSrNo(null);
            }
        }
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserCredit',
            )
        );
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_user_user_credit_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_credit_admin';
    }
}
