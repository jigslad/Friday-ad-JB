<?php

namespace Fa\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\CoreBundle\Form\Type\JsChoiceList;

class JsChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices'           => new JsChoiceList(),
            'validation_groups' => false,
        ));
    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'js_choice';
    }
    
    public function getBlockPrefix()
    {
        return 'js_choice';
    }
}
