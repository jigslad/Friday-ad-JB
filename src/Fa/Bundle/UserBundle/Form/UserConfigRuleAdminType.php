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
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Validator\Constraints\NotBlank;
// use Symfony\Component\Validator\Constraints\True;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Fa\Bundle\UserBundle\Repository\UserConfigRuleRepository;
use Fa\Bundle\UserBundle\Entity\UserConfigRule;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user configuration rule form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserConfigRuleAdminType extends AbstractType
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
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user', HiddenType::class, array('data' => $this->request->get('user_id'), 'mapped' => false))
                ->add('config', HiddenType::class, array('data' => $this->request->get('config_id'), 'mapped' => false))
                ->add('value', TextType::class)
                ->addEventSubscriber(new AddDatePickerFieldSubscriber('period_from'))
                ->addEventSubscriber(new AddDatePickerFieldSubscriber('period_to'))
                ->add('save', SubmitType::class);


        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'))
                ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $configRule = $event->getData();
        $form       = $event->getForm();

        if ($form->isValid()) {
            $user   = $this->em->getRepository('FaUserBundle:User')->find($form->get('user')->getData());
            $config = $this->em->getRepository('FaCoreBundle:Config')->find($form->get('config')->getData());

            $configRule->setUser($user);
            $configRule->setConfig($config);
            $configRule->setPeriodFrom(CommonManager::getTimeStampFromStartDate($form->get('period_from')->getData()));
            $configRule->setPeriodTo(CommonManager::getTimeStampFromEndDate($form->get('period_to')->getData()));
        }
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $userConfigRule = $event->getData();
        $form           = $event->getForm();

        $configId = $form->get('config')->getData();
        $userId   = $form->get('user')->getData();

        // Check if user or config id exist on database
        $user   = $this->em->getRepository('FaUserBundle:User')->find($userId);
        $config = $this->em->getRepository('FaCoreBundle:Config')->find($configId);

        if (!$user) {
            $form->get('value')->addError(new FormError('Unable to find user entity.'));
        }

        if (!$config) {
            $form->get('value')->addError(new FormError('Unable to find config entity.'));
        }

        // Check uniquq entry for user and config field while new form
        if (!$userConfigRule->getId()) {
            $uniqueUserConfigRule = $this->em->getRepository('FaUserBundle:UserConfigRule')->findOneBy(array('config' => $configId, 'user' => $userId));
            if ($uniqueUserConfigRule) {
                $form->get('value')->addError(new FormError('Configuration rule is already set for this user.'));
            }
        }

        // Period dates validation
        if ($form->get('period_from')->getData() && $form->get('period_to')->getData()) {
            $periodFrom = CommonManager::getTimeStampFromStartDate($form->get('period_from')->getData());
            $periodTo   = CommonManager::getTimeStampFromEndDate($form->get('period_to')->getData());

            if ($periodTo < $periodFrom) {
                $form->get('period_to')->addError(new FormError('Perido To date should be greater than Period From date.'));
            }
        }
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserConfigRule'
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_config_rule_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_config_rule_admin';
    }
}
