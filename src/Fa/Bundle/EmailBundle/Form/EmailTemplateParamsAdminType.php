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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Email template search type form.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EmailTemplateParamsAdminType extends AbstractType
{
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
        $this->emailTemplateParams = $this->em->getRepository('FaEmailBundle:EmailTemplate')->findOneBy(array('id' => $options['attr']['template_id']));

        $parameters = array_combine(explode('|', $this->emailTemplateParams->getParams()), explode('|', $this->emailTemplateParams->getParamsValue()));

        foreach ($parameters as $key => $val) {
            $builder->add($key, TextType::class, array('required' => true, 'data' => $val, 'constraints' => new NotBlank(array('message' => $this->translator->trans('Please enter parameter value.', array(), 'validators')))));
        }

        $builder->add('save', SubmitType::class)
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Post submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->isValid()) {
            $parameters  = $form->getData();
            $paramString = array();
            $paramVal   = array();

            foreach ($parameters as $key => $val) {
                $paramString[] = $key;
                $paramVal[] = $val;
            }

            $this->emailTemplateParams->setParams(implode('|', $paramString));
            $this->emailTemplateParams->setParamsValue(implode('|', $paramVal));
            $this->em->persist($this->emailTemplateParams);
            $this->em->flush();
        }
    }


    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_email_template_email_template_params_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_email_template_email_template_params_admin';
    }
}
