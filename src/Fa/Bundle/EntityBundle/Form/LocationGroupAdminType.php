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
use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Fa\Bundle\EntityBundle\Entity\LocationGroupLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This command is used to location group admin type.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LocationGroupAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var object
     */
    private $em;

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
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $selDomicileTownOptionArray = array(
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Selected Domicile Town',
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please add atleast one domicile or town.', array(), 'validators'))),
                );
        $locationGroupId = null;
        if ($builder->getData()->getId()) {
            $locationGroupId = $builder->getData()->getId();
            if (isset($selDomicileTownOptionArray['constraints'])) {
                unset($selDomicileTownOptionArray['constraints']);
            }
        }
        $builder
            ->add('name')
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'choices' => array_flip(LocationGroupRepository::getLocationGroupTypeArray($this->container)),
                )
            )
            ->addEventSubscriber(new AddDomicileChoiceFieldSubscriber($this->container, false, 'domicile_id'))
            ->addEventSubscriber(new AddTownChoiceFieldSubscriber($this->container, false, 'town_id', 'domicile_id', array('multiple' => true)))
            ->add(
                'sel_domicile_town',
                ChoiceType::class,
                $selDomicileTownOptionArray
            )
            ->add(
                'related_print_edition_select',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Related print editions',
                    'choices' => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getOtherPrintEditionArrayForLocationGroupId($locationGroupId)),
                )
            )
            ->add(
                'related_print_edition',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Selected related print editions',
                    'choices' => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getSelectedPrintEditionArrayForLocationGroupId($locationGroupId)),
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class)
            ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'))
            ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'))
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $locationGroup = $event->getData();
                $form = $event->getForm();
                $selDomicileTown   = $form->get('sel_domicile_town')->getData();
                $locationGroupType = $form->get('type')->getData();
                if (count($selDomicileTown)) {
                    $domicileTownArray = $this->em->getRepository('FaEntityBundle:LocationGroupLocation')->checkDuplicateDomicileTownByLocationGroupId($locationGroup->getId(), $selDomicileTown, $locationGroupType);

                    if (count($domicileTownArray)) {
                        $event->getForm()->get('sel_domicile_town')->addError(new \Symfony\Component\Form\FormError(implode(',', $domicileTownArray).' already exist in another location group.'));
                    }
                }
            }
        );
    }

    /**
     * Pre set data.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPreSetData(FormEvent $event)
    {
        $locationGroup = $event->getData();
        $form = $event->getForm();
        $method = strtolower($this->container->get('request_stack')->getCurrentRequest()->getMethod());

        if ($locationGroup->getId() && $method == 'get') {
            $choices = $this->em->getRepository('FaEntityBundle:LocationGroupLocation')->getDomicileTownArrayByLocationGroupId($locationGroup->getId());
            $form->add(
                'sel_domicile_town',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Selected Domicile Town',
                    'choices' => array_flip($choices),
                    //'constraints' => new NotBlank(array('message' => $this->translator->trans('Please add atleast one domicile or town.', array(), 'validators'))),
                )
            );
        }

        $this->addDomicile($form, $locationGroup->getId(), $locationGroup->getType());
    }

    /**
     * Pre submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['sel_domicile_town']) && count($data['sel_domicile_town'])) {
            $domicileTownIds = array();
            foreach ($data['sel_domicile_town'] as $selectedId) {
                $explodeResult = explode('_', $selectedId);
                if (count($explodeResult) > 1) {
                    $domicileTownIds[] = $explodeResult[1];
                } else {
                    $domicileTownIds[] = $selectedId;
                }
            }
            array_unique($domicileTownIds);
            $choices = $this->em->getRepository('FaEntityBundle:Location')->getLocationKeyValueArrayByIds($domicileTownIds);
            $form->add(
                'sel_domicile_town',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Selected Domicile Town',
                    'choices' => array_flip($choices),
                )
            );
        }

        if (isset($data['related_print_edition']) && count($data['related_print_edition'])) {
            $form->add(
                'related_print_edition',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Selected related print editions',
                    'choices' => array_flip($this->em->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionArrayByIds($data['related_print_edition'])),
                )
            );
        }

        if (isset($data['related_print_edition_select']) && count($data['related_print_edition_select'])) {
            $form->add(
                'related_print_edition_select',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Related print editions',
                    'choices' => array_flip($this->em->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionArrayByIds($data['related_print_edition_select'])),
                )
            );
        } else {
            $form->add(
                'related_print_edition_select',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Related print editions',
                    'choices' => array(),
                )
            );
        }

        $this->addDomicile($form, $form->getData()->getId(), $data['type']);

        if (isset($data['town_id']) && count($data['town_id'])) {
            $choices = $this->em->getRepository('FaEntityBundle:Location')->getLocationKeyValueArrayByIds($data['town_id'], false);
            $form->add(
                'town_id',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'multiple' => true,
                    'label' => 'Selected Town',
                    'choices' => array_flip($choices)
                )
            );
        }
    }

    /**
     * Post submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPostSubmit(FormEvent $event)
    {
        $locationGroup = $event->getData();
        $form = $event->getForm();
        if ($form->isValid()) {
            //set location group's related print editions
            $relatedPrintEditions = $form->get('related_print_edition')->getData();
            $locationGroup->setRelatedPrintEdition(implode(',', $relatedPrintEditions));
            //remove location group location for edit location group
            if ($locationGroup->getId()) {
                $this->em->getRepository('FaEntityBundle:LocationGroupLocation')->removeRecordsByLocationGroupId($locationGroup->getId());
            }
            $domicileTowns   = $form->get('sel_domicile_town')->getData();
            $domicileTownIds = array();
            if (count($domicileTowns)) {
                //remove domicile if it's town is added
                foreach ($domicileTowns as $domicileTown) {
                    $tmpExplodeResult = explode('_', $domicileTown);
                    if (count($tmpExplodeResult) > 1) {
                        $key = array_search($tmpExplodeResult[0], $domicileTowns);
                        if ($key !== false) {
                            unset($domicileTowns[$key]);
                        }
                    }
                }
                //get all domicile and town ids array
                foreach ($domicileTowns as $domicileTown) {
                    $explodeResult = explode('_', $domicileTown);
                    if (count($explodeResult) > 1) {
                        $domicileTownIds[] = $explodeResult[1];
                    } else {
                        $domicileTownIds[] = $domicileTown;
                    }
                }
                array_unique($domicileTownIds);
                $countryObj = $this->em->getRepository('FaEntityBundle:Location')->find(LocationRepository::COUNTY_ID);
                $locations = $this->em->getRepository('FaEntityBundle:Location')->getLocationByIds($domicileTownIds);
                foreach ($locations as $location) {
                    $locationGroupLocation = new LocationGroupLocation();
                    $locationGroupLocation->setLocationGroup($locationGroup);
                    $locationGroupLocation->setLocationCountry($countryObj);
                    if ($location->getLvl() == 2) {
                        $locationGroupLocation->setLocationDomicile($location);
                    } else {
                        $locationGroupLocation->setLocationDomicile($location->getParent());
                        $locationGroupLocation->setLocationTown($location);
                    }
                    $this->em->persist($locationGroupLocation);
                }
                $this->em->flush();
            }
            // update non print location group.
            if ($locationGroup->getId() && $locationGroup->getId() != LocationGroupRepository::NON_PRINT_LOCATION_GROUP_ID) {
                $locationTableName = $this->em->getClassMetadata('FaEntityBundle:Location')->getTableName();
                $locationGroupLocationTableName = $this->em->getClassMetadata('FaEntityBundle:LocationGroupLocation')->getTableName();
                $sql = 'DELETE FROM '.$locationGroupLocationTableName.' WHERE location_group_id = '.LocationGroupRepository::NON_PRINT_LOCATION_GROUP_ID.';INSERT INTO '.$locationGroupLocationTableName.' (location_group_id, country_id, domicile_id, town_id) SELECT '.LocationGroupRepository::NON_PRINT_LOCATION_GROUP_ID.', '.LocationRepository::COUNTY_ID.', l.parent_id, l.id FROM '.$locationTableName.' l WHERE l.lvl = 3 and l.id NOT IN (select town_id from '.$locationGroupLocationTableName.' WHERE town_id IS NOT NULL);';
                $stmt = $this->em->getConnection()->prepare($sql);
                $stmt->execute();
            }
        }
    }

    /**
     * Add domicile field.
     *
     * @param object  $form
     * @param integer $locationGroupId
     * @param integer $locationGroupType
     */
    private function addDomicile($form, $locationGroupId, $locationGroupType)
    {
        if ($locationGroupType) {
            $choices = $this->em->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId(LocationRepository::COUNTY_ID);
            $domicileIds = $this->em->getRepository('FaEntityBundle:LocationGroupLocation')->getDomicileArrayByLocationGroupId($locationGroupId, $locationGroupType);

            foreach ($domicileIds as $domicileId) {
                unset($choices[$domicileId]);
            }

            $choices = array('' => 'Select Domicile') + $choices;

            $form->add(
                'domicile_id',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'label' => 'Select Domicile',
                    'choices' => array_flip($choices),
                )
            );
        } else {
            $form->add(
                'domicile_id',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'label' => 'Select Domicile',
                    'choices' => array('Select Domicile' => ''),
                )
            );
        }
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
                'data_class' => 'Fa\Bundle\EntityBundle\Entity\LocationGroup',
                'csrf_protection' => false,
            )
        );
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_entity_location_group_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_entity_location_group_admin';
    }
}
