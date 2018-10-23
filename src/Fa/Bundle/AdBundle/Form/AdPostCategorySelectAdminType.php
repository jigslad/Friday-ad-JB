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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
// use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostCategorySelectAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostCategorySelectAdminType extends AbstractType
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
     * Constructor.
     *
     * @param object       $container    Container instance.
     * @param RequestStack $requestStack RequestStack instance.
     *
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->addEventSubscriber(
            new AddAutoSuggestFieldSubscriber(
                $this->container,
                'category_id',
                'category_id_json',
                'FaEntityBundle:Category'
            )
        )
        ->add(
            'user_id',
            HiddenType::class,
            array(
                'mapped' => false,
                'data' => $this->request->get('user_id', null)
            )
        );
        $this->addCategoryChoiceFields($builder);
    }

    /**
     * Add category choice fields.
     *
     * @param string $builder
     */
    private function addCategoryChoiceFields($builder)
    {
        $totalLevel = $this->em->getRepository('FaEntityBundle:Category')->getMaxLevel();

        if ($totalLevel) {
            for ($i = 1; $i <= $totalLevel; $i++) {
                if ($i == 1) {
                    $optionArray = array(
                        'placeholder' => 'Please select category',
                        'attr'        => array('class' => 'category category_'.$i),
                    );
                } else {
                    $optionArray = array(
                        'placeholder' => 'Please select subcategory',
                        'attr'        => array('class' => 'category category_'.$i),
                    );
                }
                $builder->addEventSubscriber(
                    new AddCategoryChoiceFieldSubscriber(
                        $this->container,
                        $i,
                        'category',
                        $optionArray,
                        null,
                        $totalLevel
                    )
                );
            }
        }
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_category_select_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_category_select_admin';
    }
}
