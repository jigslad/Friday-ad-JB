<?php 
namespace Fa\Bundle\AdBundle\Form;

use Fa\Bundle\AdBundle\Entity\Campaigns;
use Fa\Bundle\EntityBundle;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\AdBundle\Entity\PaaField;
use Fa\Bundle\AdBundle\Entity\PaaLiteFieldRule;
use Fa\Bundle\AdBundle\Repository\CampaignsRepository;
use Fa\Bundle\AdBundle\Repository\PaaLiteFieldRuleRepository;
use Fa\Bundle\PromotionBundle\Repository\PackageDiscountCodeRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class CampaignsAdminType extends AbstractType
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
     * The request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_campaigns_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_campaigns_admin';
    }

    /**
     * Constructor.
     *
     * @param object       $container    Container instance
     * @param RequestStack $requestStack RequestStack instance
     *
     */

    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->em = $container->get('doctrine')->getManager();
        $this->container = $container;
        $this->translator = CommonManager::getTranslator($container);
        $this->request   = $requestStack->getCurrentRequest();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
         
        if (!$builder->getForm()->getData()->getId()) {
            $builder
            ->addEventSubscriber(
                new AddAutoSuggestFieldSubscriber(
                    $this->container,
                    'category_id',
                    'category_id_json',
                    'FaEntityBundle:Category',
                    $this->request->get('category_id'),
                    array(
                        /** @Ignore */
                        'label' => false,
                        //'attr'  => array('field-help' => 'Select or change category for load category-wise PAA fields.')
                    )
                )
            );
            $builder->add('saveAndNew', SubmitType::class);
        }  

        if($builder->getForm()->getData()->getId() || $this->request->get('category_id')!='' || $this->request->get('fa_ad_campaigns_admin')['category_id']!='') {

            $builder->add('campaign_name', TextType::class, array(
                    'attr' => array(
                        'placeholder' => 'Please enter name here....',
                    ),
                    'label' => "Campaign Name",
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(),
                        new Length(array('max' => 100)),
                    )))
                ->add('page_title', TextType::class, array(
                    'attr' => array(
                        'placeholder' => 'Please enter title here....',
                    ),
                    'label' => "Page Title",
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(),
                        new Length(array('max' => 250)),
                    )))
                ->add('page_title_color', ChoiceType::class,array(
                    'label' => "Text Color",
                    'multiple' => false,
                    'choices'   => array('Light'=>'Light','Dark'=>'Dark')
                    ))
                ->add('seo_page_title', TextType::class, array(
                    'label' => "Seo Page Title",
                    'attr' => array( 'placeholder' => 'Please enter seo page title here....',),
                    'required' => false,
                    ))
                ->add('seo_page_description', TextType::class, array(
                    'label' => "Seo Page Description",
                    'attr' => array( 'placeholder' => 'Please enter seo page description here....',),
                    'required' => false,
                    ))
                ->add('seo_page_keywords', TextType::class, array(
                    'attr' => array(
                        'placeholder' => 'Please enter seo page keywords here....',
                    ),
                    'required' => false,
                    'label' => "Seo Page Keywords",
                    'constraints' => array(
                        new Length(array('max' => 250)),
                    )))
                ->add('slug', TextType::class, array('label' => "Slug",'required' => true,
                    'constraints' => array(
                        new NotBlank(),
                    )))
                    ->add('form_fill_times', NumberType::class, array(
                    'label' => "How many times can one user fill this form?",
                    'attr' => array('min' =>1, 'max' => 100),
                    'required' => false,
                    ))
                ->add('intro_text', TextareaType::class, array(
                    'attr' => array(
                        'placeholder' => 'Please enter description here....',
                        'class' => 'tinymce'
                    ),
                    'label' => "Intro Text in HTML",
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(),
                    ),
                    
                    ))
                ->add('background_file',FileType::class, array('label' => 'Header Image','required' => false))
                ->add('campaign_status', ChoiceType::class, array(
                    'label' => "Status",
                    'constraints' => array(
                        new NotBlank(),
                    ),
                    'choices' => array(
                        'Active'=>1, 
                        'In-Active'=>2),
                    'Select Status'=>'empty_value'));
       
            $builder->add('saveAndNew', SubmitType::class);
        }
        $builder->add('save', SubmitType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }
    
     /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event
     *            Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $campaign = $event->getData();
        $form = $event->getForm();
        $this->validateHeaderImage($form,$campaign);
    }
    
    protected function validateHeaderImage($form,$campaign)
    {
        if($campaign->getCampaignBackgroundFileName()=='' && $form->get('background_file')->getData() == '') {
            //$form->get('background_file')->addError(new FormError($this->translator->trans('Please upload header image.', array(), 'validators')));
        }
    }
    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $campaign = $event->getData();
        $form         = $event->getForm();
        $formdata     = $this->request->get('fa_ad_campaigns_admin');
        $categoryId = '';$getCategoryObj =array();

        if(!empty($formdata)  && isset($formdata['category_id']) && !$campaign->getId()) {
            $categoryId = $formdata['category_id'];
        } elseif (!$campaign->getId()) {
            $categoryId = $this->request->get('category_id', null);
        } else {
            $categoryId = $campaign->getCategory()->getId();
        }

        $this->addCategroyPaaLiteFieldsForm($form, $categoryId, $campaign);
    }

    /**
     * Callbak method for PRE_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (!$form->getData()->getId() && !empty($data)) {
            $categoryId = array_key_exists('category_id', $data) ? $data['category_id'] : null;
        } else {
            $categoryId = $form->getData()->getCategory()->getId();
        }
        
        $this->addCategroyPaaLiteFieldsForm($form, $categoryId, $form->getData());
    }

    /**
     *  Add campaign wise paa fields.
     *
     * @param object  $form         Form instance.
     * @param integer $categoryId   Selected campaign id.
     * @param object  $campaign campaign instance.
     */
    private function addCategroyPaaLiteFieldsForm($form, $categoryId = null, $campaign = null)
    {
        if ($categoryId) {
            //Edit
            $form->add('discount_code', 'choice', array(
                'label' => "Add a discount code if basic ads in this category are not free",
                'required' =>false,
                'choices' => array('0' => 'Select Discount Code') + $this->em->getRepository('FaPromotionBundle:PackageDiscountCode')->getPackageDiscountCodeArrayByCategoryId($categoryId)
            ));

            if ($campaign && $campaign->getId()) {
                $fieldRules = $this->em->getRepository('FaAdBundle:PaaLiteFieldRule')->getPaaLiteFieldRulesByCategoryId($categoryId);
                
                foreach ($fieldRules as $fieldRule) {
                    $fieldId = $fieldRule->getPaaLiteField()->getId();
                    $field   = $fieldRule->getPaaLiteField()->getField();
                    $label   = $fieldRule->getPaaLiteField()->getLabel();

                    $form->add($field, new PaaFieldAdminType($this->container, $fieldRule), array('mapped' => false, 'label' => /** @Ignore */$label, 'required' => false));
                }
            } else {
                $ord           = 1;
                $PaaLiteFieldsData = $this->em->getRepository('FaAdBundle:PaaField')->getPaaFieldsByCategoryAncestor($categoryId, true);
                foreach ($PaaLiteFieldsData as $fieldId => $PaaLiteFieldData) {
                    if ($PaaLiteFieldData['is_rule']) {
                        $fieldRule = $PaaLiteFieldData['data'];
                        $field = $fieldRule->getPaaField()->getField();
                        $label = $fieldRule->getPaaField()->getLabel();
                        $form->add($field, new PaaFieldAdminType($this->container, $fieldRule), array('mapped' => false, 'label' => /** @Ignore */$label, 'required' => false));
                    } else {
                        $PaaLiteField = $PaaLiteFieldData['data'];
                        $form->add($PaaLiteField->getField(), new PaaFieldAdminType($this->container, null, $PaaLiteField, $ord), array('mapped' => false, 'label' => /** @Ignore */$PaaLiteField->getLabel(), 'required' => false));
                    }

                    $ord++;
                }

            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            array(
                'data_class' => 'Fa\Bundle\AdBundle\Entity\Campaigns',
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();
                    if (!$data->getId()) {
                        return array('new');
                    } else {
                        return array('edit');
                    }
                },
            )
        ));
    }

    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $campaign = $event->getForm()->getData();

        if ((!$form->getData()->getId()) && (!$form->get('background_file')->getData())) {
            $form->get('background_file')->addError(new FormError('Please upload header image.'));
        } 

        if ($form->isValid()) {
            
            if (!$form->getData()->getId()) {
                $this->save($form);
                
            } else {
                $oldFile = $campaign->getAbsolutePath();
                $oldFileName = $campaign->getCampaignBackgroundFileName();

                $backgroundFile     = $form->get('background_file')->getData();
                $backgroundFileName = null;
                if ($backgroundFile !== null) {
                    $backgroundFileName = uniqid().'.'.$backgroundFile->guessExtension();
                    $campaign->setBackgroundFile($backgroundFile);
                    $campaign->setCampaignBackgroundFileName($backgroundFileName);
                }

                $this->save($form);

                $this->em->persist($campaign);
                $this->em->flush();

                if ($backgroundFile !== null && $backgroundFileName) {
                    $this->removeImage($campaign, $oldFile, $oldFileName);
                    $this->uploadBackgroundImage($campaign, $backgroundFileName);
                }
            }
        }
    }

    /**
     * Upload background image.
     *
     * @param object $campaign Campaign object.
     * @param string $fileName    File name.
     *
     * @return void
     */
    public function uploadBackgroundImage($campaign, $fileName)
    {
        if ($fileName) {
            $campaign->setCampaignBackgroundFileName($fileName);
            $campaign->getBackgroundFile()->move($campaign->getUploadRootDir(), $fileName);
            $campaign->setBackgroundFile(null);
        }
    }

    /**
     * Remove image if image is not assign to any other rule.
     *
     * @param object $campaign Header image object.
     * @param string $file        Image file path.
     * @param string $file        Image file name.
     */
    public function removeImage($campaign, $file, $fileName)
    {
        // Count how many rules found with same image, delete image if only one rule found
        $data['query_filters'] = array('campaigns' => array('campaign_background_filename' => $fileName));
        $this->container->get('fa.sqlsearch.manager')->init($this->em->getRepository('FaAdBundle:Campaigns'), $data);
        $imageCount = $this->container->get('fa.sqlsearch.manager')->getResultCount();

        // Delete image from directory
        if ($imageCount < 1) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }


    /**
     * Save campaign.
     *
     * @param object  $form     Form object.
     */
    public function save($form)
    {
        $campaign       = $form->getData();
        $campaign_name  = $form->get('campaign_name')->getData();
        $status         = $form->get('campaign_status')->getData();
        $campaignId     = $form->getData()->getId();
        $campaign_background_file = $form->get('background_file')->getData();

        $backgroundFileName = null;
        if ($campaign_background_file !== null) {
            $backgroundFileName = uniqid().'.'.$campaign_background_file->guessExtension();
        }
        
        $is_not_deletable = 0;
        if($campaignId=='') {
            $campaign = new Campaigns();
            $campaign->setBackgroundFile($campaign_background_file);
            $campaign->setCampaignBackgroundFileName($backgroundFileName);
            $category_id    = $form->get('category_id')->getData();
            $category = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $category_id));
            $category_parent_id = $category->getParent()->getId();
            //$is_not_deletable = ($category_parent_id==1)?1:0;
            $campaign->setCategory($category);
            $campaign->setIsNotDeletable($is_not_deletable);
        } else {
            $categoryId = $form->getData()->getCategory()->getId();
            $category = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $categoryId));
            $category_parent_id = $category->getParent()->getId();
            //$is_not_deletable = ($category_parent_id==1)?1:0;
            $campaign->setIsNotDeletable($is_not_deletable);
        } 

        $campaign->setCampaignName($campaign_name);
        $campaign->setPageTitle($form->get('page_title')->getData());
        $campaign->setPageTitleColor($form->get('page_title_color')->getData());
        $campaign->setSeoPageDescription($form->get('seo_page_description')->getData());
        $campaign->setSeoPageTitle($form->get('seo_page_title')->getData());
        $campaign->setSeoPageKeywords($form->get('seo_page_keywords')->getData());
        $campaign->setSlug($form->get('slug')->getData());
        $campaign->setFormFillTimes($form->get('form_fill_times')->getData());
        $campaign->setDiscountCode($form->get('discount_code')->getData());
        $campaign->setIntroText($form->get('intro_text')->getData());
        
        $campaign->setCampaignStatus($status);
        $this->em->persist($campaign);
        $this->em->flush($campaign);

        if($campaignId == '') {
            $this->uploadBackgroundImage($campaign, $backgroundFileName);
            $categoryId = $form->get('category_id')->getData();
            if ($categoryId) {
                $category = $this->em->getRepository('FaEntityBundle:Category')->find($categoryId);

                // Check if rule is already added for this category
               /* if ($this->checkRuleExist($categoryId)) {
                    $form->get('category_id')->addError(new FormError('Campaign already exists for category : '.$category->getName()));
                }*/

                // Check if display same order is used many times for different fields
                $this->validateDisplayOrder($form, $categoryId, $campaign);

                if ($form->isValid()) {
                    $this->savePaaLiteFieldRules($form, $category,$campaign);
                }
            }
        } else {
            $categoryId = $form->getData()->getCategory()->getId();
            $this->validateDisplayOrder($form, $categoryId, $campaign);
            $this->saveEditPaaLiteFieldRules($form, $categoryId,$campaign);
        }
    }

    /**
     * Save paa field rule in create mode.
     *
     * @param object  $form     Form instance.
     * @param object  $category Select category instance.
     */
    private function savePaaLiteFieldRules($form, $category,$campaign)
    {
        
        $paaLiteFields = $this->em->getRepository('FaAdBundle:PaaField')->getAllPaaFields($category->getId());
        foreach ($paaLiteFields as $PaaLiteField) {
            $field = $PaaLiteField->getField();
            if ($form->has($field)) {
                $fieldRule    = $form->get($field)->getData();
                $PaaLiteFieldRule = new PaaLiteFieldRule();
                $PaaLiteFieldRule->setLabel($fieldRule['label']);
                $PaaLiteFieldRule->setPlaceholderText($fieldRule['placeholder_text']);
                $PaaLiteFieldRule->setStatus($fieldRule['status']);
                $PaaLiteFieldRule->setIsRequired($fieldRule['is_required']);
                $PaaLiteFieldRule->setIsRecommended($fieldRule['is_recommended']);
                $PaaLiteFieldRule->setHelpText($fieldRule['help_text']);
                $PaaLiteFieldRule->setErrorText($fieldRule['error_text']);
                $PaaLiteFieldRule->setOrd($fieldRule['ord']);
                $PaaLiteFieldRule->setDefaultValue($fieldRule['default_value']);
                $PaaLiteFieldRule->setMinValue($fieldRule['min_value']);
                $PaaLiteFieldRule->setMaxValue($fieldRule['max_value']);
                $PaaLiteFieldRule->setStep($fieldRule['step']);
                $PaaLiteFieldRule->setCategory($category);
                $PaaLiteFieldRule->setCampaign($campaign);
                $PaaLiteFieldRule->setPaaLiteField($PaaLiteField);
                $PaaLiteFieldRule->setIsAdded($fieldRule['is_added']);
                if ($field == 'photo_error') {
                    $PaaLiteFieldRule->setMinMaxType(PaaLiteFieldRuleRepository::MIN_MAX_TYPE_RANGE);
                }
                $this->em->persist($PaaLiteFieldRule);
                $this->em->flush();
            }
        }
    }

    /**
     * Save paa field rule in edit mode.
     *
     * @param object  $form       Form instance.
     * @param integer $categoryId Selected campaign id.
     */
    private function saveEditPaaLiteFieldRules($form, $categoryId,$campaign)
    {
        $PaaLiteFieldRules = $this->em->getRepository('FaAdBundle:PaaLiteFieldRule')->getPaaLiteFieldRulesByCampaignId($campaign->getId(),$categoryId);
        foreach ($PaaLiteFieldRules as $PaaLiteFieldRule) {
            $PaaLiteField = $PaaLiteFieldRule->getPaaLiteField();
            $field    = $PaaLiteField->getField();
            if ($form->has($field)) {
                $fieldRule = $form->get($field)->getData();
                $PaaLiteFieldRule->setLabel($fieldRule['label']);
                $PaaLiteFieldRule->setPlaceholderText($fieldRule['placeholder_text']);
                $PaaLiteFieldRule->setStatus($fieldRule['status']);
                $PaaLiteFieldRule->setIsRequired($fieldRule['is_required']);
                $PaaLiteFieldRule->setIsRecommended($fieldRule['is_recommended']);
                $PaaLiteFieldRule->setHelpText($fieldRule['help_text']);
                $PaaLiteFieldRule->setErrorText($fieldRule['error_text']);
                $PaaLiteFieldRule->setOrd($fieldRule['ord']);
                $PaaLiteFieldRule->setDefaultValue($fieldRule['default_value']);
                $PaaLiteFieldRule->setMinValue($fieldRule['min_value']);
                $PaaLiteFieldRule->setMaxValue($fieldRule['max_value']);
                $PaaLiteFieldRule->setStep($fieldRule['step']);
                $PaaLiteFieldRule->setIsAdded($fieldRule['is_added']);
                $PaaLiteFieldRule->setCampaign($campaign);
                $this->em->persist($PaaLiteFieldRule);
                $this->em->flush();
            }
        }
    }

    /**
     * Check for duplicate campaignwise rule.
     *
     * @param integer $categoryId Selected campaign id.
     *
     * @return boolean
     */
    private function checkRuleExist($categoryId)
    {
        $PaaLiteFieldRule = $this->em->getRepository('FaAdBundle:PaaLiteFieldRule')->findOneBy(array('category' => $categoryId));
        if ($PaaLiteFieldRule) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check for duplicate campaignwise rule.
     *
     * @param object  $form         Form instance.
     * @param integer $categoryId   Selected campaign id.
     * @param object  $PaaLiteFieldRule Paa field rule instance.
     */
    private function validateDisplayOrder($form, $categoryId, $campaign = null)
    {
        return true;
        //Edit
        if ($campaign && $campaign->getId()) {
            $displayOrder  = array();
            $PaaLiteFieldRules = $this->em->getRepository('FaAdBundle:PaaLiteFieldRule')->getPaaLiteFieldRulesByCategoryId($categoryId);
            foreach ($PaaLiteFieldRules as $PaaLiteFieldRule) {
                $PaaLiteField = $PaaLiteFieldRule->getPaaLiteField();
                $field    = $PaaLiteField->getField();
                if ($form->has($field)) {
                    $fieldRule = $form->get($field)->getData();
                    if (in_array($fieldRule['ord'], $displayOrder) && $fieldRule['is_added']==1) {
                        $form->get($field)->addError(new FormError("'".$field."' ' ".$fieldRule['ord']." ' display order is given to more than one fields."));
                    } else {
                        $displayOrder[] = $fieldRule['ord'];
                    }
                }
            }
        } else {
            $displayOrder  = array();
            $PaaLiteFields     = $this->em->getRepository('FaAdBundle:PaaField')->getAllPaaFields($categoryId);
            foreach ($PaaLiteFields as $PaaLiteField) {
                $field = $PaaLiteField->getField();
                if ($form->has($field)) {
                    $fieldRule = $form->get($field)->getData();
                    if (in_array($fieldRule['ord'], $displayOrder)) {
                        $form->get($field)->addError(new FormError("' ".$fieldRule['ord']." ' display order is given to more than one fields."));
                    } else {
                        $displayOrder[] = $fieldRule['ord'];
                    }
                }
            }
        }
    }
}
?>