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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used for edititng contact details.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class EditContactDetailsType extends AbstractType
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
        $builder
        ->add(
            'company_address',
            TextType::class,
            array('label' => 'Company address')
            )
            ->add(
                'phone1',
                TelType::class,
                array(
                    'label'       => 'Telephone 1',
                    'constraints' => array(new Regex(array('pattern' => '/^\+?\d{7,11}$/', 'message' => $this->translator->trans('Please enter correct telephone Number , A correct telephone number will contain a minimum of 7 digits and a maximum of 11 digits.', array(), 'validators'))))
                )
            )
            ->add(
                'phone2',
                TelType::class,
                array(
                    'label'       => 'Telephone 2',
                    'constraints' => array(new Regex(array('pattern' => '/^\+?\d{7,11}$/', 'message' => $this->translator->trans('Please enter correct telephone Number , A correct telephone number will contain a minimum of 7 digits and a maximum of 11 digits.', array(), 'validators'))))
                )
            )
            ->add(
                'website_link',
                TextType::class,
                array(
                    'label' => 'Website link',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Save changes'));
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserSite',
                'translation_domain' => 'frontend-my-profile',
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
        return 'fa_user_edit_contact_details';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_edit_contact_details';
    }
}
