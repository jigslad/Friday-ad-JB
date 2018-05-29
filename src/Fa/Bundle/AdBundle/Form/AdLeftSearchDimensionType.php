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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * This form is used for left search dimension see more modal.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdLeftSearchDimensionType extends AbstractType
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
     * The request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * Constructor.
     *
     * @param object       $container    Container instance.
     * @param RequestStack $requestStack RequestStack instance.
     */
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->request   = $requestStack->getCurrentRequest();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Form builder.
     * @param array                $options Form options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('item__category_id', HiddenType::class)
        ->add('keywords', HiddenType::class)
        ->add('item__location', HiddenType::class)
        ->add('item__distance', HiddenType::class)
        ->add('item__price_from', HiddenType::class)
        ->add('item__price_to', HiddenType::class)
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $form                = $event->getForm();
        $searchParams        = $this->request->get('searchParams');
        $dimensionField      = $this->request->get('dimensionField');
        $dimensionId         = $this->request->get('dimensionId');
        $dimensionName       = $this->request->get('dimensionName');
        $dimensionSearchType = $this->request->get('dimensionSearchType');

        if ($dimensionField == 'item_motors__reg_year') {
            $fieldChoices   = CommonManager::getRegYearChoices();
        } else {
            $fieldChoices = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($dimensionId, $this->container);
        }
        $fieldOptions = array(/** @Ignore */'label' => $dimensionName, 'choices' => $fieldChoices);
        if ($dimensionSearchType == 'choice_checkbox') {
            $fieldOptions['expanded'] = true;
            $fieldOptions['multiple'] = true;
        } elseif ($dimensionSearchType == 'choice_link') {
            $fieldOptions['expanded'] = false;
            $fieldOptions['multiple'] = false;
        }

        $form->add($dimensionField, 'choice', $fieldOptions);
    }

    /**
     * Set default form options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => null,
                'translation_domain' => 'frontend-left-search',
                'csrf_protection'    => false,
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
        return 'fa_left_search_dimension';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_left_search_dimension';
    }
}
