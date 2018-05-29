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

use Fa\Bundle\UserBundle\Entity\RoleResourcePermission;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * This is rolr resource permission form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class RoleResourcePermissionType extends AbstractType
{
    /**
     * Entity manager.
     *
     * @var object
     */
    protected $entityManager;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->entityManager = $options['em'];
        $builder
            ->add('role', HiddenType::class)
            ->add('permission', HiddenType::class)
            ->add('resource', HiddenType::class);
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fa\Bundle\UserBundle\Entity\RoleResourcePermission'
        ))->setRequired(array(
                'em'
            ))
            ->setAllowedTypes('em', array('null', 'string', 'Doctrine\Common\Persistence\ObjectManager'));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_bundle_userbundle_roleresourcepermission';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_bundle_userbundle_roleresourcepermission';
    }
}
