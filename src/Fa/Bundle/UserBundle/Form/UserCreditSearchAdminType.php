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
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Package discount code search admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserCreditSearchAdminType extends AbstractType
{
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
            'user_credit__status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray($this->container)),
            )
        )
        ->add(
            'user_credit__category_id',
            ChoiceType::class,
            array(
                'choices'  => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container)),
                'mapped'   => false,
                'placeholder' => $this->translator->trans('Please select category.', array(), 'validators'),
            )
        )
        ->add(
            'user_credit__package_sr_no',
            ChoiceType::class,
            array(
                'label' => 'Package type',
                'choices' => array_flip($this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray()),
                'multiple'  => true,
                'expanded'  => true,
                'required'  => false,
            )
        )
        ->add('search', SubmitType::class, array('label' => 'Search'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_user_user_credit_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_credit_search_admin';
    }
}
