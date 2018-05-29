<?php

namespace Fa\Bundle\CoreBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AddDatePickerFieldSubscriber implements EventSubscriberInterface
{
    /**
     * Domicile field name in form
     *
     * @var string
     */
    private $dateFieldName;

    /**
     * Field options
     *
     * @var array
     */
    private $fieldOptions = array();

    /**
     * Constructor.
     *
     * @param string $dateFieldName date field name
     * @param array  $fieldOptions  field options
     *
     */
    public function __construct($dateFieldName = 'date', $fieldOptions = array())
    {
        $this->dateFieldName = $dateFieldName;
        $this->fieldOptions  = $fieldOptions;
    }

    /**
     * Bind form events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit'
        );
    }

    /**
     * Callbak method for PRE_SET_DATA form event
     *
     * @param object $event event instance
     *
     * @return void
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            $date = null;
        } else {
            $accessor = PropertyAccess::createPropertyAccessor();
            $date     = $accessor->getValue($data, $this->dateFieldName);

            if ($date) {
                $date = date('d/m/Y', $date);
            }
        }

        $this->addDateField($form, $date);
    }

    /**
     * Callbak method for PRE_SUBMIT_DATA form event
     *
     * @param object $event event instance
     *
     * @return void
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $date = array_key_exists($this->dateFieldName, $data) ? $data[$this->dateFieldName] : null;

        $this->addDateField($form, $date);
    }

    /**
     * Add date field to form
     *
     * @param object $form form instance
     * @param string $date date
     *
     * @return void
     */
    private function addDateField($form, $date = null)
    {
        $fieldOptions = array(
                            'required' => false,
                            'label'    => 'Period From',
                            'mapped'   => false,
                            'data'     => $date,
                            'attr'     => array(
                                              'field-help' => 'dd/mm/yyyy',
                                              'class' => 'fdatepicker',
                                              'autocomplete' => 'off'
                                          ),
                        );

        $fieldOptions = array_merge($fieldOptions, $this->fieldOptions);

        $form->add($this->dateFieldName, TextType::class, $fieldOptions);
    }
}
