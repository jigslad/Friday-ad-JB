<?php

namespace Fa\Bundle\CoreBundle\Form\Type;

// use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface; // old 2.7 symfony
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;

class JsChoiceList implements ChoiceListInterface
{
    public function getChoices()
    {
        return array();
    }

    public function getChoicesForValues(array $values)
    {
        return $values;
    }

    public function getIndicesForChoices(array $choices)
    {
        return $choices;
    }

    public function getIndicesForValues(array $values)
    {
        return $values;
    }

    public function getPreferredViews()
    {
        return array();
    }

    public function getRemainingViews()
    {
        return array();
    }

    public function getValues()
    {
        return array();
    }
    
    public function getStructuredValues()
    {
        return array();
    }
    
    public function getOriginalKeys()
    {
        return array();
    }

    public function getValuesForChoices(array $choices)
    {
        return $choices;
    }
}
