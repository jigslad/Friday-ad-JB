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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * This form is used to show nimber location.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class NimberPostcodeType extends AbstractType
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
     * @param object $container Container identifier.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form for nimber.
     *
     * @param FormBuilderInterface $builder Form builder.
     * @param array                $options Array of options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);
        $builder
            ->add(
                'zip',
                TextType::class,
                array(
                    'max_length' => 15,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter postcode.', array(), 'validators')))),
                    'data' => ($loggedInUser && $loggedInUser->getZip() ? $loggedInUser->getZip() : null),
                )
            )
            ->add('get_estimate_btn', SubmitType::class, array('label' => 'Get delivery estimate'));

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $form       = $event->getForm();
        $postCode   = trim($form->get('zip')->getData());

        //removed post code validation
        if ($postCode) {
            $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
            if (!$postCodeObj) {
                $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid post code.', array(), 'validators')));
            }
        }
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver Option resolver.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
                'translation_domain' => 'frontend-nimber',
            )
        );
    }

    /**
     * Get form name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_nimber_postcode';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_nimber_postcode';
    }
}
