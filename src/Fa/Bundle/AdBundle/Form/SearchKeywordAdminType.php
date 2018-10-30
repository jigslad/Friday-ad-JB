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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\AdBundle\Entity\SearchKeywordCategory;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * SearchKeywordAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class SearchKeywordAdminType extends AbstractType
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
     * How much categories entered
     *
     * @var integer
     */
    private $total_categories = 2;

    /**
     * Constructor.
     *
     * @param object $container
     */

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
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
            ->add('keyword', TextType::class)
            ->add(
                'search_count',
                TextType::class,
                array('label' => 'Monthly searches')
             )
             ->add(
                 'do_not_overwrite_category',
                 CheckboxType::class,
                 array(
                     'required' => false,
                     'label'    => 'Do not overwrite category',
                 )
             )
            ->add('save', SubmitType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
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
                'data_class' => 'Fa\Bundle\AdBundle\Entity\SearchKeyword'
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
        return 'fa_ad_search_keyword_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_search_keyword_admin';
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $searchKeyword = $event->getData();
        $form          = $event->getForm();

        if ($searchKeyword->getId()) {
            $counter     = 1;
            $categoryIds = $this->em->getRepository('FaAdBundle:SearchKeywordCategory')->getCategoryIdsByKeywordId($searchKeyword->getId());
            if (isset($categoryIds[$searchKeyword->getId()])) {
                foreach ($categoryIds[$searchKeyword->getId()] as $categoryId) {
                    $this->addCategoryAutoSuggestField($form, 'category_'.$counter, 'Category '.$counter, $categoryId);
                    $counter++;
                }
            }

            while ($counter <= $this->total_categories) {
                $this->addCategoryAutoSuggestField($form, 'category_'.$counter, 'Category '.$counter);
                $counter++;
            }
        }
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $searchKeyword = $event->getData();
        $form          = $event->getForm();

        if ($form->isValid()) {
            $this->saveKeywordCategories($form, $searchKeyword);
        }
    }

    /**
     * Save header image.
     *
     * @param object $form Form object.
     */
    public function saveKeywordCategories($form, $searchKeyword)
    {
        $processedKeyword = array();
        $processedKeyword[0]['search_keyword_id'] = $searchKeyword->getId();
        $processedKeyword[0]['category_id']       = null;
        $processedKeyword[0]['keyword']           = $form->get('keyword')->getData();
        $processedKeyword[0]['search_count']      = $form->get('search_count')->getData();

        for ($i = 1; $i <= $this->total_categories; $i++) {
            $field = 'category_'.$i;
            if ($form->has($field) && $form->get($field)->getData()) {
                $processedKeyword[$i]['search_keyword_id'] = $searchKeyword->getId();
                $processedKeyword[$i]['category_id']       = $form->get($field)->getData();
                $processedKeyword[$i]['keyword']           = $form->get('keyword')->getData().' in '.$this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $form->get($field)->getData());
                $processedKeyword[$i]['search_count']      = $form->get('search_count')->getData();
            }
        }

        // Remove old entries before new process for keyword.
        $this->em->getRepository('FaAdBundle:SearchKeywordCategory')->removeByKeywordId($searchKeyword->getId());

        // Insert new entries.
        foreach ($processedKeyword as $processedKeywordInfo) {
            $searchKeywordCategory = new SearchKeywordCategory();
            $searchKeywordCategory->setSearchKeywordId($processedKeywordInfo['search_keyword_id']);
            $searchKeywordCategory->setCategoryId($processedKeywordInfo['category_id']);
            $searchKeywordCategory->setKeyword($processedKeywordInfo['keyword']);
            $searchKeywordCategory->setSearchCount($processedKeywordInfo['search_count']);

            $this->em->persist($searchKeywordCategory);
            $this->em->flush($searchKeywordCategory);
        }
    }

    /**
     * Add auto-suggest dimension fields.
     *
     * @param object  $form            Form instance.
     * @param string  $fieldName       Field name.
     * @param integer $dimensionId     Dimension id.
     * @param array   $paaFieldOptions Paa field options.
     * @param object  $verticalObj     Vertical instance.
     *
     */
    protected function addCategoryAutoSuggestField($form, $field, $fieldName, $selectedId = null)
    {
        // autocomplete hidden field for value
        $form->add($field, HiddenType::class, array('mapped' => false, 'data' => $selectedId));

        // autocomplete text field
        $fieldOptions = array(
            'required' => false,
            'mapped'   => false,
            /** @Ignore */
            'label'    => $fieldName,
        );

        if ($selectedId) {
            $category = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $selectedId));
            if ($category) {
                $fieldOptions['data'] = $category->getName();
            }
        }

        $form->add($field.'_autocomplete', TextType::class, $fieldOptions);
    }
}
