<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Dotmailer search type form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerFilterAdminType extends AbstractType
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
    protected $em;

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
            'name',
            TextType::class,
            array(
                'label' => 'Filter name',
                'constraints' => array(
                                    new NotBlank(array('message' => $this->translator->trans('Filter name should not be blank.', array(), 'validators'))),
                                    new Length(array('max' => 20))
                                 ),
                'attr' => array('maxlength' => 20)
            )
        )
        ->add(
            'comment',
            TextareaType::class,
            array(
                'label' => 'Comment',
                'attr'  => array('rows' => 5)
            )
        )
        ->add('filters', HiddenType::class)
        ->add('save', SubmitType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitData'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_dotmailer_dotmailer_filter_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_dotmailer_dotmailer_filter_admin';
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
        $resolver->setDefaults(array(
            'data_class' => 'Fa\Bundle\DotMailerBundle\Entity\DotmailerFilter',
        ));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $dotmailerFilter = $event->getData();
        $form            = $event->getForm();

        $is24hLoopOptions = array(
                                'label'    => 'Repeat every 24h',
                                'required' => false,
                            );
        if (!$dotmailerFilter->getId()) {
            $is24hLoopOptions['data'] = false;
        }

        $form->add('is_24h_loop', CheckboxType::class, $is24hLoopOptions);
    }

    /**
     * On post submit data.
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $this->save($event);
    }

    /**
     * Save user site.
     *
     * @param object $form Form object.
     */
    public function save($event)
    {
        $form            = $event->getForm();
        $dotmailerFilter = $event->getData();

        if ($form->isValid()) {
            $dotmailerFilter->setName($form->get('name')->getData());
            $dotmailerFilter->setComment($form->get('comment')->getData());
            $dotmailerFilter->setFilters($form->get('filters')->getData());
            $dotmailerFilter->setIs24hLoop($form->get('is_24h_loop')->getData());

            if (!$dotmailerFilter->getId()) {
                $dotmailerFilter->setStatus(0);
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
                if ($user) {
                    $dotmailerFilter->setCreatedBy(ucwords($user->getFirstName().' '.$user->getLastName()));
                }
            }

            $this->em->persist($dotmailerFilter);
            $this->em->flush($dotmailerFilter);
        }
    }
}
