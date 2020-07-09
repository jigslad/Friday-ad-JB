<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Form;

use phpDocumentor\Reflection\Types\Collection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EmailBundle\Repository\EmailTemplateRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Email template admin type.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EmailTemplateAdminType extends AbstractType
{
    /**
     * Entity manger object.
     *
     * @var object
     */
    protected $entityManager;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            ->add('name')
            ->add('subject')
            ->add('body_html', TextareaType::class, array('attr' => array('rows' => 10)))
            ->add('body_text', TextareaType::class, array('attr' => array('rows' => 10)))
            ->add('sender_email')
            ->add('sender_name')
            ->add('bcc_emails')
            ->add('params_help')
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container)),
                )
            )
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'required' => false,
                    'choices' => array_flip(array(
                        '' => 'Select email type',
                        EmailTemplateRepository::PACKAGE_TYPE_ID => 'Package',
                    )),
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class)
            ->add('saveAndPreview', SubmitType::class);
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));

        //validate twig content for syntax
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $emailTemplate = $event->getData();
                $form = $event->getForm();

                if ($emailTemplate->getBodyHtml()) {
                    $errorMessage = CommonManager::validateTwigContent($this->container, $emailTemplate->getBodyHtml());
                    if ($errorMessage) {
                        $event->getForm()->get('body_html')->addError(new \Symfony\Component\Form\FormError('Twig Error: '.$errorMessage));
                    }
                }

                if ($emailTemplate->getBodyText()) {
                    $errorMessage = CommonManager::validateTwigContent($this->container, $emailTemplate->getBodyText());
                    if ($errorMessage) {
                        $event->getForm()->get('body_text')->addError(new \Symfony\Component\Form\FormError('Twig Error: '.$errorMessage));
                    }
                }
            }
        );
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            if ($data->getBccEmails()) {
                $emails = explode(',', $data->getBccEmails());
                foreach ($emails as $email) {
                    $emailErr = '';
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $emailErr = $email." is Invalid";
                        $form->get('bcc_emails')->addError(new FormError($emailErr));
                    }
                }
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
                'data_class' => 'Fa\Bundle\EmailBundle\Entity\EmailTemplate'
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
        return 'fa_email_template_email_template_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_email_template_email_template_admin';
    }
}
