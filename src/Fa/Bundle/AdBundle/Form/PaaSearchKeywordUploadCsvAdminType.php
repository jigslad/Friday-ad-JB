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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\AdBundle\Entity\PaaSearchKeywordCategory;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * PaaSearchKeywordUploadCsvAdminType form.
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class PaaSearchKeywordUploadCsvAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Translator.
     *
     * @var object
     */
    protected $translator;

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
            'file',
            FileType::class,
            array(
                'label'       => 'Upload Csv',
                'constraints' => array(
                                     new NotBlank(array('message' => $this->translator->trans('File is required.', array(), 'validators'))),
                                     //new File(array('mimeTypes' => array('text/csv', 'application/csv', 'text/plain', 'text/x-c'), 'mimeTypesMessage' => $this->translator->trans('Please upload a valid csv file.', array(), 'validators')))
                                 ),
            )
        )
        ->add('save', SubmitType::class, array('label' => 'Upload',));

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_paa_search_keyword_upload_csv_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_paa_search_keyword_upload_csv_admin';
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $file = $form->get('file')->getData();

            $webPath        = $this->container->get('kernel')->getRootDir().'/../web';
            $uploadFilePath = $webPath.DIRECTORY_SEPARATOR.'uploads/keyword';

            //upload file
            $file->move($uploadFilePath, 'paa_search_keywords.csv');

            // Remove file from other directories
            if (file_exists($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/import/paa_search_keywords.csv')) {
                unlink($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/import/paa_search_keywords.csv');
            }

            if (file_exists($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/process/paa_search_keywords.csv')) {
                unlink($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/process/paa_search_keywords.csv');
            }

            if (file_exists($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/processing/paa_search_keywords.csv')) {
                unlink($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/processing/paa_search_keywords.csv');
            }

            if (file_exists($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/processed/paa_search_keywords.csv')) {
                unlink($this->container->get('kernel')->getRootDir().'/../web/uploads/keyword/processed/paa_search_keywords.csv');
            }
        }
    }
}
