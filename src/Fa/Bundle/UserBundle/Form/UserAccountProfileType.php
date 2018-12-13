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
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user account profile form.
 *
  * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAccountProfileType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager class object.
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
     * @param object $container Container instance.
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
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $loggedInUser = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();

        $builder
        ->add(
            'business_category_id',
            ChoiceType::class,
            array(
                'multiple' => false,
                'label' => 'Business category',
                'placeholder' => 'Please select category',
                'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container)),
            )
        )
        ->add(
            'user_roles',
            ChoiceType::class,
            array(
                'required'  => true,
                'multiple'  => false,
                'expanded'  => true,
                'mapped'    => false,
                'data' => ($loggedInUser ?  $this->em->getRepository('FaUserBundle:User')->getUserRole($loggedInUser->getId(), $this->container) : null),
                'label'     => 'User type',
                'choices'   => array_flip(RoleRepository::getCustomerRoles($this->container)),
                'constraints' => new NotBlank(array('groups'   => array('user_account_profile'), 'message' => $this->translator->trans('Please select account status.', array(), 'validators')))
            )
        )
        ->add(
            'payment_source',
            ChoiceType::class,
            array(
                'choices'  => array_flip($this->em->getRepository('FaPaymentBundle:PaymentCyberSource')->getPaymentMethodOptions($loggedInUser->getId(), $this->container, false)),
                'constraints' => array(new NotBlank(array('groups' => array('user_account_profile'), 'message' => $this->translator->trans('Please select payment source.', array(), 'validators')))),
                'multiple' => false,
                'expanded' => true,
                'mapped'   => false,
            )
        );

        $builder->add('save_profile_changes', SubmitType::class, array('label' => 'Save changes'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $loggedInUser   = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
        $form           = $event->getForm();
        $old_data       = $this->em->getUnitOfWork()->getOriginalEntityData($form->getData());
        $oldUserRole    = (isset($old_data['role']) ? $old_data['role']->getName() : null);
        $oldBusinessCat = $old_data['business_category_id'];

        if ($oldUserRole != $form->get('user_roles')->getData() || $oldBusinessCat != $form->get('business_category_id')->getData()) {
            $shopPackageDetail = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackageDetail($loggedInUser->getId());
            $activeAdCount     = $this->em->getRepository('FaAdBundle:Ad')->getActiveAdCountForUser($loggedInUser->getId());

            if (($shopPackageDetail && $shopPackageDetail->getPackage() && !$shopPackageDetail->getPackage()->getPrice() && !$activeAdCount) || (!$shopPackageDetail && !$activeAdCount)) {
                if ($form->get('user_roles')->getData()
                    && ($form->get('user_roles')->getData() == RoleRepository::ROLE_BUSINESS_SELLER || $form->get('user_roles')->getData() == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION)
                    && $form->has('business_category_id')
                    && $form->get('business_category_id')->getData() == '') {
                    $form->get('business_category_id')->addError(new FormError($this->translator->trans('Please select business category.', array(), 'validators')));
                }
            } else {
                $form->get('user_roles')->addError(new FormError($this->translator->trans('You cannot edit user type or business category setting while you have live adverts or a paid profile subscription.', array(), 'validators')));
            }
        }

        if ($form->has('payment_source') && $form->get('payment_source')->getData()) {
            $token = $this->em->getRepository('FaPaymentBundle:PaymentTokenization')->isValidUserToken($loggedInUser->getId(), $form->get('payment_source')->getData());
            if (!$token) {
                $form->get('payment_source')->addError(new FormError($this->translator->trans('Invalid payment source.', array(), 'validators')));
            }
        }
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $old_data       = $this->em->getUnitOfWork()->getOriginalEntityData($form->getData());
            $oldBusinessCat = $old_data['business_category_id'];
            $oldRoleId = $old_data['role_id'];
            $loggedInUser   = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
            $shopPackageDetail = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackageDetail($loggedInUser->getId());
            $user = $form->getData();
            $role = $form->get('user_roles')->getData();
            $oldRoleObj = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('id' => $oldRoleId));
            $oldRole = ($oldRoleObj ? $oldRoleObj->getName() : null);
            $updateSQL = '';
            if ($role) {
                if ($oldRole != $role) {
                    foreach ($user->getRoles() as $userRole) {
                        $user->removeRole($userRole);
                    }
                    $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => $role));
                    $user->addRole($sellerRole);
                    $user->setRole($sellerRole);

                    if ($role == RoleRepository::ROLE_SELLER) {
                        $this->em->getRepository('FaUserBundle:UserPackage')->closeActivePackage($loggedInUser);
                        $this->em->getRepository('FaUserBundle:UserSite')->removeBusinessUserSiteData($user->getId(), $this->container);
                        $user->setBusinessName(null);
                        $user->setBusinessCategoryId(null);
                        $updateSQL = "UPDATE ad SET is_trade_ad = '0' WHERE user_id = '".$user->getId()."'";
                    }

                    if ($role == RoleRepository::ROLE_BUSINESS_SELLER || $role == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                        $userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
                        if (!$userSite) {
                            $userSite = new UserSite();
                            $userSite->setUser($user);
                            $this->em->persist($userSite);
                            $this->em->flush($userSite);
                            $culture = CommonManager::getCurrentCulture($this->container);
                            CommonManager::removeCache($this->container, $this->getUserTableName().'|getUserProfileSlug|'.$user->getId().'_'.$culture);
                        }

                        $user->setImage(null);
                        $this->em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, 'my_account_user_upgrade', $this->container);
                        $this->em->getRepository('FaUserBundle:User')->removePrivateUserData($user->getId(), $this->container);
                        $user->setFreeTrialEnable(1);
                        $updateSQL = "UPDATE ad SET is_trade_ad = '1' WHERE user_id = '".$user->getId()."'";
                    }

                    if (!empty($updateSQL)) {
                        $stmt = $this->em->getConnection()->prepare($updateSQL);
                        $stmt->execute();

                        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index update --status="A,S,E" --user_id="'.$user->getId().'" >/dev/null &');
                    }
                }

                //update profile exposure category id.
                if ($role == RoleRepository::ROLE_BUSINESS_SELLER || $role == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                    $userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
                    //update profile exposure category id.
                    if ($userSite && $oldBusinessCat != $form->get('business_category_id')->getData()) {
                        $userSite->setProfileExposureCategoryId($user->getBusinessCategoryId());
                        $this->em->persist($userSite);
                        $this->em->flush($userSite);
                    }
                }
                // update payment source if it exist.
                if ($form->has('payment_source') && $form->get('payment_source')->getData() && $shopPackageDetail && $shopPackageDetail->getPayment()) {
                    $payment      = $shopPackageDetail->getPayment();
                    $paymentValue = unserialize($payment->getValue());
                    $token        = $this->em->getRepository('FaPaymentBundle:PaymentTokenization')->isValidUserToken($loggedInUser->getId(), $form->get('payment_source')->getData());

                    if ($token && isset($paymentValue['subscriptionID'])) {
                        $paymentValue['subscriptionID'] = $token->getSubscriptionId();
                        $payment->setValue(serialize($paymentValue));
                        $this->em->persist($payment);
                        $this->em->flush($payment);
                    }
                }
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\User',
                'validation_groups' => array('user_account_profile'),
                'translation_domain' => 'frontend-user-account-profile',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_account_profile';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_account_profile';
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getUserTableName()
    {
        return $this->em->getClassMetadata('FaUserBundle:User')->getTableName();
    }
}
