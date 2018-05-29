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
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This is system resource form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ResourceType extends AbstractType
{
    /**
     * Entity manager.
     *
     * @var object
     */
    protected $entityManager;

    /**
     * Entity manager.
     *
     * @var object
     */
    protected $container;

    /**
     * Array of attributes.
     *
     * @var array
     */
    protected $attribute;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->entityManager = $options['em'];
        $this->container = $options['container'];
        $this->attribute     = $options['attr'];
        $builder
            ->add('name')
            ->add('code')
            ->add('resource')
            ->add('resource_group')
            ->add('is_menu')
            ->add('display_in_tree')
            ->add('icon_class')
            ->add('lft')
            ->add('rgt')
            ->add('root')
            ->add('lvl')
            ->add('created_at')
            ->add('updated_at')
            ->add('permission')
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\Resource'
            )
        )
        ->setRequired(
            array(
                'em',
                'container',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_bundle_userbundle_resource';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_bundle_userbundle_resource';
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
        $parentId = $this->container->get('request_stack')->getCurrentRequest()->get('parent_id', null);
        $data = $event->getData();
        $form = $event->getForm();

        if ($parentId) {
            $parent = $this->entityManager->getRepository('FaUserBundle:Resource')->find($parentId);

            if (!$parent) {
                throw new NotFoundHttpException('Unable to find resource entity.');
            }
            $data->setParent($parent);
        }
    }
}
