<?php

namespace Fa\Bundle\CoreBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Fa\Bundle\CoreBundle\Form\EventListner\AddAutoSuggestFieldSubscriber
 *
 * AddAutoSuggestFieldSubscriber event subscriber
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 */
class AddAutoSuggestFieldSubscriber implements EventSubscriberInterface
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager
     *
     * @var object
     */
    private $em;

    /**
     * Field name in form
     *
     * @var string
     */
    private $fieldName;

    /**
     * Json field name in form
     *
     * @var string
     */
    private $jsonFieldName;

    /**
     * Field bundle and entity name
     *
     * @var string
     */
    private $fieldBundleEntity;

    /**
     * Selected autosuggest value
     *
     * @var string
     */
    private $selectedData;

    /**
     * Field options
     *
     * @var array
     */
    private $fieldOptions = array();

    /**
     * Constructor.
     *
     * @param object $container         Container instance
     * @param string $fieldName         Field name in form
     * @param string $jsonFieldName     Json field name in form
     * @param string $fieldBundleEntity Bundle and entity name
     * @param string $selectedData      Selected auto suggest field data
     * @param string $fieldOptions      Field options
     *
     */
    public function __construct(ContainerInterface $container, $fieldName, $jsonFieldName, $fieldBundleEntity, $selectedData = '', $fieldOptions = array())
    {
        $this->container         = $container;
        $this->em                = $this->container->get('doctrine')->getManager();
        $this->fieldName         = $fieldName;
        $this->jsonFieldName     = $jsonFieldName;
        $this->fieldBundleEntity = $fieldBundleEntity;
        $this->fieldOptions      = $fieldOptions;
        $this->selectedData      = $selectedData;
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
            FormEvents::SUBMIT       => 'onSubmit',
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
        $form = $event->getForm();

        $this->addAutoSuggestField($form);

        $this->addJsonField($form, $this->selectedData ? $this->selectedData : '');
    }

    /**
     * Callbak method for PRE_SUBMIT_DATA form event
     *
     * @param object $event event instance
     *
     * @return void
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $this->addJsonField($form, $form->get($this->fieldName)->getData());
    }

    /**
     * Add autosuggest field to form
     *
     * @param object $form form instance
     *
     * @return void
     */
    private function addAutoSuggestField($form)
    {
        $fieldOptions = array(
                            'required' => false,
                            'mapped'   => false,
                        );

        $fieldOptions = array_merge($fieldOptions, $this->fieldOptions);

        $form->add($this->fieldName, TextType::class, $fieldOptions);
    }

    /**
     * Add json field to form
     *
     * @param object $form         form instance
     * @param string $selectedData json data
     *
     * @return void
     */
    private function addJsonField($form, $selectedData = '')
    {
        $jsonData = array();
        if ($selectedData) {
            $entities = explode(',', $selectedData);

            foreach ($entities as $entityId) {
                $entity = $this->em->getRepository($this->fieldBundleEntity)->findOneBy(array('id' => $entityId));

                if ($entity) {
                    $text = $entity->getName();
                    if ($this->fieldBundleEntity == 'FaEntityBundle:Category') {
                        if (method_exists($entity, 'getParent') && $entity->getParent()) {
                            $jsonData[] = array('id'=> $entity->getId(), 'text' => $text, 'text2' => $entity->getParent()->getName());
                        }
                    } else {
                        if (method_exists($entity, 'getParent') && $entity->getParent()) {
                            $text .= ' (';
                            $text .= $entity->getParent()->getName();

                            if ($entity->getParent()->getParent()) {
                                $text .= ', '.$entity->getParent()->getParent()->getName();
                            }
                            $text .= ')';
                        }

                        $jsonData[] = array('id'=> $entity->getId(), 'text' => $text);
                    }
                }
            }
        }

        $fieldOptions = array('mapped' => false);

        if (count($jsonData)) {
            $fieldOptions['data'] = json_encode($jsonData);
        }

        $form->add($this->jsonFieldName, HiddenType::class, $fieldOptions);
    }
}
