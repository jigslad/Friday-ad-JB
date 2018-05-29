<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Location type.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LocationType extends AbstractType
{
    /**
     * Entity manager.
     *
     * @var entityManager
     */
    protected $entityManager;

    /**
     * Attribute.
     *
     * @var attribute
     */
    protected $attribute;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->entityManager = $options['em'];
        $this->attribute     = $options['attr'];

        $builder
            ->add('name')
            ->add('latitude')
            ->add('longitude')
            ->add('lft')
            ->add('rgt')
            ->add('root')
            ->add('parent')
            ->add('lvl')
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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
                'data_class' => 'Fa\Bundle\EntityBundle\Entity\Location',
                'csrf_protection'   => false,
            )
        )
        ->setRequired(
            array(
                'em',
            )
        )
        ->setAllowedTypes('em', array('null', 'string', 'Doctrine\Common\Persistence\ObjectManager'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_bundle_EntityBundle_location';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_bundle_EntityBundle_location';
    }

    /**
     * On post submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPostSubmit(FormEvent $event)
    {
        if (isset($this->attribute) && isset($this->attribute['parent_id'])) {
            $location = $event->getForm()->getData();
            $parent   = $this->entityManager->getRepository('FaEntityBundle:Location')->find($this->attribute['parent_id']);

            if (!$parent) {
                throw new NotFoundHttpException('Unable to find Location entity.');
            }

            $location->setParent($parent);
        }
    }
}
