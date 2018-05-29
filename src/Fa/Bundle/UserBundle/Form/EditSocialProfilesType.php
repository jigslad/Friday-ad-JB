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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used for edit social profiles.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class EditSocialProfilesType extends AbstractType
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
                'facebook_url',
                TextType::class,
                array(
                    'label' => 'Facebook',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add(
                'google_url',
                TextType::class,
                array(
                    'label' => 'Google +',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add(
                'twitter_url',
                TextType::class,
                array(
                    'label' => 'Twitter',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add(
                'pinterest_url',
                TextType::class,
                array(
                    'label' => 'Pinterest',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )->add(
                'instagram_url',
                TextType::class,
                array(
                    'label' => 'Instagram',
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
        return 'fa_user_edit_social_profiles';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_edit_social_profiles';
    }
}
