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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Length;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * This is user business profile user detail form.
 *
  * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserBusinessProfileUserDetailType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager class object.
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
     * @param object $container Container instance.
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
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'first_name',
                TextType::class,
                array(
                    'label' => 'First name',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new Regex(array('pattern' => '/^[a-z0-9 _-]+$/i', 'groups' => array('user_business_profile'), 'message' => $this->translator->trans('First name cannot have special characters other than hyphen and underscore', array(), 'validators'))),new NotBlank(array( 'groups' => array('user_business_profile'),'message' => $this->translator->trans('Please enter first name.', array(), 'validators'))))
                )
            )
            ->add(
                'last_name',
                TextType::class,
                array(
                    'label' => 'Last name',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new Regex(array('pattern' => '/^[a-z0-9 _-]+$/i', 'groups' => array('user_business_profile'), 'message' => $this->translator->trans('Last name cannot have special characters other than hyphen and underscore', array(), 'validators'))),new NotBlank(array( 'groups' => array('user_business_profile'),'message' => $this->translator->trans('Please enter last name.', array(), 'validators'))))
                )
            )
            ->add(
                'business_name',
                TextType::class,
                array(
                    'label' => 'Business name',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new NotBlank(array( 'groups' => array('user_business_profile'),'message' => $this->translator->trans('Please enter business name.', array(), 'validators'))))
                )
            );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\User',
                'validation_groups' => array('user_business_profile'),
                'translation_domain' => 'frontend-my-profile',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_business_profile_user_detail';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_business_profile_user_detail';
    }
}
