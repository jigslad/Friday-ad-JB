<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\UserBundle\Entity\Resource;

/**
 * This fixture is used to load resource data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadResourceData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load fixture.
     *
     * @param ObjectManager $em object.
     */
    public function load(ObjectManager $em)
    {
        //return false;

        $createPermissionObj = $em->getRepository('\Fa\Bundle\UserBundle\Entity\Permission')->findOneBy(array('name' => 'CREATE'));
        $editPermissionObj   = $em->getRepository('\Fa\Bundle\UserBundle\Entity\Permission')->findOneBy(array('name' => 'EDIT'));
        $deletePermissionObj = $em->getRepository('\Fa\Bundle\UserBundle\Entity\Permission')->findOneBy(array('name' => 'DELETE'));
        $viewPermissionObj   = $em->getRepository('\Fa\Bundle\UserBundle\Entity\Permission')->findOneBy(array('name' => 'VIEW'));

        $resource_root = new Resource();
        $resource_root->setName('All');
        $resource_root->setIsMenu(0);
        $resource_root->setDisplayInTree(1);
        $resource_root->setPermission($em->getRepository('\Fa\Bundle\UserBundle\Entity\Permission')->findOneBy(array('name' => 'MASTER')));
        $em->persist($resource_root);
        $em->flush();

        $userManegment = new Resource();
        $userManegment->setName('User Management');
        $userManegment->setIsMenu(1);
        $userManegment->setDisplayInTree(1);
        $userManegment->setIconClass('fi-torso');
        $userManegment->setResourceGroup('user_admin');
        $userManegment->setParent($resource_root);
        $userManegment->setPermission($viewPermissionObj);
        $em->persist($userManegment);
        $em->flush();

        $resource_user = new Resource();
        $resource_user->setName('User');
        $resource_user->setIsMenu(1);
        $resource_user->setDisplayInTree(1);
        $resource_user->setIconClass('fi-torso');
        $resource_user->setResourceGroup('user_admin');
        $resource_user->setParent($userManegment);
        $resource_user->setPermission($viewPermissionObj);
        $em->persist($resource_user);
        $em->flush();

        $resource_user_list = new Resource();
        $resource_user_list->setName('User list');
        $resource_user_list->setResource('user_admin');
        $resource_user_list->setIsMenu(1);
        $resource_user_list->setDisplayInTree(1);
        $resource_user_list->setIconClass('fi-list');
        $resource_user_list->setParent($resource_user);
        $resource_user_list->setPermission($viewPermissionObj);
        $em->persist($resource_user_list);
        $em->flush();

        $resource_user_add = new Resource();
        $resource_user_add->setName('User add');
        $resource_user_add->setResource('user_new_admin');
        $resource_user_add->setIsMenu(1);
        $resource_user_add->setDisplayInTree(1);
        $resource_user_add->setIconClass('fi-plus');
        $resource_user_add->setParent($resource_user);
        $resource_user_add->setPermission($createPermissionObj);
        $em->persist($resource_user_add);
        $em->flush();

        $resource_user_create = new Resource();
        $resource_user_create->setName('User create');
        $resource_user_create->setResource('user_create_admin');
        $resource_user_create->setIsMenu(0);
        $resource_user_create->setDisplayInTree(0);
        $resource_user_create->setParent($resource_user_add);
        $resource_user_create->setPermission($createPermissionObj);
        $em->persist($resource_user_create);
        $em->flush();

        $resource_user_edit = new Resource();
        $resource_user_edit->setName('User edit');
        $resource_user_edit->setResource('user_edit_admin');
        $resource_user_edit->setIsMenu(0);
        $resource_user_edit->setDisplayInTree(1);
        $resource_user_edit->setParent($resource_user);
        $resource_user_edit->setPermission($editPermissionObj);
        $em->persist($resource_user_edit);
        $em->flush();

        $resource_user_update = new Resource();
        $resource_user_update->setName('User update');
        $resource_user_update->setResource('user_update_admin');
        $resource_user_update->setIsMenu(0);
        $resource_user_update->setDisplayInTree(0);
        $resource_user_update->setParent($resource_user_edit);
        $resource_user_update->setPermission($editPermissionObj);
        $em->persist($resource_user_update);
        $em->flush();

        $resource_user_delete = new Resource();
        $resource_user_delete->setName('User delete');
        $resource_user_delete->setResource('user_delete_admin');
        $resource_user_delete->setIsMenu(0);
        $resource_user_delete->setDisplayInTree(1);
        $resource_user_delete->setParent($resource_user);
        $resource_user_delete->setPermission($deletePermissionObj);
        $em->persist($resource_user_delete);
        $em->flush();

        $resource_login_as_user = new Resource();
        $resource_login_as_user->setName('Login as user');
        $resource_login_as_user->setResource('login_as_user');
        $resource_login_as_user->setIsMenu(0);
        $resource_login_as_user->setDisplayInTree(1);
        $resource_login_as_user->setParent($resource_user);
        $resource_login_as_user->setPermission($viewPermissionObj);
        $em->persist($resource_login_as_user);
        $em->flush();

        $resource_user_detail_admin = new Resource();
        $resource_user_detail_admin->setName('User Detail');
        $resource_user_detail_admin->setResource('user_show_admin');
        $resource_user_detail_admin->setIsMenu(0);
        $resource_user_detail_admin->setDisplayInTree(1);
        $resource_user_detail_admin->setParent($resource_user);
        $resource_user_detail_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_detail_admin);
        $em->flush();

        $resource_user_ad_list_admin = new Resource();
        $resource_user_ad_list_admin->setName('User Ads');
        $resource_user_ad_list_admin->setResource('user_ad_list_admin');
        $resource_user_ad_list_admin->setIsMenu(0);
        $resource_user_ad_list_admin->setDisplayInTree(1);
        $resource_user_ad_list_admin->setParent($resource_user);
        $resource_user_ad_list_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_ad_list_admin);
        $em->flush();

        $resource_user_payment_list_admin = new Resource();
        $resource_user_payment_list_admin->setName('User Payments');
        $resource_user_payment_list_admin->setResource('user_payment_list_admin');
        $resource_user_payment_list_admin->setIsMenu(0);
        $resource_user_payment_list_admin->setDisplayInTree(1);
        $resource_user_payment_list_admin->setParent($resource_user);
        $resource_user_payment_list_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_payment_list_admin);
        $em->flush();

        $resource_user_reset_password = new Resource();
        $resource_user_reset_password->setName('Reset password');
        $resource_user_reset_password->setResource('user_reset_password');
        $resource_user_reset_password->setIsMenu(0);
        $resource_user_reset_password->setDisplayInTree(1);
        $resource_user_reset_password->setParent($resource_user);
        $resource_user_reset_password->setPermission($editPermissionObj);
        $em->persist($resource_user_reset_password);
        $em->flush();

        $resource_user_change_status = new Resource();
        $resource_user_change_status->setName('Change status');
        $resource_user_change_status->setResource('user_change_status');
        $resource_user_change_status->setIsMenu(0);
        $resource_user_change_status->setDisplayInTree(1);
        $resource_user_change_status->setParent($resource_user);
        $resource_user_change_status->setPermission($editPermissionObj);
        $em->persist($resource_user_change_status);
        $em->flush();

        $resource_ad_post_add = new Resource();
        $resource_ad_post_add->setName('Add ad for user');
        $resource_ad_post_add->setResource('ad_post_new_admin');
        $resource_ad_post_add->setIsMenu(0);
        $resource_ad_post_add->setDisplayInTree(1);
        $resource_ad_post_add->setIconClass('fi-plus');
        $resource_ad_post_add->setParent($resource_user);
        $resource_ad_post_add->setPermission($createPermissionObj);
        $em->persist($resource_ad_post_add);
        $em->flush();

        $resource_ad_post_from_cat = new Resource();
        $resource_ad_post_from_cat->setName('Add ad by category');
        $resource_ad_post_from_cat->setResource('ad_post_new_from_category_admin');
        $resource_ad_post_from_cat->setIsMenu(0);
        $resource_ad_post_from_cat->setDisplayInTree(0);
        $resource_ad_post_from_cat->setParent($resource_ad_post_add);
        $resource_ad_post_from_cat->setPermission($createPermissionObj);
        $em->persist($resource_ad_post_from_cat);
        $em->flush();

        $resource_ad_post_create = new Resource();
        $resource_ad_post_create->setName('Ad create');
        $resource_ad_post_create->setResource('ad_post_create_admin');
        $resource_ad_post_create->setIsMenu(0);
        $resource_ad_post_create->setDisplayInTree(0);
        $resource_ad_post_create->setParent($resource_ad_post_add);
        $resource_ad_post_create->setPermission($createPermissionObj);
        $em->persist($resource_ad_post_create);
        $em->flush();

        $resource_user_show_cart_admin = new Resource();
        $resource_user_show_cart_admin->setName('Show cart');
        $resource_user_show_cart_admin->setResource('show_cart_admin');
        $resource_user_show_cart_admin->setIsMenu(0);
        $resource_user_show_cart_admin->setDisplayInTree(1);
        $resource_user_show_cart_admin->setIconClass('fi-social-dropbox');
        $resource_user_show_cart_admin->setParent($resource_user);
        $resource_user_show_cart_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_show_cart_admin);
        $em->flush();

        $resource_user_process_payment_admin = new Resource();
        $resource_user_process_payment_admin->setName('Cart process payment');
        $resource_user_process_payment_admin->setResource('process_payment_admin');
        $resource_user_process_payment_admin->setIsMenu(0);
        $resource_user_process_payment_admin->setDisplayInTree(0);
        $resource_user_process_payment_admin->setParent($resource_user_show_cart_admin);
        $resource_user_process_payment_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_process_payment_admin);
        $em->flush();

        $resource_user_remove_cart_item_admin = new Resource();
        $resource_user_remove_cart_item_admin->setName('Cart remove item');
        $resource_user_remove_cart_item_admin->setResource('remove_cart_item_admin');
        $resource_user_remove_cart_item_admin->setIsMenu(0);
        $resource_user_remove_cart_item_admin->setDisplayInTree(0);
        $resource_user_remove_cart_item_admin->setParent($resource_user_show_cart_admin);
        $resource_user_remove_cart_item_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_remove_cart_item_admin);
        $em->flush();

        $resource_user_checkout_payment_success_admin = new Resource();
        $resource_user_checkout_payment_success_admin->setName('Cart payment success');
        $resource_user_checkout_payment_success_admin->setResource('checkout_payment_success_admin');
        $resource_user_checkout_payment_success_admin->setIsMenu(0);
        $resource_user_checkout_payment_success_admin->setDisplayInTree(0);
        $resource_user_checkout_payment_success_admin->setParent($resource_user_show_cart_admin);
        $resource_user_checkout_payment_success_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_checkout_payment_success_admin);
        $em->flush();

        $resource_user_checkout_payment_failure_admin = new Resource();
        $resource_user_checkout_payment_failure_admin->setName('Cart payment failure');
        $resource_user_checkout_payment_failure_admin->setResource('checkout_payment_failure_admin');
        $resource_user_checkout_payment_failure_admin->setIsMenu(0);
        $resource_user_checkout_payment_failure_admin->setDisplayInTree(0);
        $resource_user_checkout_payment_failure_admin->setParent($resource_user_show_cart_admin);
        $resource_user_checkout_payment_failure_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_checkout_payment_failure_admin);
        $em->flush();

        $resource_user_cybersource_checkout_admin = new Resource();
        $resource_user_cybersource_checkout_admin->setName('Cart cybersource payment');
        $resource_user_cybersource_checkout_admin->setResource('cybersource_checkout_admin');
        $resource_user_cybersource_checkout_admin->setIsMenu(0);
        $resource_user_cybersource_checkout_admin->setDisplayInTree(0);
        $resource_user_cybersource_checkout_admin->setParent($resource_user_show_cart_admin);
        $resource_user_cybersource_checkout_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_cybersource_checkout_admin);
        $em->flush();


        $resource_user_cybersource_delete_token_admin = new Resource();
        $resource_user_cybersource_delete_token_admin->setName('Cart delete token');
        $resource_user_cybersource_delete_token_admin->setResource('cybersource_delete_token_admin');
        $resource_user_cybersource_delete_token_admin->setIsMenu(0);
        $resource_user_cybersource_delete_token_admin->setDisplayInTree(0);
        $resource_user_cybersource_delete_token_admin->setParent($resource_user_show_cart_admin);
        $resource_user_cybersource_delete_token_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_cybersource_delete_token_admin);
        $em->flush();

        $resource_user_paypal_checkout_admin = new Resource();
        $resource_user_paypal_checkout_admin->setName('Cart paypal payment');
        $resource_user_paypal_checkout_admin->setResource('paypal_checkout_admin');
        $resource_user_paypal_checkout_admin->setIsMenu(0);
        $resource_user_paypal_checkout_admin->setDisplayInTree(0);
        $resource_user_paypal_checkout_admin->setParent($resource_user_show_cart_admin);
        $resource_user_paypal_checkout_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_paypal_checkout_admin);
        $em->flush();

        $resource_user_paypal_process_payment_admin = new Resource();
        $resource_user_paypal_process_payment_admin->setName('Cart paypal process');
        $resource_user_paypal_process_payment_admin->setResource('paypal_process_payment_admin');
        $resource_user_paypal_process_payment_admin->setIsMenu(0);
        $resource_user_paypal_process_payment_admin->setDisplayInTree(0);
        $resource_user_paypal_process_payment_admin->setParent($resource_user_show_cart_admin);
        $resource_user_paypal_process_payment_admin->setPermission($viewPermissionObj);
        $em->persist($resource_user_paypal_process_payment_admin);
        $em->flush();

        $resource_review_user = new Resource();
        $resource_review_user->setName('User review');
        $resource_review_user->setIsMenu(0);
        $resource_review_user->setDisplayInTree(1);
        $resource_review_user->setIconClass('fi-torso');
        $resource_review_user->setResourceGroup('user_reviews_list_admin');
        $resource_review_user->setParent($resource_user);
        $resource_review_user->setPermission($viewPermissionObj);
        $em->persist($resource_review_user);
        $em->flush();

        $resource_review_user = new Resource();
        $resource_review_user->setName('User review left for others');
        $resource_review_user->setIsMenu(0);
        $resource_review_user->setDisplayInTree(1);
        $resource_review_user->setIconClass('fi-torso');
        $resource_review_user->setResourceGroup('user_reviews_list_left_for_other_admin');
        $resource_review_user->setParent($resource_user);
        $resource_review_user->setPermission($viewPermissionObj);
        $em->persist($resource_review_user);
        $em->flush();

        $resource_user_review_list = new Resource();
        $resource_user_review_list->setName('User review list');
        $resource_user_review_list->setResource('user_reviews_list_admin');
        $resource_user_review_list->setIsMenu(0);
        $resource_user_review_list->setDisplayInTree(1);
        $resource_user_review_list->setIconClass('fi-list');
        $resource_user_review_list->setParent($resource_review_user);
        $resource_user_review_list->setPermission($viewPermissionObj);
        $em->persist($resource_user_review_list);
        $em->flush();

        $resource_user_review_edit = new Resource();
        $resource_user_review_edit->setName('User review edit');
        $resource_user_review_edit->setResource('user_review_edit_ajax_admin');
        $resource_user_review_edit->setIsMenu(0);
        $resource_user_review_edit->setDisplayInTree(1);
        $resource_user_review_edit->setParent($resource_review_user);
        $resource_user_review_edit->setPermission($editPermissionObj);
        $em->persist($resource_user_review_edit);
        $em->flush();

        $resource_user_review_delete = new Resource();
        $resource_user_review_delete->setName('User review delete');
        $resource_user_review_delete->setResource('user_review_delete_admin');
        $resource_user_review_delete->setIsMenu(0);
        $resource_user_review_delete->setDisplayInTree(1);
        $resource_user_review_delete->setParent($resource_review_user);
        $resource_user_review_delete->setPermission($deletePermissionObj);
        $em->persist($resource_user_review_delete);
        $em->flush();

        $resource_user_role = new Resource();
        $resource_user_role->setName('Role');
        $resource_user_role->setIsMenu(1);
        $resource_user_role->setDisplayInTree(1);
        $resource_user_role->setIconClass('fi-page-edit');
        $resource_user_role->setResourceGroup('role');
        $resource_user_role->setParent($resource_user);
        $resource_user_role->setPermission($viewPermissionObj);
        $em->persist($resource_user_role);
        $em->flush();

        $resource_user_role_list = new Resource();
        $resource_user_role_list->setName('Role list');
        $resource_user_role_list->setResource('role');
        $resource_user_role_list->setIsMenu(1);
        $resource_user_role_list->setDisplayInTree(1);
        $resource_user_role_list->setIconClass('fi-list');
        $resource_user_role_list->setParent($resource_user_role);
        $resource_user_role_list->setPermission($viewPermissionObj);
        $em->persist($resource_user_role_list);
        $em->flush();

        $resource_user_role_add = new Resource();
        $resource_user_role_add->setName('Role add');
        $resource_user_role_add->setResource('role_new');
        $resource_user_role_add->setIsMenu(1);
        $resource_user_role_add->setDisplayInTree(1);
        $resource_user_role_add->setIconClass('fi-plus');
        $resource_user_role_add->setParent($resource_user_role);
        $resource_user_role_add->setPermission($createPermissionObj);
        $em->persist($resource_user_role_add);
        $em->flush();

        $resource_user_role_create = new Resource();
        $resource_user_role_create->setName('Role create');
        $resource_user_role_create->setResource('role_create');
        $resource_user_role_create->setIsMenu(0);
        $resource_user_role_create->setDisplayInTree(0);
        $resource_user_role_create->setParent($resource_user_role_add);
        $resource_user_role_create->setPermission($createPermissionObj);
        $em->persist($resource_user_role_create);
        $em->flush();

        $resource_user_role_edit = new Resource();
        $resource_user_role_edit->setName('Role edit');
        $resource_user_role_edit->setResource('role_edit');
        $resource_user_role_edit->setIsMenu(0);
        $resource_user_role_edit->setDisplayInTree(1);
        $resource_user_role_edit->setParent($resource_user_role);
        $resource_user_role_edit->setPermission($editPermissionObj);
        $em->persist($resource_user_role_edit);
        $em->flush();

        $resource_user_role_update = new Resource();
        $resource_user_role_update->setName('Role update');
        $resource_user_role_update->setResource('role_update');
        $resource_user_role_update->setIsMenu(0);
        $resource_user_role_update->setDisplayInTree(0);
        $resource_user_role_update->setParent($resource_user_role_edit);
        $resource_user_role_update->setPermission($editPermissionObj);
        $em->persist($resource_user_role_update);
        $em->flush();

        $resource_user_role_delete = new Resource();
        $resource_user_role_delete->setName('Role delete');
        $resource_user_role_delete->setResource('role_delete');
        $resource_user_role_delete->setIsMenu(0);
        $resource_user_role_delete->setDisplayInTree(1);
        $resource_user_role_delete->setParent($resource_user_role);
        $resource_user_role_delete->setPermission($deletePermissionObj);
        $em->persist($resource_user_role_delete);
        $em->flush();

        $resource_user_role_permission = new Resource();
        $resource_user_role_permission->setName('Role permission');
        $resource_user_role_permission->setResource('roleresourcepermission_edit');
        $resource_user_role_permission->setIsMenu(0);
        $resource_user_role_permission->setDisplayInTree(1);
        $resource_user_role_permission->setParent($resource_user_role);
        $resource_user_role_permission->setPermission($deletePermissionObj);
        $em->persist($resource_user_role_permission);
        $em->flush();

        $resource_permission = new Resource();
        $resource_permission->setName('Permission');
        $resource_permission->setIsMenu(0);
        $resource_permission->setDisplayInTree(0);
        $resource_permission->setIconClass('fa-pencil');
        $resource_permission->setResourceGroup('permission');
        $resource_permission->setParent($resource_user);
        $resource_permission->setPermission($viewPermissionObj);
        $em->persist($resource_permission);
        $em->flush();

        $resource_permission_list = new Resource();
        $resource_permission_list->setName('Permission list');
        $resource_permission_list->setResource('permission');
        $resource_permission_list->setIsMenu(0);
        $resource_permission_list->setDisplayInTree(0);
        $resource_permission_list->setParent($resource_permission);
        $resource_permission_list->setPermission($viewPermissionObj);
        $em->persist($resource_permission_list);
        $em->flush();

        $resource_permission_add = new Resource();
        $resource_permission_add->setName('Permission add');
        $resource_permission_add->setResource('permission_new');
        $resource_permission_add->setIsMenu(0);
        $resource_permission_add->setDisplayInTree(0);
        $resource_permission_add->setParent($resource_permission);
        $resource_permission_add->setPermission($createPermissionObj);
        $em->persist($resource_permission_add);
        $em->flush();

        $resource_permission_create = new Resource();
        $resource_permission_create->setName('Permission create');
        $resource_permission_create->setResource('permission_create');
        $resource_permission_create->setIsMenu(0);
        $resource_permission_create->setDisplayInTree(0);
        $resource_permission_create->setParent($resource_permission_add);
        $resource_permission_create->setPermission($createPermissionObj);
        $em->persist($resource_permission_create);
        $em->flush();

        $resource_permission_edit = new Resource();
        $resource_permission_edit->setName('Permission edit');
        $resource_permission_edit->setResource('permission_edit');
        $resource_permission_edit->setIsMenu(0);
        $resource_permission_edit->setDisplayInTree(0);
        $resource_permission_edit->setParent($resource_permission);
        $resource_permission_edit->setPermission($editPermissionObj);
        $em->persist($resource_permission_edit);
        $em->flush();

        $resource_permission_update = new Resource();
        $resource_permission_update->setName('Permission update');
        $resource_permission_update->setResource('permission_update');
        $resource_permission_update->setIsMenu(0);
        $resource_permission_update->setDisplayInTree(0);
        $resource_permission_update->setParent($resource_permission_edit);
        $resource_permission_update->setPermission($editPermissionObj);
        $em->persist($resource_permission_update);
        $em->flush();

        $resource_permission_delete = new Resource();
        $resource_permission_delete->setName('Permission delete');
        $resource_permission_delete->setResource('permission_delete');
        $resource_permission_delete->setIsMenu(0);
        $resource_permission_delete->setDisplayInTree(0);
        $resource_permission_delete->setParent($resource_permission);
        $resource_permission_delete->setPermission($deletePermissionObj);
        $em->persist($resource_permission_delete);
        $em->flush();

        $resource_user_config_rule = new Resource();
        $resource_user_config_rule->setName('User Configuration Rules');
        $resource_user_config_rule->setIsMenu(0);
        $resource_user_config_rule->setDisplayInTree(1);
        $resource_user_config_rule->setIconClass('fi-wrench');
        $resource_user_config_rule->setResourceGroup('user_config_rule_admin');
        $resource_user_config_rule->setParent($resource_user);
        $resource_user_config_rule->setPermission($viewPermissionObj);
        $em->persist($resource_user_config_rule);
        $em->flush();

        $resource_user_config_rule_list = new Resource();
        $resource_user_config_rule_list->setName('User Configuration Rules List');
        $resource_user_config_rule_list->setResource('user_config_rule_admin');
        $resource_user_config_rule_list->setIsMenu(0);
        $resource_user_config_rule_list->setDisplayInTree(1);
        $resource_user_config_rule_list->setIconClass('fi-list');
        $resource_user_config_rule_list->setParent($resource_user_config_rule);
        $resource_user_config_rule_list->setPermission($viewPermissionObj);
        $em->persist($resource_user_config_rule_list);
        $em->flush();

        $resource_user_config_rule_add = new Resource();
        $resource_user_config_rule_add->setName('User Configuration Rules Add');
        $resource_user_config_rule_add->setResource('user_config_rule_new_admin');
        $resource_user_config_rule_add->setIsMenu(0);
        $resource_user_config_rule_add->setDisplayInTree(1);
        $resource_user_config_rule_add->setIconClass('fi-plus');
        $resource_user_config_rule_add->setParent($resource_user_config_rule);
        $resource_user_config_rule_add->setPermission($createPermissionObj);
        $em->persist($resource_user_config_rule_add);
        $em->flush();

        $resource_user_config_rule_create = new Resource();
        $resource_user_config_rule_create->setName('User Configuration Rules Create');
        $resource_user_config_rule_create->setResource('user_config_rule_create_admin');
        $resource_user_config_rule_create->setIsMenu(0);
        $resource_user_config_rule_create->setDisplayInTree(0);
        $resource_user_config_rule_create->setParent($resource_user_config_rule_add);
        $resource_user_config_rule_create->setPermission($createPermissionObj);
        $em->persist($resource_user_config_rule_create);
        $em->flush();

        $resource_user_config_rule_edit = new Resource();
        $resource_user_config_rule_edit->setName('User Configuration Rules Edit');
        $resource_user_config_rule_edit->setResource('user_config_rule_edit_admin');
        $resource_user_config_rule_edit->setIsMenu(0);
        $resource_user_config_rule_edit->setDisplayInTree(1);
        $resource_user_config_rule_edit->setParent($resource_user_config_rule);
        $resource_user_config_rule_edit->setPermission($editPermissionObj);
        $em->persist($resource_user_config_rule_edit);
        $em->flush();

        $resource_user_config_rule_update = new Resource();
        $resource_user_config_rule_update->setName('User Configuration Rules Update');
        $resource_user_config_rule_update->setResource('user_config_rule_update_admin');
        $resource_user_config_rule_update->setIsMenu(0);
        $resource_user_config_rule_update->setDisplayInTree(0);
        $resource_user_config_rule_update->setParent($resource_user_config_rule_edit);
        $resource_user_config_rule_update->setPermission($editPermissionObj);
        $em->persist($resource_user_config_rule_update);
        $em->flush();

        $resource_user_config_rule_delete = new Resource();
        $resource_user_config_rule_delete->setName('User Configuration Rules Delete');
        $resource_user_config_rule_delete->setResource('user_config_rule_delete_admin');
        $resource_user_config_rule_delete->setIsMenu(0);
        $resource_user_config_rule_delete->setDisplayInTree(1);
        $resource_user_config_rule_delete->setParent($resource_user_config_rule);
        $resource_user_config_rule_delete->setPermission($deletePermissionObj);
        $em->persist($resource_user_config_rule_delete);

        $resource_user_config_rule_add_edit = new Resource();
        $resource_user_config_rule_add_edit->setName('User Configuration Rules add edit');
        $resource_user_config_rule_add_edit->setResource('user_config_rule_add_edit_admin');
        $resource_user_config_rule_add_edit->setIsMenu(0);
        $resource_user_config_rule_add_edit->setDisplayInTree(1);
        $resource_user_config_rule_add_edit->setParent($resource_user_config_rule);
        $resource_user_config_rule_add_edit->setPermission($editPermissionObj);
        $em->persist($resource_user_config_rule_add_edit);
        $em->flush();

        $resource_user_config_rule_add_edit_save = new Resource();
        $resource_user_config_rule_add_edit_save->setName('User Configuration Rules add edit save');
        $resource_user_config_rule_add_edit_save->setResource('user_config_rule_add_edit_save_admin');
        $resource_user_config_rule_add_edit_save->setIsMenu(0);
        $resource_user_config_rule_add_edit_save->setDisplayInTree(0);
        $resource_user_config_rule_add_edit_save->setParent($resource_user_config_rule_add_edit);
        $resource_user_config_rule_add_edit_save->setPermission($editPermissionObj);
        $em->persist($resource_user_config_rule_add_edit_save);
        $em->flush();

        $resource_testimonials = new Resource();
        $resource_testimonials->setName('Testimonials');
        $resource_testimonials->setIsMenu(1);
        $resource_testimonials->setDisplayInTree(1);
        $resource_testimonials->setIconClass('fi-torso');
        $resource_testimonials->setResourceGroup('testimonials_admin');
        $resource_testimonials->setParent($userManegment);
        $resource_testimonials->setPermission($viewPermissionObj);
        $em->persist($resource_testimonials);
        $em->flush();

        $resource_testimonials_list = new Resource();
        $resource_testimonials_list->setName('Testimonials list');
        $resource_testimonials_list->setResource('testimonials_admin');
        $resource_testimonials_list->setIsMenu(1);
        $resource_testimonials_list->setDisplayInTree(1);
        $resource_testimonials_list->setIconClass('fi-list');
        $resource_testimonials_list->setParent($resource_testimonials);
        $resource_testimonials_list->setPermission($viewPermissionObj);
        $em->persist($resource_testimonials_list);
        $em->flush();

        $resource_testimonials_edit = new Resource();
        $resource_testimonials_edit->setName('Testimonials edit');
        $resource_testimonials_edit->setResource('testimonials_edit_admin');
        $resource_testimonials_edit->setIsMenu(0);
        $resource_testimonials_edit->setDisplayInTree(1);
        $resource_testimonials_edit->setParent($resource_testimonials);
        $resource_testimonials_edit->setPermission($editPermissionObj);
        $em->persist($resource_testimonials_edit);
        $em->flush();

        $resource_testimonials_update = new Resource();
        $resource_testimonials_update->setName('Testimonials update');
        $resource_testimonials_update->setResource('testimonials_update_admin');
        $resource_testimonials_update->setIsMenu(0);
        $resource_testimonials_update->setDisplayInTree(0);
        $resource_testimonials_update->setParent($resource_testimonials_edit);
        $resource_testimonials_update->setPermission($editPermissionObj);
        $em->persist($resource_testimonials_update);
        $em->flush();

        $resource_testimonials_delete = new Resource();
        $resource_testimonials_delete->setName('Testimonials delete');
        $resource_testimonials_delete->setResource('testimonials_delete_admin');
        $resource_testimonials_delete->setIsMenu(0);
        $resource_testimonials_delete->setDisplayInTree(1);
        $resource_testimonials_delete->setParent($resource_testimonials);
        $resource_testimonials_delete->setPermission($deletePermissionObj);
        $em->persist($resource_testimonials_delete);
        $em->flush();

        $resource_testimonials_ajax_change_status = new Resource();
        $resource_testimonials_ajax_change_status->setName('Testimonials ajax change status');
        $resource_testimonials_ajax_change_status->setResource('ajax_testimonials_change_status');
        $resource_testimonials_ajax_change_status->setIsMenu(0);
        $resource_testimonials_ajax_change_status->setDisplayInTree(1);
        $resource_testimonials_ajax_change_status->setParent($resource_testimonials);
        $resource_testimonials_ajax_change_status->setPermission($editPermissionObj);
        $em->persist($resource_testimonials_ajax_change_status);
        $em->flush();

        $adManegment = new Resource();
        $adManegment->setName('Ad Management');
        $adManegment->setIsMenu(1);
        $adManegment->setDisplayInTree(1);
        $adManegment->setIconClass('fi-monitor');
        $adManegment->setResourceGroup('ad_admin');
        $adManegment->setParent($resource_root);
        $adManegment->setPermission($viewPermissionObj);
        $em->persist($adManegment);
        $em->flush();

        $resource_ad = new Resource();
        $resource_ad->setName('Ad');
        $resource_ad->setIsMenu(1);
        $resource_ad->setDisplayInTree(1);
        $resource_ad->setIconClass('fi-monitor');
        $resource_ad->setResourceGroup('ad_admin');
        $resource_ad->setParent($adManegment);
        $resource_ad->setPermission($viewPermissionObj);
        $em->persist($resource_ad);
        $em->flush();

        $resource_ad_list = new Resource();
        $resource_ad_list->setName('Ad list');
        $resource_ad_list->setResource('ad_admin');
        $resource_ad_list->setIsMenu(1);
        $resource_ad_list->setDisplayInTree(1);
        $resource_ad_list->setIconClass('fi-list');
        $resource_ad_list->setParent($resource_ad);
        $resource_ad_list->setPermission($viewPermissionObj);
        $em->persist($resource_ad_list);
        $em->flush();

        $resource_ad_change_status = new Resource();
        $resource_ad_change_status->setName('Ad change status');
        $resource_ad_change_status->setResource('ad_change_status');
        $resource_ad_change_status->setIsMenu(0);
        $resource_ad_change_status->setDisplayInTree(1);
        $resource_ad_change_status->setParent($resource_ad);
        $resource_ad_change_status->setPermission($editPermissionObj);
        $em->persist($resource_ad_change_status);
        $em->flush();

        $resource_ad_post_search_user = new Resource();
        $resource_ad_post_search_user->setName('Ad add');
        $resource_ad_post_search_user->setResource('ad_post_search_user_admin');
        $resource_ad_post_search_user->setIsMenu(1);
        $resource_ad_post_search_user->setDisplayInTree(1);
        $resource_ad_post_search_user->setIconClass('fi-plus');
        $resource_ad_post_search_user->setParent($resource_ad);
        $resource_ad_post_search_user->setPermission($viewPermissionObj);
        $em->persist($resource_ad_post_search_user);
        $em->flush();

        $resource_ad_post_edit = new Resource();
        $resource_ad_post_edit->setName('Ad edit');
        $resource_ad_post_edit->setResource('ad_post_edit_admin');
        $resource_ad_post_edit->setIsMenu(0);
        $resource_ad_post_edit->setDisplayInTree(1);
        $resource_ad_post_edit->setParent($resource_ad);
        $resource_ad_post_edit->setPermission($editPermissionObj);
        $em->persist($resource_ad_post_edit);
        $em->flush();

        $resource_ad_post_update = new Resource();
        $resource_ad_post_update->setName('Ad update');
        $resource_ad_post_update->setResource('ad_post_update_admin');
        $resource_ad_post_update->setIsMenu(0);
        $resource_ad_post_update->setDisplayInTree(0);
        $resource_ad_post_update->setParent($resource_ad_post_edit);
        $resource_ad_post_update->setPermission($editPermissionObj);
        $em->persist($resource_ad_post_update);
        $em->flush();

        $resource_ad_detail = new Resource();
        $resource_ad_detail->setName('Ad detail');
        $resource_ad_detail->setResource('ad_detail_admin');
        $resource_ad_detail->setIsMenu(0);
        $resource_ad_detail->setDisplayInTree(1);
        $resource_ad_detail->setParent($resource_ad);
        $resource_ad_detail->setPermission($viewPermissionObj);
        $em->persist($resource_ad_detail);
        $em->flush();


        $resource_ad_ajax_ad_image_save = new Resource();
        $resource_ad_ajax_ad_image_save->setName('Ad ajax image save');
        $resource_ad_ajax_ad_image_save->setResource('ajax_ad_image_save_admin');
        $resource_ad_ajax_ad_image_save->setIsMenu(0);
        $resource_ad_ajax_ad_image_save->setDisplayInTree(1);
        $resource_ad_ajax_ad_image_save->setParent($resource_ad);
        $resource_ad_ajax_ad_image_save->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_ad_image_save);
        $em->flush();

        $resource_ad_ajax_print_dates_list = new Resource();
        $resource_ad_ajax_print_dates_list->setName('Ad ajax list print insert dates');
        $resource_ad_ajax_print_dates_list->setResource('ajax_print_dates_list_admin');
        $resource_ad_ajax_print_dates_list->setIsMenu(0);
        $resource_ad_ajax_print_dates_list->setDisplayInTree(1);
        $resource_ad_ajax_print_dates_list->setParent($resource_ad);
        $resource_ad_ajax_print_dates_list->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_print_dates_list);
        $em->flush();

        $resource_ad_ajax_get_ad_images = new Resource();
        $resource_ad_ajax_get_ad_images->setName('Ad ajax get image');
        $resource_ad_ajax_get_ad_images->setResource('ajax_get_ad_images_admin');
        $resource_ad_ajax_get_ad_images->setIsMenu(0);
        $resource_ad_ajax_get_ad_images->setDisplayInTree(1);
        $resource_ad_ajax_get_ad_images->setParent($resource_ad);
        $resource_ad_ajax_get_ad_images->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_get_ad_images);
        $em->flush();

        $resource_ad_ajax_delete_ad_images = new Resource();
        $resource_ad_ajax_delete_ad_images->setName('Ad ajax delete image');
        $resource_ad_ajax_delete_ad_images->setResource('ajax_delete_ad_images_admin');
        $resource_ad_ajax_delete_ad_images->setIsMenu(0);
        $resource_ad_ajax_delete_ad_images->setDisplayInTree(1);
        $resource_ad_ajax_delete_ad_images->setParent($resource_ad);
        $resource_ad_ajax_delete_ad_images->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_delete_ad_images);
        $em->flush();

        $resource_ad_ajax_get_big_ad_image = new Resource();
        $resource_ad_ajax_get_big_ad_image->setName('Ad ajax get crop image');
        $resource_ad_ajax_get_big_ad_image->setResource('ajax_get_big_ad_image_admin');
        $resource_ad_ajax_get_big_ad_image->setIsMenu(0);
        $resource_ad_ajax_get_big_ad_image->setDisplayInTree(1);
        $resource_ad_ajax_get_big_ad_image->setParent($resource_ad);
        $resource_ad_ajax_get_big_ad_image->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_get_big_ad_image);
        $em->flush();

        $resource_ad_ajax_crop_ad_image = new Resource();
        $resource_ad_ajax_crop_ad_image->setName('Ad ajax crop image_admin');
        $resource_ad_ajax_crop_ad_image->setResource('ajax_crop_ad_image');
        $resource_ad_ajax_crop_ad_image->setIsMenu(0);
        $resource_ad_ajax_crop_ad_image->setDisplayInTree(1);
        $resource_ad_ajax_crop_ad_image->setParent($resource_ad);
        $resource_ad_ajax_crop_ad_image->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_crop_ad_image);
        $em->flush();

        $resource_ad_ajax_change_ad_image_order = new Resource();
        $resource_ad_ajax_change_ad_image_order->setName('Ad ajax change image order');
        $resource_ad_ajax_change_ad_image_order->setResource('ajax_change_ad_image_order');
        $resource_ad_ajax_change_ad_image_order->setIsMenu(0);
        $resource_ad_ajax_change_ad_image_order->setDisplayInTree(1);
        $resource_ad_ajax_change_ad_image_order->setParent($resource_ad);
        $resource_ad_ajax_change_ad_image_order->setPermission($editPermissionObj);
        $em->persist($resource_ad_ajax_change_ad_image_order);
        $em->flush();

        $resource_ad_package_purchase_admin = new Resource();
        $resource_ad_package_purchase_admin->setName('Assign package to ad');
        $resource_ad_package_purchase_admin->setResource('ad_package_purchase_admin');
        $resource_ad_package_purchase_admin->setIsMenu(0);
        $resource_ad_package_purchase_admin->setDisplayInTree(1);
        $resource_ad_package_purchase_admin->setIconClass('fi-social-dropbox');
        $resource_ad_package_purchase_admin->setParent($resource_ad);
        $resource_ad_package_purchase_admin->setPermission($viewPermissionObj);
        $em->persist($resource_ad_package_purchase_admin);
        $em->flush();



        $resource_archive_ad = new Resource();
        $resource_archive_ad->setName('Archive ad');
        $resource_archive_ad->setIsMenu(1);
        $resource_archive_ad->setDisplayInTree(1);
        $resource_archive_ad->setIconClass('fi-archive');
        $resource_archive_ad->setResourceGroup('archive_ad_admin');
        $resource_archive_ad->setParent($adManegment);
        $resource_archive_ad->setPermission($viewPermissionObj);
        $em->persist($resource_archive_ad);
        $em->flush();

        $resource_archive_ad_list = new Resource();
        $resource_archive_ad_list->setName('Archive ad list');
        $resource_archive_ad_list->setResource('archive_ad_admin');
        $resource_archive_ad_list->setIsMenu(1);
        $resource_archive_ad_list->setDisplayInTree(1);
        $resource_archive_ad_list->setIconClass('fi-list');
        $resource_archive_ad_list->setParent($resource_archive_ad);
        $resource_archive_ad_list->setPermission($viewPermissionObj);
        $em->persist($resource_archive_ad_list);
        $em->flush();

        $resource_archive_ad_detail = new Resource();
        $resource_archive_ad_detail->setName('Archive ad detail');
        $resource_archive_ad_detail->setResource('archive_ad_detail_admin');
        $resource_archive_ad_detail->setIsMenu(0);
        $resource_archive_ad_detail->setDisplayInTree(1);
        $resource_archive_ad_detail->setParent($resource_archive_ad);
        $resource_archive_ad_detail->setPermission($viewPermissionObj);
        $em->persist($resource_archive_ad_detail);
        $em->flush();

        $businessManegment = new Resource();
        $businessManegment->setName('Business Rules & Settings');
        $businessManegment->setIsMenu(1);
        $businessManegment->setDisplayInTree(1);
        $businessManegment->setIconClass('fi-torso-business');
        $businessManegment->setResourceGroup('fa_admin_homepage');
        $businessManegment->setParent($resource_root);
        $businessManegment->setPermission($viewPermissionObj);
        $em->persist($businessManegment);
        $em->flush();

        $resource_package = new Resource();
        $resource_package->setName('Package');
        $resource_package->setIsMenu(1);
        $resource_package->setDisplayInTree(1);
        $resource_package->setIconClass('fi-social-dropbox');
        $resource_package->setResourceGroup('package_admin');
        $resource_package->setParent($businessManegment);
        $resource_package->setPermission($viewPermissionObj);
        $em->persist($resource_package);
        $em->flush();

        $resource_package_list = new Resource();
        $resource_package_list->setName('Package list');
        $resource_package_list->setResource('package_admin');
        $resource_package_list->setIsMenu(1);
        $resource_package_list->setDisplayInTree(1);
        $resource_package_list->setIconClass('fi-list');
        $resource_package_list->setParent($resource_package);
        $resource_package_list->setPermission($viewPermissionObj);
        $em->persist($resource_package_list);
        $em->flush();

        $resource_package_list_nested_ajax = new Resource();
        $resource_package_list_nested_ajax->setName('Package list nested  ajax');
        $resource_package_list_nested_ajax->setResource('category_ajax_get_nested_node_json');
        $resource_package_list_nested_ajax->setIsMenu(0);
        $resource_package_list_nested_ajax->setDisplayInTree(0);
        $resource_package_list_nested_ajax->setParent($resource_package_list);
        $resource_package_list_nested_ajax->setPermission($viewPermissionObj);
        $em->persist($resource_package_list_nested_ajax);
        $em->flush();

        $resource_package_list_ajax = new Resource();
        $resource_package_list_ajax->setName('Package list ajax');
        $resource_package_list_ajax->setResource('category_ajax_get_node_json');
        $resource_package_list_ajax->setIsMenu(0);
        $resource_package_list_ajax->setDisplayInTree(0);
        $resource_package_list_ajax->setParent($resource_package_list);
        $resource_package_list_ajax->setPermission($viewPermissionObj);
        $em->persist($resource_package_list_ajax);
        $em->flush();

        $resource_package_add = new Resource();
        $resource_package_add->setName('Package add');
        $resource_package_add->setResource('package_new_admin');
        $resource_package_add->setIsMenu(1);
        $resource_package_add->setDisplayInTree(1);
        $resource_package_add->setIconClass('fi-plus');
        $resource_package_add->setParent($resource_package);
        $resource_package_add->setPermission($createPermissionObj);
        $em->persist($resource_package_add);
        $em->flush();

        $resource_package_create = new Resource();
        $resource_package_create->setName('Package create');
        $resource_package_create->setResource('package_create_admin');
        $resource_package_create->setIsMenu(0);
        $resource_package_create->setDisplayInTree(0);
        $resource_package_create->setParent($resource_package_add);
        $resource_package_create->setPermission($createPermissionObj);
        $em->persist($resource_package_create);
        $em->flush();

        $resource_package_edit = new Resource();
        $resource_package_edit->setName('Package edit');
        $resource_package_edit->setResource('package_edit_admin');
        $resource_package_edit->setIsMenu(0);
        $resource_package_edit->setDisplayInTree(1);
        $resource_package_edit->setParent($resource_package);
        $resource_package_edit->setPermission($editPermissionObj);
        $em->persist($resource_package_edit);
        $em->flush();

        $resource_package_update = new Resource();
        $resource_package_update->setName('Package update');
        $resource_package_update->setResource('package_update_admin');
        $resource_package_update->setIsMenu(0);
        $resource_package_update->setDisplayInTree(0);
        $resource_package_update->setParent($resource_package_edit);
        $resource_package_update->setPermission($editPermissionObj);
        $em->persist($resource_package_update);
        $em->flush();

        $resource_package_delete = new Resource();
        $resource_package_delete->setName('Package delete');
        $resource_package_delete->setResource('package_delete_admin');
        $resource_package_delete->setIsMenu(0);
        $resource_package_delete->setDisplayInTree(1);
        $resource_package_delete->setParent($resource_package);
        $resource_package_delete->setPermission($deletePermissionObj);
        $em->persist($resource_package_delete);
        $em->flush();

                $resource_package = new Resource();
        $resource_package->setName('Subscriptions');
        $resource_package->setIsMenu(1);
        $resource_package->setDisplayInTree(1);
        $resource_package->setIconClass('fi-social-dropbox');
        $resource_package->setResourceGroup('shop_package_admin');
        $resource_package->setParent($businessManegment);
        $resource_package->setPermission($viewPermissionObj);
        $em->persist($resource_package);
        $em->flush();

        $resource_package_list = new Resource();
        $resource_package_list->setName('Subscription list');
        $resource_package_list->setResource('shop_package_admin');
        $resource_package_list->setIsMenu(1);
        $resource_package_list->setDisplayInTree(1);
        $resource_package_list->setIconClass('fi-list');
        $resource_package_list->setParent($resource_package);
        $resource_package_list->setPermission($viewPermissionObj);
        $em->persist($resource_package_list);
        $em->flush();

        $resource_package_add = new Resource();
        $resource_package_add->setName('Subscription add');
        $resource_package_add->setResource('shop_package_new_admin');
        $resource_package_add->setIsMenu(1);
        $resource_package_add->setDisplayInTree(1);
        $resource_package_add->setIconClass('fi-plus');
        $resource_package_add->setParent($resource_package);
        $resource_package_add->setPermission($createPermissionObj);
        $em->persist($resource_package_add);
        $em->flush();

        $resource_package_create = new Resource();
        $resource_package_create->setName('Subscription create');
        $resource_package_create->setResource('shop_package_create_admin');
        $resource_package_create->setIsMenu(0);
        $resource_package_create->setDisplayInTree(0);
        $resource_package_create->setParent($resource_package_add);
        $resource_package_create->setPermission($createPermissionObj);
        $em->persist($resource_package_create);
        $em->flush();

        $resource_package_edit = new Resource();
        $resource_package_edit->setName('Subscription edit');
        $resource_package_edit->setResource('shop_package_edit_admin');
        $resource_package_edit->setIsMenu(0);
        $resource_package_edit->setDisplayInTree(1);
        $resource_package_edit->setParent($resource_package);
        $resource_package_edit->setPermission($editPermissionObj);
        $em->persist($resource_package_edit);
        $em->flush();

        $resource_package_update = new Resource();
        $resource_package_update->setName('Subscription update');
        $resource_package_update->setResource('shop_package_update_admin');
        $resource_package_update->setIsMenu(0);
        $resource_package_update->setDisplayInTree(0);
        $resource_package_update->setParent($resource_package_edit);
        $resource_package_update->setPermission($editPermissionObj);
        $em->persist($resource_package_update);
        $em->flush();

        $resource_package_delete = new Resource();
        $resource_package_delete->setName('Subscription delete');
        $resource_package_delete->setResource('shop_package_delete_admin');
        $resource_package_delete->setIsMenu(0);
        $resource_package_delete->setDisplayInTree(1);
        $resource_package_delete->setParent($resource_package);
        $resource_package_delete->setPermission($deletePermissionObj);
        $em->persist($resource_package_delete);
        $em->flush();

        $resource_upsell = new Resource();
        $resource_upsell->setName('Upsell');
        $resource_upsell->setIsMenu(1);
        $resource_upsell->setDisplayInTree(1);
        $resource_upsell->setIconClass('fi-arrow-up');
        $resource_upsell->setResourceGroup('upsell_admin');
        $resource_upsell->setParent($businessManegment);
        $resource_upsell->setPermission($viewPermissionObj);
        $em->persist($resource_upsell);
        $em->flush();

        $resource_upsell_list = new Resource();
        $resource_upsell_list->setName('Upsell list');
        $resource_upsell_list->setResource('upsell_admin');
        $resource_upsell_list->setIsMenu(1);
        $resource_upsell_list->setDisplayInTree(1);
        $resource_upsell_list->setIconClass('fi-list');
        $resource_upsell_list->setParent($resource_upsell);
        $resource_upsell_list->setPermission($viewPermissionObj);
        $em->persist($resource_upsell_list);
        $em->flush();

        $resource_upsell_add = new Resource();
        $resource_upsell_add->setName('Upsell add');
        $resource_upsell_add->setResource('upsell_new_admin');
        $resource_upsell_add->setIsMenu(1);
        $resource_upsell_add->setDisplayInTree(1);
        $resource_upsell_add->setIconClass('fi-plus');
        $resource_upsell_add->setParent($resource_upsell);
        $resource_upsell_add->setPermission($createPermissionObj);
        $em->persist($resource_upsell_add);
        $em->flush();

        $resource_upsell_create = new Resource();
        $resource_upsell_create->setName('Upsell create');
        $resource_upsell_create->setResource('upsell_create_admin');
        $resource_upsell_create->setIsMenu(0);
        $resource_upsell_create->setDisplayInTree(0);
        $resource_upsell_create->setParent($resource_upsell_add);
        $resource_upsell_create->setPermission($createPermissionObj);
        $em->persist($resource_upsell_create);
        $em->flush();

        $resource_upsell_edit = new Resource();
        $resource_upsell_edit->setName('Upsell edit');
        $resource_upsell_edit->setResource('upsell_edit_admin');
        $resource_upsell_edit->setIsMenu(0);
        $resource_upsell_edit->setDisplayInTree(1);
        $resource_upsell_edit->setParent($resource_upsell);
        $resource_upsell_edit->setPermission($editPermissionObj);
        $em->persist($resource_upsell_edit);
        $em->flush();

        $resource_upsell_update = new Resource();
        $resource_upsell_update->setName('Upsell update');
        $resource_upsell_update->setResource('upsell_update_admin');
        $resource_upsell_update->setIsMenu(0);
        $resource_upsell_update->setDisplayInTree(0);
        $resource_upsell_update->setParent($resource_upsell_edit);
        $resource_upsell_update->setPermission($editPermissionObj);
        $em->persist($resource_upsell_update);
        $em->flush();

        $resource_upsell_delete = new Resource();
        $resource_upsell_delete->setName('Upsell delete');
        $resource_upsell_delete->setResource('upsell_delete_admin');
        $resource_upsell_delete->setIsMenu(0);
        $resource_upsell_delete->setDisplayInTree(1);
        $resource_upsell_delete->setParent($resource_upsell);
        $resource_upsell_delete->setPermission($deletePermissionObj);
        $em->persist($resource_upsell_delete);
        $em->flush();

        $resource_upsell = new Resource();
        $resource_upsell->setName('Profile Upsell');
        $resource_upsell->setIsMenu(1);
        $resource_upsell->setDisplayInTree(1);
        $resource_upsell->setIconClass('fi-arrow-up');
        $resource_upsell->setResourceGroup('profile_upsell_admin');
        $resource_upsell->setParent($businessManegment);
        $resource_upsell->setPermission($viewPermissionObj);
        $em->persist($resource_upsell);
        $em->flush();

        $resource_upsell_list = new Resource();
        $resource_upsell_list->setName('Profile upsell list');
        $resource_upsell_list->setResource('profile_upsell_admin');
        $resource_upsell_list->setIsMenu(1);
        $resource_upsell_list->setDisplayInTree(1);
        $resource_upsell_list->setIconClass('fi-list');
        $resource_upsell_list->setParent($resource_upsell);
        $resource_upsell_list->setPermission($viewPermissionObj);
        $em->persist($resource_upsell_list);
        $em->flush();

        $resource_upsell_add = new Resource();
        $resource_upsell_add->setName('Profile upsell add');
        $resource_upsell_add->setResource('profile_upsell_new_admin');
        $resource_upsell_add->setIsMenu(1);
        $resource_upsell_add->setDisplayInTree(1);
        $resource_upsell_add->setIconClass('fi-plus');
        $resource_upsell_add->setParent($resource_upsell);
        $resource_upsell_add->setPermission($createPermissionObj);
        $em->persist($resource_upsell_add);
        $em->flush();

        $resource_upsell_create = new Resource();
        $resource_upsell_create->setName('Profile upsell create');
        $resource_upsell_create->setResource('profile_upsell_create_admin');
        $resource_upsell_create->setIsMenu(0);
        $resource_upsell_create->setDisplayInTree(0);
        $resource_upsell_create->setParent($resource_upsell_add);
        $resource_upsell_create->setPermission($createPermissionObj);
        $em->persist($resource_upsell_create);
        $em->flush();

        $resource_upsell_edit = new Resource();
        $resource_upsell_edit->setName('Profile upsell edit');
        $resource_upsell_edit->setResource('profile_upsell_edit_admin');
        $resource_upsell_edit->setIsMenu(0);
        $resource_upsell_edit->setDisplayInTree(1);
        $resource_upsell_edit->setParent($resource_upsell);
        $resource_upsell_edit->setPermission($editPermissionObj);
        $em->persist($resource_upsell_edit);
        $em->flush();

        $resource_upsell_update = new Resource();
        $resource_upsell_update->setName('Profile upsell update');
        $resource_upsell_update->setResource('profile_upsell_update_admin');
        $resource_upsell_update->setIsMenu(0);
        $resource_upsell_update->setDisplayInTree(0);
        $resource_upsell_update->setParent($resource_upsell_edit);
        $resource_upsell_update->setPermission($editPermissionObj);
        $em->persist($resource_upsell_update);
        $em->flush();

        $resource_upsell_delete = new Resource();
        $resource_upsell_delete->setName('Profile upsell delete');
        $resource_upsell_delete->setResource('profile_upsell_delete_admin');
        $resource_upsell_delete->setIsMenu(0);
        $resource_upsell_delete->setDisplayInTree(1);
        $resource_upsell_delete->setParent($resource_upsell);
        $resource_upsell_delete->setPermission($deletePermissionObj);
        $em->persist($resource_upsell_delete);
        $em->flush();

        $siteManegment = new Resource();
        $siteManegment->setName('Configuration Rules');
        $siteManegment->setIsMenu(1);
        $siteManegment->setDisplayInTree(1);
        $siteManegment->setIconClass('fi-widget');
        $siteManegment->setResourceGroup('config_rule_admin');
        $siteManegment->setParent($resource_root);
        $siteManegment->setPermission($viewPermissionObj);
        $em->persist($siteManegment);
        $em->flush();

        $resource_config_rule = new Resource();
        $resource_config_rule->setName('Configuration Rules & Settings');
        $resource_config_rule->setIsMenu(1);
        $resource_config_rule->setDisplayInTree(1);
        $resource_config_rule->setIconClass('fi-wrench');
        $resource_config_rule->setResourceGroup('config_rule_admin');
        $resource_config_rule->setParent($siteManegment);
        $resource_config_rule->setPermission($viewPermissionObj);
        $em->persist($resource_config_rule);
        $em->flush();

        $resource_config_rule_list = new Resource();
        $resource_config_rule_list->setName('Configuration Rules List');
        $resource_config_rule_list->setResource('config_rule_admin');
        $resource_config_rule_list->setIsMenu(1);
        $resource_config_rule_list->setDisplayInTree(1);
        $resource_config_rule_list->setIconClass('fi-list');
        $resource_config_rule_list->setParent($resource_config_rule);
        $resource_config_rule_list->setPermission($viewPermissionObj);
        $em->persist($resource_config_rule_list);
        $em->flush();

        $resource_config_rule_add = new Resource();
        $resource_config_rule_add->setName('Configuration Rules Add');
        $resource_config_rule_add->setResource('config_rule_new_admin');
        $resource_config_rule_add->setIsMenu(1);
        $resource_config_rule_add->setDisplayInTree(1);
        $resource_config_rule_add->setIconClass('fi-plus');
        $resource_config_rule_add->setParent($resource_config_rule);
        $resource_config_rule_add->setPermission($createPermissionObj);
        $em->persist($resource_config_rule_add);
        $em->flush();

        $resource_config_rule_create = new Resource();
        $resource_config_rule_create->setName('Configuration Rules Create');
        $resource_config_rule_create->setResource('config_rule_create_admin');
        $resource_config_rule_create->setIsMenu(0);
        $resource_config_rule_create->setDisplayInTree(0);
        $resource_config_rule_create->setParent($resource_config_rule_add);
        $resource_config_rule_create->setPermission($createPermissionObj);
        $em->persist($resource_config_rule_create);
        $em->flush();

        $resource_config_rule_edit = new Resource();
        $resource_config_rule_edit->setName('Configuration Rules Edit');
        $resource_config_rule_edit->setResource('config_rule_edit_admin');
        $resource_config_rule_edit->setIsMenu(0);
        $resource_config_rule_edit->setDisplayInTree(1);
        $resource_config_rule_edit->setParent($resource_config_rule);
        $resource_config_rule_edit->setPermission($editPermissionObj);
        $em->persist($resource_config_rule_edit);
        $em->flush();

        $resource_config_rule_update = new Resource();
        $resource_config_rule_update->setName('Configuration Rules Update');
        $resource_config_rule_update->setResource('config_rule_update_admin');
        $resource_config_rule_update->setIsMenu(0);
        $resource_config_rule_update->setDisplayInTree(0);
        $resource_config_rule_update->setParent($resource_config_rule_edit);
        $resource_config_rule_update->setPermission($editPermissionObj);
        $em->persist($resource_config_rule_update);
        $em->flush();

        $resource_config_rule_delete = new Resource();
        $resource_config_rule_delete->setName('Configuration Rules Delete');
        $resource_config_rule_delete->setResource('config_rule_delete_admin');
        $resource_config_rule_delete->setIsMenu(0);
        $resource_config_rule_delete->setDisplayInTree(1);
        $resource_config_rule_delete->setParent($resource_config_rule);
        $resource_config_rule_delete->setPermission($deletePermissionObj);
        $em->persist($resource_config_rule_delete);

        $resource_category = new Resource();
        $resource_category->setName('Category');
        $resource_category->setIsMenu(1);
        $resource_category->setDisplayInTree(1);
        $resource_category->setIconClass('fi-list-thumbnails');
        $resource_category->setResourceGroup('category_admin');
        $resource_category->setParent($siteManegment);
        $resource_category->setPermission($viewPermissionObj);
        $em->persist($resource_category);
        $em->flush();

        $resource_category_list = new Resource();
        $resource_category_list->setName('Category list');
        $resource_category_list->setResource('category_admin');
        $resource_category_list->setIsMenu(0);
        $resource_category_list->setDisplayInTree(1);
        $resource_category_list->setParent($resource_category);
        $resource_category_list->setPermission($viewPermissionObj);
        $em->persist($resource_category_list);
        $em->flush();

        $resource_category_list_ajax = new Resource();
        $resource_category_list_ajax->setName('Category list ajax');
        $resource_category_list_ajax->setResource('category_ajax_get_node');
        $resource_category_list_ajax->setIsMenu(0);
        $resource_category_list_ajax->setDisplayInTree(0);
        $resource_category_list_ajax->setParent($resource_category_list);
        $resource_category_list_ajax->setPermission($viewPermissionObj);
        $em->persist($resource_category_list_ajax);
        $em->flush();

        $resource_category_add = new Resource();
        $resource_category_add->setName('Category add');
        $resource_category_add->setResource('category_new_admin');
        $resource_category_add->setIsMenu(0);
        $resource_category_add->setDisplayInTree(1);
        $resource_category_add->setParent($resource_category);
        $resource_category_add->setPermission($createPermissionObj);
        $em->persist($resource_category_add);
        $em->flush();

        $resource_category_create = new Resource();
        $resource_category_create->setName('Category create');
        $resource_category_create->setResource('category_create_admin');
        $resource_category_create->setIsMenu(0);
        $resource_category_create->setDisplayInTree(0);
        $resource_category_create->setParent($resource_category_add);
        $resource_category_create->setPermission($createPermissionObj);
        $em->persist($resource_category_create);
        $em->flush();

        $resource_category_edit = new Resource();
        $resource_category_edit->setName('Category edit');
        $resource_category_edit->setResource('category_edit_admin');
        $resource_category_edit->setIsMenu(0);
        $resource_category_edit->setDisplayInTree(1);
        $resource_category_edit->setParent($resource_category);
        $resource_category_edit->setPermission($editPermissionObj);
        $em->persist($resource_category_edit);
        $em->flush();

        $resource_category_update = new Resource();
        $resource_category_update->setName('Category update');
        $resource_category_update->setResource('category_update_admin');
        $resource_category_update->setIsMenu(0);
        $resource_category_update->setDisplayInTree(0);
        $resource_category_update->setParent($resource_category_edit);
        $resource_category_update->setPermission($editPermissionObj);
        $em->persist($resource_category_update);
        $em->flush();

        $resource_category_delete = new Resource();
        $resource_category_delete->setName('Category delete');
        $resource_category_delete->setResource('category_delete_admin');
        $resource_category_delete->setIsMenu(0);
        $resource_category_delete->setDisplayInTree(1);
        $resource_category_delete->setParent($resource_category);
        $resource_category_delete->setPermission($deletePermissionObj);
        $em->persist($resource_category_delete);
        $em->flush();

        $resource_location = new Resource();
        $resource_location->setName('Location');
        $resource_location->setIsMenu(1);
        $resource_location->setDisplayInTree(1);
        $resource_location->setIconClass('fi-marker');
        $resource_location->setResourceGroup('location');
        $resource_location->setParent($siteManegment);
        $resource_location->setPermission($viewPermissionObj);
        $em->persist($resource_location);
        $em->flush();

        $resource_location_list = new Resource();
        $resource_location_list->setName('Location list');
        $resource_location_list->setResource('location');
        $resource_location_list->setIsMenu(0);
        $resource_location_list->setDisplayInTree(1);
        $resource_location_list->setParent($resource_location);
        $resource_location_list->setPermission($viewPermissionObj);
        $em->persist($resource_location_list);
        $em->flush();

        $resource_location_list_ajax = new Resource();
        $resource_location_list_ajax->setName('Location list ajax');
        $resource_location_list_ajax->setResource('location_ajax_get_node');
        $resource_location_list_ajax->setIsMenu(0);
        $resource_location_list_ajax->setDisplayInTree(0);
        $resource_location_list_ajax->setParent($resource_location_list);
        $resource_location_list_ajax->setPermission($viewPermissionObj);
        $em->persist($resource_location_list_ajax);
        $em->flush();

        $resource_location_add = new Resource();
        $resource_location_add->setName('Location add');
        $resource_location_add->setResource('location_new');
        $resource_location_add->setIsMenu(0);
        $resource_location_add->setDisplayInTree(1);
        $resource_location_add->setParent($resource_location);
        $resource_location_add->setPermission($createPermissionObj);
        $em->persist($resource_location_add);
        $em->flush();

        $resource_location_create = new Resource();
        $resource_location_create->setName('Location create');
        $resource_location_create->setResource('location_create');
        $resource_location_create->setIsMenu(0);
        $resource_location_create->setDisplayInTree(0);
        $resource_location_create->setParent($resource_location_add);
        $resource_location_create->setPermission($createPermissionObj);
        $em->persist($resource_location_create);
        $em->flush();

        $resource_location_edit = new Resource();
        $resource_location_edit->setName('Location edit');
        $resource_location_edit->setResource('location_edit');
        $resource_location_edit->setIsMenu(0);
        $resource_location_edit->setDisplayInTree(1);
        $resource_location_edit->setParent($resource_location);
        $resource_location_edit->setPermission($editPermissionObj);
        $em->persist($resource_location_edit);
        $em->flush();

        $resource_location_update = new Resource();
        $resource_location_update->setName('Location update');
        $resource_location_update->setResource('location_update');
        $resource_location_update->setIsMenu(0);
        $resource_location_update->setDisplayInTree(0);
        $resource_location_update->setParent($resource_location_edit);
        $resource_location_update->setPermission($editPermissionObj);
        $em->persist($resource_location_update);
        $em->flush();

        $resource_location_delete = new Resource();
        $resource_location_delete->setName('Location delete');
        $resource_location_delete->setResource('location_delete');
        $resource_location_delete->setIsMenu(0);
        $resource_location_delete->setDisplayInTree(1);
        $resource_location_delete->setParent($resource_location);
        $resource_location_delete->setPermission($deletePermissionObj);
        $em->persist($resource_location_delete);
        $em->flush();

        $resource_location = new Resource();
        $resource_location->setName('Location Group');
        $resource_location->setIsMenu(1);
        $resource_location->setDisplayInTree(1);
        $resource_location->setIconClass('fi-marker');
        $resource_location->setResourceGroup('location_group_admin');
        $resource_location->setParent($siteManegment);
        $resource_location->setPermission($viewPermissionObj);
        $em->persist($resource_location);
        $em->flush();

        $resource_location_group_list = new Resource();
        $resource_location_group_list->setName('Location Group list');
        $resource_location_group_list->setResource('location_group_admin');
        $resource_location_group_list->setIsMenu(0);
        $resource_location_group_list->setDisplayInTree(1);
        $resource_location_group_list->setParent($resource_location);
        $resource_location_group_list->setPermission($viewPermissionObj);
        $em->persist($resource_location_group_list);
        $em->flush();

        $resource_location_group_add = new Resource();
        $resource_location_group_add->setName('Location Group add');
        $resource_location_group_add->setResource('location_group_new_admin');
        $resource_location_group_add->setIsMenu(0);
        $resource_location_group_add->setDisplayInTree(1);
        $resource_location_group_add->setParent($resource_location);
        $resource_location_group_add->setPermission($createPermissionObj);
        $em->persist($resource_location_group_add);
        $em->flush();

        $resource_location_group_create = new Resource();
        $resource_location_group_create->setName('Location Group create');
        $resource_location_group_create->setResource('location_group_create_admin');
        $resource_location_group_create->setIsMenu(0);
        $resource_location_group_create->setDisplayInTree(0);
        $resource_location_group_create->setParent($resource_location_group_add);
        $resource_location_group_create->setPermission($createPermissionObj);
        $em->persist($resource_location_group_create);
        $em->flush();

        $resource_location_group_edit = new Resource();
        $resource_location_group_edit->setName('Location Group edit');
        $resource_location_group_edit->setResource('location_group_edit_admin');
        $resource_location_group_edit->setIsMenu(0);
        $resource_location_group_edit->setDisplayInTree(1);
        $resource_location_group_edit->setParent($resource_location);
        $resource_location_group_edit->setPermission($editPermissionObj);
        $em->persist($resource_location_group_edit);
        $em->flush();

        $resource_location_group_update = new Resource();
        $resource_location_group_update->setName('Location Group update');
        $resource_location_group_update->setResource('location_group_update_admin');
        $resource_location_group_update->setIsMenu(0);
        $resource_location_group_update->setDisplayInTree(0);
        $resource_location_group_update->setParent($resource_location_group_edit);
        $resource_location_group_update->setPermission($editPermissionObj);
        $em->persist($resource_location_group_update);
        $em->flush();

        $resource_location_group_delete = new Resource();
        $resource_location_group_delete->setName('Location Group delete');
        $resource_location_group_delete->setResource('location_group_delete_admin');
        $resource_location_group_delete->setIsMenu(0);
        $resource_location_group_delete->setDisplayInTree(1);
        $resource_location_group_delete->setParent($resource_location);
        $resource_location_group_delete->setPermission($deletePermissionObj);
        $em->persist($resource_location_group_delete);
        $em->flush();

        $resource_entity = new Resource();
        $resource_entity->setName('Entity');
        $resource_entity->setIsMenu(1);
        $resource_entity->setDisplayInTree(1);
        $resource_entity->setIconClass('fi-asterisk');
        $resource_entity->setResourceGroup('entity');
        $resource_entity->setParent($siteManegment);
        $resource_entity->setPermission($viewPermissionObj);
        $em->persist($resource_entity);
        $em->flush();

        $resource_entity_list = new Resource();
        $resource_entity_list->setName('Entity list');
        $resource_entity_list->setResource('entity');
        $resource_entity_list->setIsMenu(1);
        $resource_entity_list->setDisplayInTree(1);
        $resource_entity_list->setIconClass('fi-list');
        $resource_entity_list->setParent($resource_entity);
        $resource_entity_list->setPermission($viewPermissionObj);
        $em->persist($resource_entity_list);
        $em->flush();

        $resource_entity_add = new Resource();
        $resource_entity_add->setName('Entity add');
        $resource_entity_add->setResource('entity_new');
        $resource_entity_add->setIsMenu(1);
        $resource_entity_add->setDisplayInTree(1);
        $resource_entity_add->setIconClass('fi-plus');
        $resource_entity_add->setParent($resource_entity);
        $resource_entity_add->setPermission($createPermissionObj);
        $em->persist($resource_entity_add);
        $em->flush();

        $resource_entity_create = new Resource();
        $resource_entity_create->setName('Entity create');
        $resource_entity_create->setResource('entity_create');
        $resource_entity_create->setIsMenu(0);
        $resource_entity_create->setDisplayInTree(0);
        $resource_entity_create->setParent($resource_entity_add);
        $resource_entity_create->setPermission($createPermissionObj);
        $em->persist($resource_entity_create);
        $em->flush();

        $resource_entity_edit = new Resource();
        $resource_entity_edit->setName('Entity edit');
        $resource_entity_edit->setResource('entity_edit');
        $resource_entity_edit->setIsMenu(0);
        $resource_entity_edit->setDisplayInTree(1);
        $resource_entity_edit->setParent($resource_entity);
        $resource_entity_edit->setPermission($editPermissionObj);
        $em->persist($resource_entity_edit);
        $em->flush();

        $resource_entity_update = new Resource();
        $resource_entity_update->setName('Entity update');
        $resource_entity_update->setResource('entity_update');
        $resource_entity_update->setIsMenu(0);
        $resource_entity_update->setDisplayInTree(0);
        $resource_entity_update->setParent($resource_entity_edit);
        $resource_entity_update->setPermission($editPermissionObj);
        $em->persist($resource_entity_update);
        $em->flush();

        $resource_entity_delete = new Resource();
        $resource_entity_delete->setName('Entity delete');
        $resource_entity_delete->setResource('entity_delete');
        $resource_entity_delete->setIsMenu(0);
        $resource_entity_delete->setDisplayInTree(1);
        $resource_entity_delete->setParent($resource_entity);
        $resource_entity_delete->setPermission($deletePermissionObj);
        $em->persist($resource_entity_delete);
        $em->flush();

        $resource_dimension_admin = new Resource();
        $resource_dimension_admin->setName('Dimension Value');
        $resource_dimension_admin->setIsMenu(1);
        $resource_dimension_admin->setDisplayInTree(1);
        $resource_dimension_admin->setIconClass('fi-arrows-out');
        $resource_dimension_admin->setResourceGroup('dimension_admin');
        $resource_dimension_admin->setParent($siteManegment);
        $resource_dimension_admin->setPermission($viewPermissionObj);
        $em->persist($resource_dimension_admin);
        $em->flush();

        $resource_dimension_list_admin = new Resource();
        $resource_dimension_list_admin->setName('Dimension Value list');
        $resource_dimension_list_admin->setResource('dimension_admin');
        $resource_dimension_list_admin->setIsMenu(1);
        $resource_dimension_list_admin->setDisplayInTree(1);
        $resource_dimension_list_admin->setIconClass('fi-list');
        $resource_dimension_list_admin->setParent($resource_dimension_admin);
        $resource_dimension_list_admin->setPermission($viewPermissionObj);
        $em->persist($resource_dimension_list_admin);
        $em->flush();

        $resource_dimension_add_admin = new Resource();
        $resource_dimension_add_admin->setName('Dimension Value add');
        $resource_dimension_add_admin->setResource('dimension_new_admin');
        $resource_dimension_add_admin->setIsMenu(1);
        $resource_dimension_add_admin->setDisplayInTree(1);
        $resource_dimension_add_admin->setIconClass('fi-plus');
        $resource_dimension_add_admin->setParent($resource_dimension_admin);
        $resource_dimension_add_admin->setPermission($createPermissionObj);
        $em->persist($resource_dimension_add_admin);
        $em->flush();

        $resource_dimension_create_admin = new Resource();
        $resource_dimension_create_admin->setName('Dimension Value create');
        $resource_dimension_create_admin->setResource('dimension_create_admin');
        $resource_dimension_create_admin->setIsMenu(0);
        $resource_dimension_create_admin->setDisplayInTree(0);
        $resource_dimension_create_admin->setParent($resource_dimension_add_admin);
        $resource_dimension_create_admin->setPermission($createPermissionObj);
        $em->persist($resource_dimension_create_admin);
        $em->flush();

        $resource_dimension_edit_admin = new Resource();
        $resource_dimension_edit_admin->setName('Dimension Value edit');
        $resource_dimension_edit_admin->setResource('dimension_edit_admin');
        $resource_dimension_edit_admin->setIsMenu(0);
        $resource_dimension_edit_admin->setDisplayInTree(1);
        $resource_dimension_edit_admin->setParent($resource_entity);
        $resource_dimension_edit_admin->setPermission($editPermissionObj);
        $em->persist($resource_dimension_edit_admin);
        $em->flush();

        $resource_dimension_update_admin = new Resource();
        $resource_dimension_update_admin->setName('Dimension Value update');
        $resource_dimension_update_admin->setResource('dimension_update_admin');
        $resource_dimension_update_admin->setIsMenu(0);
        $resource_dimension_update_admin->setDisplayInTree(0);
        $resource_dimension_update_admin->setParent($resource_dimension_edit_admin);
        $resource_dimension_update_admin->setPermission($editPermissionObj);
        $em->persist($resource_dimension_update_admin);
        $em->flush();

        $resource_dimension_delete_admin = new Resource();
        $resource_dimension_delete_admin->setName('Dimension Value delete');
        $resource_dimension_delete_admin->setResource('dimension_delete_admin');
        $resource_dimension_delete_admin->setIsMenu(0);
        $resource_dimension_delete_admin->setDisplayInTree(1);
        $resource_dimension_delete_admin->setParent($resource_entity);
        $resource_dimension_delete_admin->setPermission($deletePermissionObj);
        $em->persist($resource_dimension_delete_admin);
        $em->flush();

        $resource_delivery_method_option = new Resource();
        $resource_delivery_method_option->setName('Postage Option');
        $resource_delivery_method_option->setIsMenu(1);
        $resource_delivery_method_option->setDisplayInTree(1);
        $resource_delivery_method_option->setIconClass('fi-clipboard-notes');
        $resource_delivery_method_option->setResourceGroup('delivery_method_option_admin');
        $resource_delivery_method_option->setParent($siteManegment);
        $resource_delivery_method_option->setPermission($viewPermissionObj);
        $em->persist($resource_delivery_method_option);
        $em->flush();

        $resource_delivery_method_option_list = new Resource();
        $resource_delivery_method_option_list->setName('Postage Option list');
        $resource_delivery_method_option_list->setResource('delivery_method_option_admin');
        $resource_delivery_method_option_list->setIsMenu(1);
        $resource_delivery_method_option_list->setDisplayInTree(1);
        $resource_delivery_method_option_list->setIconClass('fi-list');
        $resource_delivery_method_option_list->setParent($resource_delivery_method_option);
        $resource_delivery_method_option_list->setPermission($viewPermissionObj);
        $em->persist($resource_delivery_method_option_list);
        $em->flush();

        $resource_delivery_method_option_add = new Resource();
        $resource_delivery_method_option_add->setName('Postage Option add');
        $resource_delivery_method_option_add->setResource('delivery_method_option_new_admin');
        $resource_delivery_method_option_add->setIsMenu(1);
        $resource_delivery_method_option_add->setDisplayInTree(1);
        $resource_delivery_method_option_add->setIconClass('fi-plus');
        $resource_delivery_method_option_add->setParent($resource_delivery_method_option);
        $resource_delivery_method_option_add->setPermission($createPermissionObj);
        $em->persist($resource_delivery_method_option_add);
        $em->flush();

        $resource_delivery_method_option_create = new Resource();
        $resource_delivery_method_option_create->setName('Postage Option create');
        $resource_delivery_method_option_create->setResource('delivery_method_option_create_admin');
        $resource_delivery_method_option_create->setIsMenu(0);
        $resource_delivery_method_option_create->setDisplayInTree(0);
        $resource_delivery_method_option_create->setParent($resource_delivery_method_option_add);
        $resource_delivery_method_option_create->setPermission($createPermissionObj);
        $em->persist($resource_delivery_method_option_create);
        $em->flush();

        $resource_delivery_method_option_edit = new Resource();
        $resource_delivery_method_option_edit->setName('Postage Option edit');
        $resource_delivery_method_option_edit->setResource('delivery_method_option_edit_admin');
        $resource_delivery_method_option_edit->setIsMenu(0);
        $resource_delivery_method_option_edit->setDisplayInTree(1);
        $resource_delivery_method_option_edit->setParent($resource_delivery_method_option);
        $resource_delivery_method_option_edit->setPermission($editPermissionObj);
        $em->persist($resource_delivery_method_option_edit);
        $em->flush();

        $resource_delivery_method_option_update = new Resource();
        $resource_delivery_method_option_update->setName('Postage Option update');
        $resource_delivery_method_option_update->setResource('delivery_method_option_update_admin');
        $resource_delivery_method_option_update->setIsMenu(0);
        $resource_delivery_method_option_update->setDisplayInTree(0);
        $resource_delivery_method_option_update->setParent($resource_delivery_method_option_edit);
        $resource_delivery_method_option_update->setPermission($editPermissionObj);
        $em->persist($resource_delivery_method_option_update);
        $em->flush();

        $resource_delivery_method_option_delete = new Resource();
        $resource_delivery_method_option_delete->setName('Postage Option delete');
        $resource_delivery_method_option_delete->setResource('delivery_method_option_delete_admin');
        $resource_delivery_method_option_delete->setIsMenu(0);
        $resource_delivery_method_option_delete->setDisplayInTree(1);
        $resource_delivery_method_option_delete->setParent($resource_delivery_method_option);
        $resource_delivery_method_option_delete->setPermission($deletePermissionObj);
        $em->persist($resource_delivery_method_option_delete);
        $em->flush();

        $resource_paa_field_rule = new Resource();
        $resource_paa_field_rule->setName('PAA field rule');
        $resource_paa_field_rule->setIsMenu(1);
        $resource_paa_field_rule->setDisplayInTree(1);
        $resource_paa_field_rule->setIconClass('fi-book');
        $resource_paa_field_rule->setResourceGroup('paa_field_rule_admin');
        $resource_paa_field_rule->setParent($siteManegment);
        $resource_paa_field_rule->setPermission($viewPermissionObj);
        $em->persist($resource_paa_field_rule);
        $em->flush();

        $resource_paa_field_rule_list = new Resource();
        $resource_paa_field_rule_list->setName('PAA field rule list');
        $resource_paa_field_rule_list->setResource('paa_field_rule_admin');
        $resource_paa_field_rule_list->setIsMenu(1);
        $resource_paa_field_rule_list->setDisplayInTree(1);
        $resource_paa_field_rule_list->setIconClass('fi-list');
        $resource_paa_field_rule_list->setParent($resource_paa_field_rule);
        $resource_paa_field_rule_list->setPermission($viewPermissionObj);
        $em->persist($resource_paa_field_rule_list);
        $em->flush();

        $resource_paa_field_rule_add = new Resource();
        $resource_paa_field_rule_add->setName('PAA field rule add');
        $resource_paa_field_rule_add->setResource('paa_field_rule_new_admin');
        $resource_paa_field_rule_add->setIsMenu(1);
        $resource_paa_field_rule_add->setDisplayInTree(1);
        $resource_paa_field_rule_add->setIconClass('fi-plus');
        $resource_paa_field_rule_add->setParent($resource_paa_field_rule);
        $resource_paa_field_rule_add->setPermission($createPermissionObj);
        $em->persist($resource_paa_field_rule_add);
        $em->flush();

        $resource_paa_field_rule_add_from_cat = new Resource();
        $resource_paa_field_rule_add_from_cat->setName('PAA field rule add from category');
        $resource_paa_field_rule_add_from_cat->setResource('paa_field_rule_new_from_category_admin');
        $resource_paa_field_rule_add_from_cat->setIsMenu(0);
        $resource_paa_field_rule_add_from_cat->setDisplayInTree(0);
        $resource_paa_field_rule_add_from_cat->setParent($resource_paa_field_rule_add);
        $resource_paa_field_rule_add_from_cat->setPermission($createPermissionObj);
        $em->persist($resource_paa_field_rule_add_from_cat);
        $em->flush();

        $resource_paa_field_rule_create = new Resource();
        $resource_paa_field_rule_create->setName('PAA field rule create');
        $resource_paa_field_rule_create->setResource('paa_field_rule_create_admin');
        $resource_paa_field_rule_create->setIsMenu(0);
        $resource_paa_field_rule_create->setDisplayInTree(0);
        $resource_paa_field_rule_create->setParent($resource_paa_field_rule_add);
        $resource_paa_field_rule_create->setPermission($createPermissionObj);
        $em->persist($resource_paa_field_rule_create);
        $em->flush();

        $resource_paa_field_rule_edit = new Resource();
        $resource_paa_field_rule_edit->setName('PAA field rule edit');
        $resource_paa_field_rule_edit->setResource('paa_field_rule_edit_admin');
        $resource_paa_field_rule_edit->setIsMenu(0);
        $resource_paa_field_rule_edit->setDisplayInTree(1);
        $resource_paa_field_rule_edit->setParent($resource_paa_field_rule);
        $resource_paa_field_rule_edit->setPermission($editPermissionObj);
        $em->persist($resource_paa_field_rule_edit);
        $em->flush();

        $resource_paa_field_rule_update = new Resource();
        $resource_paa_field_rule_update->setName('PAA field rule update');
        $resource_paa_field_rule_update->setResource('paa_field_rule_update_admin');
        $resource_paa_field_rule_update->setIsMenu(0);
        $resource_paa_field_rule_update->setDisplayInTree(0);
        $resource_paa_field_rule_update->setParent($resource_paa_field_rule_edit);
        $resource_paa_field_rule_update->setPermission($editPermissionObj);
        $em->persist($resource_paa_field_rule_update);
        $em->flush();

        $resource_paa_field_rule_delete = new Resource();
        $resource_paa_field_rule_delete->setName('PAA field rule delete');
        $resource_paa_field_rule_delete->setResource('paa_field_rule_delete_admin');
        $resource_paa_field_rule_delete->setIsMenu(0);
        $resource_paa_field_rule_delete->setDisplayInTree(1);
        $resource_paa_field_rule_delete->setParent($resource_paa_field_rule);
        $resource_paa_field_rule_delete->setPermission($deletePermissionObj);
        $em->persist($resource_paa_field_rule_delete);
        $em->flush();

        $resource_paa_field_rule_show = new Resource();
        $resource_paa_field_rule_show->setName('PAA field rule show');
        $resource_paa_field_rule_show->setResource('paa_field_rule_show_admin');
        $resource_paa_field_rule_show->setIsMenu(0);
        $resource_paa_field_rule_show->setDisplayInTree(1);
        $resource_paa_field_rule_show->setParent($resource_paa_field_rule);
        $resource_paa_field_rule_show->setPermission($viewPermissionObj);
        $em->persist($resource_paa_field_rule_show);
        $em->flush();

        $resource_print_edition = new Resource();
        $resource_print_edition->setName('Print edition');
        $resource_print_edition->setIsMenu(1);
        $resource_print_edition->setDisplayInTree(1);
        $resource_print_edition->setIconClass('fi-print');
        $resource_print_edition->setResourceGroup('print_edition_admin');
        $resource_print_edition->setParent($siteManegment);
        $resource_print_edition->setPermission($viewPermissionObj);
        $em->persist($resource_print_edition);
        $em->flush();

        $resource_print_edition_list = new Resource();
        $resource_print_edition_list->setName('Print edition list');
        $resource_print_edition_list->setResource('print_edition_admin');
        $resource_print_edition_list->setIsMenu(1);
        $resource_print_edition_list->setDisplayInTree(1);
        $resource_print_edition_list->setIconClass('fi-list');
        $resource_print_edition_list->setParent($resource_print_edition);
        $resource_print_edition_list->setPermission($viewPermissionObj);
        $em->persist($resource_print_edition_list);
        $em->flush();

        $resource_print_edition_add = new Resource();
        $resource_print_edition_add->setName('Print edition add');
        $resource_print_edition_add->setResource('print_edition_new_admin');
        $resource_print_edition_add->setIsMenu(1);
        $resource_print_edition_add->setDisplayInTree(1);
        $resource_print_edition_add->setIconClass('fi-plus');
        $resource_print_edition_add->setParent($resource_print_edition);
        $resource_print_edition_add->setPermission($createPermissionObj);
        $em->persist($resource_print_edition_add);
        $em->flush();

        $resource_print_edition_create = new Resource();
        $resource_print_edition_create->setName('Print edition create');
        $resource_print_edition_create->setResource('print_edition_create_admin');
        $resource_print_edition_create->setIsMenu(0);
        $resource_print_edition_create->setDisplayInTree(0);
        $resource_print_edition_create->setParent($resource_print_edition_add);
        $resource_print_edition_create->setPermission($createPermissionObj);
        $em->persist($resource_print_edition_create);
        $em->flush();

        $resource_print_edition_edit = new Resource();
        $resource_print_edition_edit->setName('Print edition edit');
        $resource_print_edition_edit->setResource('print_edition_edit_admin');
        $resource_print_edition_edit->setIsMenu(0);
        $resource_print_edition_edit->setDisplayInTree(1);
        $resource_print_edition_edit->setParent($resource_print_edition);
        $resource_print_edition_edit->setPermission($editPermissionObj);
        $em->persist($resource_print_edition_edit);
        $em->flush();

        $resource_print_edition_update = new Resource();
        $resource_print_edition_update->setName('Print edition update');
        $resource_print_edition_update->setResource('print_edition_update_admin');
        $resource_print_edition_update->setIsMenu(0);
        $resource_print_edition_update->setDisplayInTree(0);
        $resource_print_edition_update->setParent($resource_print_edition_edit);
        $resource_print_edition_update->setPermission($editPermissionObj);
        $em->persist($resource_print_edition_update);
        $em->flush();

        $resource_print_edition_delete = new Resource();
        $resource_print_edition_delete->setName('Print edition delete');
        $resource_print_edition_delete->setResource('print_edition_delete_admin');
        $resource_print_edition_delete->setIsMenu(0);
        $resource_print_edition_delete->setDisplayInTree(1);
        $resource_print_edition_delete->setParent($resource_print_edition);
        $resource_print_edition_delete->setPermission($deletePermissionObj);
        $em->persist($resource_print_edition_delete);
        $em->flush();

        $resource_print_deadline = new Resource();
        $resource_print_deadline->setName('Print deadline');
        $resource_print_deadline->setIsMenu(1);
        $resource_print_deadline->setDisplayInTree(1);
        $resource_print_deadline->setIconClass('fi-print');
        $resource_print_deadline->setResourceGroup('print_deadline_admin');
        $resource_print_deadline->setParent($siteManegment);
        $resource_print_deadline->setPermission($viewPermissionObj);
        $em->persist($resource_print_deadline);
        $em->flush();

        $resource_print_deadline_list = new Resource();
        $resource_print_deadline_list->setName('Print deadline list');
        $resource_print_deadline_list->setResource('print_deadline_admin');
        $resource_print_deadline_list->setIsMenu(1);
        $resource_print_deadline_list->setDisplayInTree(1);
        $resource_print_deadline_list->setIconClass('fi-list');
        $resource_print_deadline_list->setParent($resource_print_deadline);
        $resource_print_deadline_list->setPermission($viewPermissionObj);
        $em->persist($resource_print_deadline_list);
        $em->flush();

        $resource_print_deadline_add = new Resource();
        $resource_print_deadline_add->setName('Print deadline add');
        $resource_print_deadline_add->setResource('print_deadline_new_admin');
        $resource_print_deadline_add->setIsMenu(1);
        $resource_print_deadline_add->setDisplayInTree(1);
        $resource_print_deadline_add->setIconClass('fi-plus');
        $resource_print_deadline_add->setParent($resource_print_deadline);
        $resource_print_deadline_add->setPermission($createPermissionObj);
        $em->persist($resource_print_deadline_add);
        $em->flush();

        $resource_print_deadline_create = new Resource();
        $resource_print_deadline_create->setName('Print deadline create');
        $resource_print_deadline_create->setResource('print_deadline_create_admin');
        $resource_print_deadline_create->setIsMenu(0);
        $resource_print_deadline_create->setDisplayInTree(0);
        $resource_print_deadline_create->setParent($resource_print_deadline_add);
        $resource_print_deadline_create->setPermission($createPermissionObj);
        $em->persist($resource_print_deadline_create);
        $em->flush();

        $resource_print_deadline_edit = new Resource();
        $resource_print_deadline_edit->setName('Print deadline edit');
        $resource_print_deadline_edit->setResource('print_deadline_edit_admin');
        $resource_print_deadline_edit->setIsMenu(0);
        $resource_print_deadline_edit->setDisplayInTree(1);
        $resource_print_deadline_edit->setParent($resource_print_deadline);
        $resource_print_deadline_edit->setPermission($editPermissionObj);
        $em->persist($resource_print_deadline_edit);
        $em->flush();

        $resource_print_deadline_update = new Resource();
        $resource_print_deadline_update->setName('Print deadline update');
        $resource_print_deadline_update->setResource('print_deadline_update_admin');
        $resource_print_deadline_update->setIsMenu(0);
        $resource_print_deadline_update->setDisplayInTree(0);
        $resource_print_deadline_update->setParent($resource_print_deadline_edit);
        $resource_print_deadline_update->setPermission($editPermissionObj);
        $em->persist($resource_print_deadline_update);
        $em->flush();

        $resource_print_deadline_delete = new Resource();
        $resource_print_deadline_delete->setName('Print deadline delete');
        $resource_print_deadline_delete->setResource('print_deadline_delete_admin');
        $resource_print_deadline_delete->setIsMenu(0);
        $resource_print_deadline_delete->setDisplayInTree(1);
        $resource_print_deadline_delete->setParent($resource_print_deadline);
        $resource_print_deadline_delete->setPermission($deletePermissionObj);
        $em->persist($resource_print_deadline_delete);
        $em->flush();

        $resource_resource = new Resource();
        $resource_resource->setName('Resource');
        $resource_resource->setIsMenu(1);
        $resource_resource->setDisplayInTree(1);
        $resource_resource->setIconClass('fi-pencil');
        $resource_resource->setResourceGroup('resource');
        $resource_resource->setParent($siteManegment);
        $resource_resource->setPermission($viewPermissionObj);
        $em->persist($resource_resource);
        $em->flush();

        $resource_resource_list = new Resource();
        $resource_resource_list->setName('Resource list');
        $resource_resource_list->setResource('resource');
        $resource_resource_list->setIsMenu(0);
        $resource_resource_list->setDisplayInTree(1);
        $resource_resource_list->setParent($resource_resource);
        $resource_resource_list->setPermission($viewPermissionObj);
        $em->persist($resource_resource_list);
        $em->flush();

        $resource_resource_add = new Resource();
        $resource_resource_add->setName('Resource add');
        $resource_resource_add->setResource('resource_new');
        $resource_resource_add->setIsMenu(0);
        $resource_resource_add->setDisplayInTree(1);
        $resource_resource_add->setParent($resource_resource);
        $resource_resource_add->setPermission($createPermissionObj);
        $em->persist($resource_resource_add);
        $em->flush();

        $resource_resource_create = new Resource();
        $resource_resource_create->setName('Resource create');
        $resource_resource_create->setResource('resource_create');
        $resource_resource_create->setIsMenu(0);
        $resource_resource_create->setDisplayInTree(0);
        $resource_resource_create->setParent($resource_resource_add);
        $resource_resource_create->setPermission($createPermissionObj);
        $em->persist($resource_resource_create);
        $em->flush();

        $resource_resource_edit = new Resource();
        $resource_resource_edit->setName('Resource edit');
        $resource_resource_edit->setResource('resource_edit');
        $resource_resource_edit->setIsMenu(0);
        $resource_resource_edit->setDisplayInTree(1);
        $resource_resource_edit->setParent($resource_resource);
        $resource_resource_edit->setPermission($editPermissionObj);
        $em->persist($resource_resource_edit);
        $em->flush();

        $resource_resource_update = new Resource();
        $resource_resource_update->setName('Resource update');
        $resource_resource_update->setResource('resource_update');
        $resource_resource_update->setIsMenu(0);
        $resource_resource_update->setDisplayInTree(0);
        $resource_resource_update->setParent($resource_resource_edit);
        $resource_resource_update->setPermission($editPermissionObj);
        $em->persist($resource_resource_update);
        $em->flush();

        $resource_resource_delete = new Resource();
        $resource_resource_delete->setName('Resource delete');
        $resource_resource_delete->setResource('resource_delete');
        $resource_resource_delete->setIsMenu(0);
        $resource_resource_delete->setDisplayInTree(1);
        $resource_resource_delete->setParent($resource_resource);
        $resource_resource_delete->setPermission($deletePermissionObj);
        $em->persist($resource_resource_delete);
        $em->flush();

        $seoManegment = new Resource();
        $seoManegment->setName('Seo');
        $seoManegment->setIsMenu(1);
        $seoManegment->setDisplayInTree(1);
        $seoManegment->setIconClass('fi-page-search');
        $seoManegment->setResourceGroup('fa_admin_homepage');
        $seoManegment->setParent($resource_root);
        $seoManegment->setPermission($viewPermissionObj);
        $em->persist($seoManegment);
        $em->flush();

        $resource_seo_tool = new Resource();
        $resource_seo_tool->setName('Seo tool');
        $resource_seo_tool->setIsMenu(1);
        $resource_seo_tool->setDisplayInTree(1);
        $resource_seo_tool->setIconClass('fi-page');
        $resource_seo_tool->setResourceGroup('seo_tool_admin');
        $resource_seo_tool->setParent($seoManegment);
        $resource_seo_tool->setPermission($viewPermissionObj);
        $em->persist($resource_seo_tool);
        $em->flush();

        $resource_seo_tool_list = new Resource();
        $resource_seo_tool_list->setName('Seo tool list');
        $resource_seo_tool_list->setResource('seo_tool_admin');
        $resource_seo_tool_list->setIsMenu(1);
        $resource_seo_tool_list->setDisplayInTree(1);
        $resource_seo_tool_list->setIconClass('fi-list');
        $resource_seo_tool_list->setParent($resource_seo_tool);
        $resource_seo_tool_list->setPermission($viewPermissionObj);
        $em->persist($resource_seo_tool_list);
        $em->flush();

        $resource_seo_tool_add = new Resource();
        $resource_seo_tool_add->setName('Seo tool add');
        $resource_seo_tool_add->setResource('seo_tool_new_admin');
        $resource_seo_tool_add->setIsMenu(1);
        $resource_seo_tool_add->setDisplayInTree(1);
        $resource_seo_tool_add->setIconClass('fi-plus');
        $resource_seo_tool_add->setParent($resource_seo_tool);
        $resource_seo_tool_add->setPermission($createPermissionObj);
        $em->persist($resource_seo_tool_add);
        $em->flush();

        $resource_seo_tool_create = new Resource();
        $resource_seo_tool_create->setName('Seo tool create');
        $resource_seo_tool_create->setResource('seo_tool_create_admin');
        $resource_seo_tool_create->setIsMenu(0);
        $resource_seo_tool_create->setDisplayInTree(0);
        $resource_seo_tool_create->setParent($resource_seo_tool_add);
        $resource_seo_tool_create->setPermission($createPermissionObj);
        $em->persist($resource_seo_tool_create);
        $em->flush();

        $resource_seo_tool_edit = new Resource();
        $resource_seo_tool_edit->setName('Seo tool edit');
        $resource_seo_tool_edit->setResource('seo_tool_edit_admin');
        $resource_seo_tool_edit->setIsMenu(0);
        $resource_seo_tool_edit->setDisplayInTree(1);
        $resource_seo_tool_edit->setParent($resource_seo_tool);
        $resource_seo_tool_edit->setPermission($editPermissionObj);
        $em->persist($resource_seo_tool_edit);
        $em->flush();

        $resource_seo_tool_update = new Resource();
        $resource_seo_tool_update->setName('Seo tool update');
        $resource_seo_tool_update->setResource('seo_tool_update_admin');
        $resource_seo_tool_update->setIsMenu(0);
        $resource_seo_tool_update->setDisplayInTree(0);
        $resource_seo_tool_update->setParent($resource_seo_tool_edit);
        $resource_seo_tool_update->setPermission($editPermissionObj);
        $em->persist($resource_seo_tool_update);
        $em->flush();

        $resource_seo_tool_delete = new Resource();
        $resource_seo_tool_delete->setName('Seo tool delete');
        $resource_seo_tool_delete->setResource('seo_tool_delete_admin');
        $resource_seo_tool_delete->setIsMenu(0);
        $resource_seo_tool_delete->setDisplayInTree(1);
        $resource_seo_tool_delete->setParent($resource_seo_tool);
        $resource_seo_tool_delete->setPermission($deletePermissionObj);
        $em->persist($resource_seo_tool_delete);
        $em->flush();

        $reportManegment = new Resource();
        $reportManegment->setName('Reporting & Statistics');
        $reportManegment->setIsMenu(1);
        $reportManegment->setDisplayInTree(1);
        $reportManegment->setIconClass('fi-graph-trend');
        $reportManegment->setResourceGroup('fa_report_ad');
        $reportManegment->setParent($resource_root);
        $reportManegment->setPermission($viewPermissionObj);
        $em->persist($reportManegment);
        $em->flush();

        $resource_user_report = new Resource();
        $resource_user_report->setName('User Report');
        $resource_user_report->setIsMenu(1);
        $resource_user_report->setDisplayInTree(1);
        $resource_user_report->setIconClass('fi-torso');
        $resource_user_report->setResource('fa_report_user');
        $resource_user_report->setResourceGroup('fa_report_user');
        $resource_user_report->setParent($reportManegment);
        $resource_user_report->setPermission($viewPermissionObj);
        $em->persist($resource_user_report);
        $em->flush();

        $resource_user_report_export_csv = new Resource();
        $resource_user_report_export_csv->setName('User Report Export To CSV');
        $resource_user_report_export_csv->setResource('fa_report_user_export_to_csv');
        $resource_user_report_export_csv->setIsMenu(0);
        $resource_user_report_export_csv->setDisplayInTree(1);
        $resource_user_report_export_csv->setParent($resource_user_report);
        $resource_user_report_export_csv->setPermission($viewPermissionObj);
        $em->persist($resource_user_report_export_csv);
        $em->flush();

        $resource_user_report_csv_list = new Resource();
        $resource_user_report_csv_list->setName('User Report CSV List');
        $resource_user_report_csv_list->setResource('ajax_fa_report_user_csv_list');
        $resource_user_report_csv_list->setIsMenu(0);
        $resource_user_report_csv_list->setDisplayInTree(1);
        $resource_user_report_csv_list->setParent($resource_user_report);
        $resource_user_report_csv_list->setPermission($viewPermissionObj);
        $em->persist($resource_user_report_csv_list);
        $em->flush();

        $resource_user_report_csv_delete = new Resource();
        $resource_user_report_csv_delete->setName('User Report CSV Delete');
        $resource_user_report_csv_delete->setResource('ajax_fa_report_user_csv_delete');
        $resource_user_report_csv_delete->setIsMenu(0);
        $resource_user_report_csv_delete->setDisplayInTree(1);
        $resource_user_report_csv_delete->setParent($resource_user_report);
        $resource_user_report_csv_delete->setPermission($viewPermissionObj);
        $em->persist($resource_user_report_csv_delete);
        $em->flush();

        $resource_user_report_csv_download = new Resource();
        $resource_user_report_csv_download->setName('User Report CSV Download');
        $resource_user_report_csv_download->setResource('fa_report_user_download_csv');
        $resource_user_report_csv_download->setIsMenu(0);
        $resource_user_report_csv_download->setDisplayInTree(1);
        $resource_user_report_csv_download->setParent($resource_user_report);
        $resource_user_report_csv_download->setPermission($viewPermissionObj);
        $em->persist($resource_user_report_csv_download);
        $em->flush();

        $resource_ad_report = new Resource();
        $resource_ad_report->setName('Ad Report');
        $resource_ad_report->setIsMenu(1);
        $resource_ad_report->setDisplayInTree(1);
        $resource_ad_report->setIconClass('fi-monitor');
        $resource_ad_report->setResource('fa_report_ad');
        $resource_ad_report->setResourceGroup('fa_report_ad');
        $resource_ad_report->setParent($reportManegment);
        $resource_ad_report->setPermission($viewPermissionObj);
        $em->persist($resource_ad_report);
        $em->flush();

        $resource_ad_report_export_csv = new Resource();
        $resource_ad_report_export_csv->setName('Ad Report Export To CSV');
        $resource_ad_report_export_csv->setResource('fa_report_ad_export_to_csv');
        $resource_ad_report_export_csv->setIsMenu(0);
        $resource_ad_report_export_csv->setDisplayInTree(1);
        $resource_ad_report_export_csv->setParent($resource_ad_report);
        $resource_ad_report_export_csv->setPermission($viewPermissionObj);
        $em->persist($resource_ad_report_export_csv);
        $em->flush();

        $resource_ad_report_csv_list = new Resource();
        $resource_ad_report_csv_list->setName('Ad Report CSV List');
        $resource_ad_report_csv_list->setResource('ajax_fa_report_ad_csv_list');
        $resource_ad_report_csv_list->setIsMenu(0);
        $resource_ad_report_csv_list->setDisplayInTree(1);
        $resource_ad_report_csv_list->setParent($resource_ad_report);
        $resource_ad_report_csv_list->setPermission($viewPermissionObj);
        $em->persist($resource_ad_report_csv_list);
        $em->flush();

        $resource_ad_report_csv_delete = new Resource();
        $resource_ad_report_csv_delete->setName('Ad Report CSV Delete');
        $resource_ad_report_csv_delete->setResource('ajax_fa_report_ad_csv_delete');
        $resource_ad_report_csv_delete->setIsMenu(0);
        $resource_ad_report_csv_delete->setDisplayInTree(1);
        $resource_ad_report_csv_delete->setParent($resource_ad_report);
        $resource_ad_report_csv_delete->setPermission($viewPermissionObj);
        $em->persist($resource_ad_report_csv_delete);
        $em->flush();

        $resource_ad_report_csv_download = new Resource();
        $resource_ad_report_csv_download->setName('Ad Report CSV Download');
        $resource_ad_report_csv_download->setResource('fa_report_ad_download_csv');
        $resource_ad_report_csv_download->setIsMenu(0);
        $resource_ad_report_csv_download->setDisplayInTree(1);
        $resource_ad_report_csv_download->setParent($resource_ad_report);
        $resource_ad_report_csv_download->setPermission($viewPermissionObj);
        $em->persist($resource_ad_report_csv_download);
        $em->flush();

        $resource_ad_print_report = new Resource();
        $resource_ad_print_report->setName('Print Expiry report');
        $resource_ad_print_report->setIsMenu(1);
        $resource_ad_print_report->setDisplayInTree(1);
        $resource_ad_print_report->setIconClass('fi-print');
        $resource_ad_print_report->setResource('fa_report_ad_print');
        $resource_ad_print_report->setResourceGroup('fa_report_ad_print');
        $resource_ad_print_report->setParent($reportManegment);
        $resource_ad_print_report->setPermission($viewPermissionObj);
        $em->persist($resource_ad_print_report);
        $em->flush();

        $resource_ad_print_report_export_csv = new Resource();
        $resource_ad_print_report_export_csv->setName('Print Expiry report Export To CSV');
        $resource_ad_print_report_export_csv->setResource('fa_report_ad_print_export_to_csv');
        $resource_ad_print_report_export_csv->setIsMenu(0);
        $resource_ad_print_report_export_csv->setDisplayInTree(1);
        $resource_ad_print_report_export_csv->setParent($resource_ad_print_report);
        $resource_ad_print_report_export_csv->setPermission($viewPermissionObj);
        $em->persist($resource_ad_print_report_export_csv);
        $em->flush();

        $resource_ad_print_report_csv_list = new Resource();
        $resource_ad_print_report_csv_list->setName('Print Expiry report CSV List');
        $resource_ad_print_report_csv_list->setResource('ajax_fa_report_ad_print_csv_list');
        $resource_ad_print_report_csv_list->setIsMenu(0);
        $resource_ad_print_report_csv_list->setDisplayInTree(1);
        $resource_ad_print_report_csv_list->setParent($resource_ad_print_report);
        $resource_ad_print_report_csv_list->setPermission($viewPermissionObj);
        $em->persist($resource_ad_print_report_csv_list);
        $em->flush();

        $resource_ad_print_report_csv_delete = new Resource();
        $resource_ad_print_report_csv_delete->setName('Print Expiry report CSV Delete');
        $resource_ad_print_report_csv_delete->setResource('ajax_fa_report_ad_print_csv_delete');
        $resource_ad_print_report_csv_delete->setIsMenu(0);
        $resource_ad_print_report_csv_delete->setDisplayInTree(1);
        $resource_ad_print_report_csv_delete->setParent($resource_ad_print_report);
        $resource_ad_print_report_csv_delete->setPermission($viewPermissionObj);
        $em->persist($resource_ad_print_report_csv_delete);
        $em->flush();

        $resource_ad_print_report_csv_download = new Resource();
        $resource_ad_print_report_csv_download->setName('Print Expiry report CSV Download');
        $resource_ad_print_report_csv_download->setResource('fa_report_ad_print_download_csv');
        $resource_ad_print_report_csv_download->setIsMenu(0);
        $resource_ad_print_report_csv_download->setDisplayInTree(1);
        $resource_ad_print_report_csv_download->setParent($resource_ad_print_report);
        $resource_ad_print_report_csv_download->setPermission($viewPermissionObj);
        $em->persist($resource_ad_print_report_csv_download);
        $em->flush();

        $resource_ad_enquiry_report = new Resource();
        $resource_ad_enquiry_report->setName('Ad Enquiry Report');
        $resource_ad_enquiry_report->setIsMenu(1);
        $resource_ad_enquiry_report->setDisplayInTree(1);
        $resource_ad_enquiry_report->setIconClass('fi-monitor');
        $resource_ad_enquiry_report->setResource('fa_report_ad_enquiry');
        $resource_ad_enquiry_report->setResourceGroup('fa_report_ad_enquiry');
        $resource_ad_enquiry_report->setParent($reportManegment);
        $resource_ad_enquiry_report->setPermission($viewPermissionObj);
        $em->persist($resource_ad_enquiry_report);
        $em->flush();

        $resource_ad_enquiry_report_export_csv = new Resource();
        $resource_ad_enquiry_report_export_csv->setName('Ad Enquiry Report Export To CSV');
        $resource_ad_enquiry_report_export_csv->setResource('fa_report_ad_enquiry_export_to_csv');
        $resource_ad_enquiry_report_export_csv->setIsMenu(0);
        $resource_ad_enquiry_report_export_csv->setDisplayInTree(1);
        $resource_ad_enquiry_report_export_csv->setParent($resource_ad_enquiry_report);
        $resource_ad_enquiry_report_export_csv->setPermission($viewPermissionObj);
        $em->persist($resource_ad_enquiry_report_export_csv);
        $em->flush();

        $resource_ad_enquiry_report_csv_list = new Resource();
        $resource_ad_enquiry_report_csv_list->setName('Ad Enquiry Report CSV List');
        $resource_ad_enquiry_report_csv_list->setResource('ajax_fa_report_ad_enquiry_csv_list');
        $resource_ad_enquiry_report_csv_list->setIsMenu(0);
        $resource_ad_enquiry_report_csv_list->setDisplayInTree(1);
        $resource_ad_enquiry_report_csv_list->setParent($resource_ad_enquiry_report);
        $resource_ad_enquiry_report_csv_list->setPermission($viewPermissionObj);
        $em->persist($resource_ad_enquiry_report_csv_list);
        $em->flush();

        $resource_ad_enquiry_report_csv_delete = new Resource();
        $resource_ad_enquiry_report_csv_delete->setName('Ad Enquiry Report CSV Delete');
        $resource_ad_enquiry_report_csv_delete->setResource('ajax_fa_report_ad_enquiry_csv_delete');
        $resource_ad_enquiry_report_csv_delete->setIsMenu(0);
        $resource_ad_enquiry_report_csv_delete->setDisplayInTree(1);
        $resource_ad_enquiry_report_csv_delete->setParent($resource_ad_enquiry_report);
        $resource_ad_enquiry_report_csv_delete->setPermission($viewPermissionObj);
        $em->persist($resource_ad_enquiry_report_csv_delete);
        $em->flush();

        $resource_ad_enquiry_report_csv_download = new Resource();
        $resource_ad_enquiry_report_csv_download->setName('Ad Enquiry Report CSV Download');
        $resource_ad_enquiry_report_csv_download->setResource('fa_report_ad_enquiry_download_csv');
        $resource_ad_enquiry_report_csv_download->setIsMenu(0);
        $resource_ad_enquiry_report_csv_download->setDisplayInTree(1);
        $resource_ad_enquiry_report_csv_download->setParent($resource_ad_enquiry_report);
        $resource_ad_enquiry_report_csv_download->setPermission($viewPermissionObj);
        $em->persist($resource_ad_enquiry_report_csv_download);
        $em->flush();

        $resource_ppr_report = new Resource();
        $resource_ppr_report->setName('Profile Package Revenue Report');
        $resource_ppr_report->setIsMenu(1);
        $resource_ppr_report->setDisplayInTree(1);
        $resource_ppr_report->setIconClass('fi-monitor');
        $resource_ppr_report->setResource('fa_report_profile_package_revenue');
        $resource_ppr_report->setResourceGroup('fa_report_profile_package_revenue');
        $resource_ppr_report->setParent($reportManegment);
        $resource_ppr_report->setPermission($viewPermissionObj);
        $em->persist($resource_ppr_report);
        $em->flush();

        $resource_ppr_report_export_csv = new Resource();
        $resource_ppr_report_export_csv->setName('Profile Package Revenue Report Export To CSV');
        $resource_ppr_report_export_csv->setResource('fa_report_profile_package_revenue_export_to_csv');
        $resource_ppr_report_export_csv->setIsMenu(0);
        $resource_ppr_report_export_csv->setDisplayInTree(1);
        $resource_ppr_report_export_csv->setParent($resource_ppr_report);
        $resource_ppr_report_export_csv->setPermission($viewPermissionObj);
        $em->persist($resource_ppr_report_export_csv);
        $em->flush();

        $resource_ppr_report_csv_list = new Resource();
        $resource_ppr_report_csv_list->setName('Profile Package Revenue Report CSV List');
        $resource_ppr_report_csv_list->setResource('ajax_fa_report_profile_package_revenue_csv_list');
        $resource_ppr_report_csv_list->setIsMenu(0);
        $resource_ppr_report_csv_list->setDisplayInTree(1);
        $resource_ppr_report_csv_list->setParent($resource_ppr_report);
        $resource_ppr_report_csv_list->setPermission($viewPermissionObj);
        $em->persist($resource_ppr_report_csv_list);
        $em->flush();

        $resource_ppr_report_csv_delete = new Resource();
        $resource_ppr_report_csv_delete->setName('Profile Package Revenue Report CSV Delete');
        $resource_ppr_report_csv_delete->setResource('ajax_fa_report_profile_package_revenue_csv_delete');
        $resource_ppr_report_csv_delete->setIsMenu(0);
        $resource_ppr_report_csv_delete->setDisplayInTree(1);
        $resource_ppr_report_csv_delete->setParent($resource_ppr_report);
        $resource_ppr_report_csv_delete->setPermission($viewPermissionObj);
        $em->persist($resource_ppr_report_csv_delete);
        $em->flush();

        $resource_ppr_report_csv_download = new Resource();
        $resource_ppr_report_csv_download->setName('Profile Package Revenue Report CSV Download');
        $resource_ppr_report_csv_download->setResource('fa_report_profile_package_revenue_download_csv');
        $resource_ppr_report_csv_download->setIsMenu(0);
        $resource_ppr_report_csv_download->setDisplayInTree(1);
        $resource_ppr_report_csv_download->setParent($resource_ppr_report);
        $resource_ppr_report_csv_download->setPermission($viewPermissionObj);
        $em->persist($resource_ppr_report_csv_download);
        $em->flush();

        $resource_auto_email_report = new Resource();
        $resource_auto_email_report->setName('Automated email report');
        $resource_auto_email_report->setIsMenu(1);
        $resource_auto_email_report->setDisplayInTree(1);
        $resource_auto_email_report->setIconClass('fi-mail');
        $resource_auto_email_report->setResource('fa_report_automated_email');
        $resource_auto_email_report->setResourceGroup('fa_report_automated_email');
        $resource_auto_email_report->setParent($reportManegment);
        $resource_auto_email_report->setPermission($viewPermissionObj);
        $em->persist($resource_auto_email_report);
        $em->flush();

        $resource_auto_email_report_export_csv = new Resource();
        $resource_auto_email_report_export_csv->setName('Automated email report Export To CSV');
        $resource_auto_email_report_export_csv->setResource('fa_report_automated_email_export_to_csv');
        $resource_auto_email_report_export_csv->setIsMenu(0);
        $resource_auto_email_report_export_csv->setDisplayInTree(1);
        $resource_auto_email_report_export_csv->setParent($resource_auto_email_report);
        $resource_auto_email_report_export_csv->setPermission($viewPermissionObj);
        $em->persist($resource_auto_email_report_export_csv);
        $em->flush();

        $resource_auto_email_report_csv_list = new Resource();
        $resource_auto_email_report_csv_list->setName('Automated email report CSV List');
        $resource_auto_email_report_csv_list->setResource('ajax_fa_report_automated_email_csv_list');
        $resource_auto_email_report_csv_list->setIsMenu(0);
        $resource_auto_email_report_csv_list->setDisplayInTree(1);
        $resource_auto_email_report_csv_list->setParent($resource_auto_email_report);
        $resource_auto_email_report_csv_list->setPermission($viewPermissionObj);
        $em->persist($resource_auto_email_report_csv_list);
        $em->flush();

        $resource_auto_email_report_csv_delete = new Resource();
        $resource_auto_email_report_csv_delete->setName('Automated email report CSV Delete');
        $resource_auto_email_report_csv_delete->setResource('ajax_fa_report_automated_email_csv_delete');
        $resource_auto_email_report_csv_delete->setIsMenu(0);
        $resource_auto_email_report_csv_delete->setDisplayInTree(1);
        $resource_auto_email_report_csv_delete->setParent($resource_auto_email_report);
        $resource_auto_email_report_csv_delete->setPermission($viewPermissionObj);
        $em->persist($resource_auto_email_report_csv_delete);
        $em->flush();

        $resource_auto_email_report_csv_download = new Resource();
        $resource_auto_email_report_csv_download->setName('Automated email report CSV Download');
        $resource_auto_email_report_csv_download->setResource('fa_report_automated_email_download_csv');
        $resource_auto_email_report_csv_download->setIsMenu(0);
        $resource_auto_email_report_csv_download->setDisplayInTree(1);
        $resource_auto_email_report_csv_download->setParent($resource_auto_email_report);
        $resource_auto_email_report_csv_download->setPermission($viewPermissionObj);
        $em->persist($resource_auto_email_report_csv_download);
        $em->flush();

        $contentManegment = new Resource();
        $contentManegment->setName('Content Management');
        $contentManegment->setIsMenu(1);
        $contentManegment->setDisplayInTree(1);
        $contentManegment->setIconClass('fi-text-color');
        $contentManegment->setResourceGroup('lexik_translation_grid');
        $contentManegment->setParent($resource_root);
        $contentManegment->setPermission($viewPermissionObj);
        $em->persist($contentManegment);
        $em->flush();

        $resource_trans = new Resource();
        $resource_trans->setName('Translation');
        $resource_trans->setIsMenu(1);
        $resource_trans->setDisplayInTree(1);
        $resource_trans->setIconClass('fi-social-tumblr');
        $resource_trans->setResourceGroup('lexik_translation_grid');
        $resource_trans->setParent($contentManegment);
        $resource_trans->setPermission($viewPermissionObj);
        $em->persist($resource_trans);
        $em->flush();

        $resource_trans_list = new Resource();
        $resource_trans_list->setName('Translation list');
        $resource_trans_list->setIsMenu(0);
        $resource_trans_list->setDisplayInTree(1);
        $resource_trans_list->setResource('lexik_translation_grid');
        $resource_trans_list->setParent($resource_trans);
        $resource_trans_list->setPermission($viewPermissionObj);
        $em->persist($resource_trans_list);
        $em->flush();

        $resource_trans_add = new Resource();
        $resource_trans_add->setName('Translation add');
        $resource_trans_add->setIsMenu(0);
        $resource_trans_add->setDisplayInTree(1);
        $resource_trans_add->setResource('lexik_translation_new');
        $resource_trans_add->setParent($resource_trans);
        $resource_trans_add->setPermission($createPermissionObj);
        $em->persist($resource_trans_add);
        $em->flush();

        $resource_trans_edit = new Resource();
        $resource_trans_edit->setName('Translation edit');
        $resource_trans_edit->setIsMenu(0);
        $resource_trans_edit->setDisplayInTree(1);
        $resource_trans_edit->setResource('lexik_translation_update');
        $resource_trans_edit->setParent($resource_trans);
        $resource_trans_edit->setPermission($editPermissionObj);
        $em->persist($resource_trans_edit);
        $em->flush();

        $resource_trans_cache_invalidate = new Resource();
        $resource_trans_cache_invalidate->setName('Translation invalidate cache');
        $resource_trans_cache_invalidate->setIsMenu(0);
        $resource_trans_cache_invalidate->setDisplayInTree(1);
        $resource_trans_cache_invalidate->setResource('lexik_translation_invalidate_cache');
        $resource_trans_cache_invalidate->setParent($resource_trans);
        $em->persist($resource_trans_cache_invalidate);
        $em->flush();

        $resource_emailtemplate = new Resource();
        $resource_emailtemplate->setName('Email template');
        $resource_emailtemplate->setIsMenu(1);
        $resource_emailtemplate->setDisplayInTree(1);
        $resource_emailtemplate->setIconClass('fi-mail');
        $resource_emailtemplate->setResourceGroup('email_template_admin');
        $resource_emailtemplate->setParent($contentManegment);
        $resource_emailtemplate->setPermission($viewPermissionObj);
        $em->persist($resource_emailtemplate);
        $em->flush();

        $resource_emailtemplate_list = new Resource();
        $resource_emailtemplate_list->setName('Email template list');
        $resource_emailtemplate_list->setResource('email_template_admin');
        $resource_emailtemplate_list->setIsMenu(1);
        $resource_emailtemplate_list->setDisplayInTree(1);
        $resource_emailtemplate_list->setIconClass('fi-list');
        $resource_emailtemplate_list->setParent($resource_emailtemplate);
        $resource_emailtemplate_list->setPermission($viewPermissionObj);
        $em->persist($resource_emailtemplate_list);
        $em->flush();

        $resource_emailtemplate_add = new Resource();
        $resource_emailtemplate_add->setName('Email template add');
        $resource_emailtemplate_add->setResource('email_template_new_admin');
        $resource_emailtemplate_add->setIsMenu(1);
        $resource_emailtemplate_add->setDisplayInTree(1);
        $resource_emailtemplate_add->setIconClass('fi-plus');
        $resource_emailtemplate_add->setParent($resource_emailtemplate);
        $resource_emailtemplate_add->setPermission($createPermissionObj);
        $em->persist($resource_emailtemplate_add);
        $em->flush();

        $resource_emailtemplate_create = new Resource();
        $resource_emailtemplate_create->setName('Email template create');
        $resource_emailtemplate_create->setResource('email_template_create_admin');
        $resource_emailtemplate_create->setIsMenu(0);
        $resource_emailtemplate_create->setDisplayInTree(0);
        $resource_emailtemplate_create->setParent($resource_emailtemplate_add);
        $resource_emailtemplate_create->setPermission($createPermissionObj);
        $em->persist($resource_emailtemplate_create);
        $em->flush();

        $resource_emailtemplate_edit = new Resource();
        $resource_emailtemplate_edit->setName('Email template edit');
        $resource_emailtemplate_edit->setResource('email_template_edit_admin');
        $resource_emailtemplate_edit->setIsMenu(0);
        $resource_emailtemplate_edit->setDisplayInTree(1);
        $resource_emailtemplate_edit->setParent($resource_emailtemplate);
        $resource_emailtemplate_edit->setPermission($editPermissionObj);
        $em->persist($resource_emailtemplate_edit);
        $em->flush();

        $resource_emailtemplate_update = new Resource();
        $resource_emailtemplate_update->setName('Email template update');
        $resource_emailtemplate_update->setResource('email_template_update_admin');
        $resource_emailtemplate_update->setIsMenu(0);
        $resource_emailtemplate_update->setDisplayInTree(0);
        $resource_emailtemplate_update->setParent($resource_emailtemplate_edit);
        $resource_emailtemplate_update->setPermission($editPermissionObj);
        $em->persist($resource_emailtemplate_update);
        $em->flush();

        $resource_emailtemplate_delete = new Resource();
        $resource_emailtemplate_delete->setName('Email template delete');
        $resource_emailtemplate_delete->setResource('email_template_delete_admin');
        $resource_emailtemplate_delete->setIsMenu(0);
        $resource_emailtemplate_delete->setDisplayInTree(1);
        $resource_emailtemplate_delete->setParent($resource_emailtemplate);
        $resource_emailtemplate_delete->setPermission($deletePermissionObj);
        $em->persist($resource_emailtemplate_delete);
        $em->flush();

        $resource_emailtemplate_preview = new Resource();
        $resource_emailtemplate_preview->setName('Email template preview');
        $resource_emailtemplate_preview->setResource('email_template_preview_admin');
        $resource_emailtemplate_preview->setIsMenu(0);
        $resource_emailtemplate_preview->setDisplayInTree(1);
        $resource_emailtemplate_preview->setParent($resource_emailtemplate);
        $resource_emailtemplate_preview->setPermission($viewPermissionObj);
        $em->persist($resource_emailtemplate_preview);
        $em->flush();

        $resource_emailtemplate_schedule = new Resource();
        $resource_emailtemplate_schedule->setName('Email template schedule');
        $resource_emailtemplate_schedule->setResource('email_template_schedule_admin');
        $resource_emailtemplate_schedule->setIsMenu(0);
        $resource_emailtemplate_schedule->setDisplayInTree(1);
        $resource_emailtemplate_schedule->setParent($resource_emailtemplate);
        $resource_emailtemplate_schedule->setPermission($editPermissionObj);
        $em->persist($resource_emailtemplate_schedule);
        $em->flush();

        $resource_header_image = new Resource();
        $resource_header_image->setName('Homepage Header Image');
        $resource_header_image->setIsMenu(1);
        $resource_header_image->setDisplayInTree(1);
        $resource_header_image->setIconClass('fi-camera');
        $resource_header_image->setResourceGroup('header_image_admin');
        $resource_header_image->setParent($contentManegment);
        $resource_header_image->setPermission($viewPermissionObj);
        $em->persist($resource_header_image);
        $em->flush();

        $resource_header_image_list = new Resource();
        $resource_header_image_list->setName('Homepage Header Image list');
        $resource_header_image_list->setResource('header_image_admin');
        $resource_header_image_list->setIsMenu(1);
        $resource_header_image_list->setDisplayInTree(1);
        $resource_header_image_list->setIconClass('fi-list');
        $resource_header_image_list->setParent($resource_header_image);
        $resource_header_image_list->setPermission($viewPermissionObj);
        $em->persist($resource_header_image_list);
        $em->flush();

        $resource_header_image_add = new Resource();
        $resource_header_image_add->setName('Homepage Header Image add');
        $resource_header_image_add->setResource('header_image_new_admin');
        $resource_header_image_add->setIsMenu(1);
        $resource_header_image_add->setDisplayInTree(1);
        $resource_header_image_add->setIconClass('fi-plus');
        $resource_header_image_add->setParent($resource_header_image);
        $resource_header_image_add->setPermission($createPermissionObj);
        $em->persist($resource_header_image_add);
        $em->flush();

        $resource_header_image_create = new Resource();
        $resource_header_image_create->setName('Homepage Header Image create');
        $resource_header_image_create->setResource('header_image_create_admin');
        $resource_header_image_create->setIsMenu(0);
        $resource_header_image_create->setDisplayInTree(0);
        $resource_header_image_create->setParent($resource_header_image_add);
        $resource_header_image_create->setPermission($createPermissionObj);
        $em->persist($resource_header_image_create);
        $em->flush();

        $resource_header_image_edit = new Resource();
        $resource_header_image_edit->setName('Homepage Header Image edit');
        $resource_header_image_edit->setResource('header_image_edit_admin');
        $resource_header_image_edit->setIsMenu(0);
        $resource_header_image_edit->setDisplayInTree(1);
        $resource_header_image_edit->setParent($resource_header_image);
        $resource_header_image_edit->setPermission($editPermissionObj);
        $em->persist($resource_header_image_edit);
        $em->flush();

        $resource_header_image_update = new Resource();
        $resource_header_image_update->setName('Homepage Header Image update');
        $resource_header_image_update->setResource('header_image_update_admin');
        $resource_header_image_update->setIsMenu(0);
        $resource_header_image_update->setDisplayInTree(0);
        $resource_header_image_update->setParent($resource_header_image_edit);
        $resource_header_image_update->setPermission($editPermissionObj);
        $em->persist($resource_header_image_update);
        $em->flush();

        $resource_header_image_delete = new Resource();
        $resource_header_image_delete->setName('Homepage Header Image delete');
        $resource_header_image_delete->setResource('header_image_delete_admin');
        $resource_header_image_delete->setIsMenu(0);
        $resource_header_image_delete->setDisplayInTree(1);
        $resource_header_image_delete->setParent($resource_header_image);
        $resource_header_image_delete->setPermission($deletePermissionObj);
        $em->persist($resource_header_image_delete);
        $em->flush();

        $resource_home_popular_image = new Resource();
        $resource_home_popular_image->setName('Homepage What\'s Popular');
        $resource_home_popular_image->setIsMenu(1);
        $resource_home_popular_image->setDisplayInTree(1);
        $resource_home_popular_image->setIconClass('fi-camera');
        $resource_home_popular_image->setResourceGroup('home_popular_image_admin');
        $resource_home_popular_image->setParent($contentManegment);
        $resource_home_popular_image->setPermission($viewPermissionObj);
        $em->persist($resource_home_popular_image);
        $em->flush();

        $resource_home_popular_image_list = new Resource();
        $resource_home_popular_image_list->setName('Homepage What\'s Popular list');
        $resource_home_popular_image_list->setResource('home_popular_image_admin');
        $resource_home_popular_image_list->setIsMenu(1);
        $resource_home_popular_image_list->setDisplayInTree(1);
        $resource_home_popular_image_list->setIconClass('fi-list');
        $resource_home_popular_image_list->setParent($resource_home_popular_image);
        $resource_home_popular_image_list->setPermission($viewPermissionObj);
        $em->persist($resource_home_popular_image_list);
        $em->flush();

        $resource_home_popular_image_add = new Resource();
        $resource_home_popular_image_add->setName('Homepage What\'s Popular add');
        $resource_home_popular_image_add->setResource('home_popular_image_new_admin');
        $resource_home_popular_image_add->setIsMenu(1);
        $resource_home_popular_image_add->setDisplayInTree(1);
        $resource_home_popular_image_add->setIconClass('fi-plus');
        $resource_home_popular_image_add->setParent($resource_home_popular_image);
        $resource_home_popular_image_add->setPermission($createPermissionObj);
        $em->persist($resource_home_popular_image_add);
        $em->flush();

        $resource_home_popular_image_create = new Resource();
        $resource_home_popular_image_create->setName('Homepage What\'s Popular create');
        $resource_home_popular_image_create->setResource('home_popular_image_create_admin');
        $resource_home_popular_image_create->setIsMenu(0);
        $resource_home_popular_image_create->setDisplayInTree(0);
        $resource_home_popular_image_create->setParent($resource_home_popular_image_add);
        $resource_home_popular_image_create->setPermission($createPermissionObj);
        $em->persist($resource_home_popular_image_create);
        $em->flush();

        $resource_home_popular_image_edit = new Resource();
        $resource_home_popular_image_edit->setName('Homepage What\'s Popular edit');
        $resource_home_popular_image_edit->setResource('home_popular_image_edit_admin');
        $resource_home_popular_image_edit->setIsMenu(0);
        $resource_home_popular_image_edit->setDisplayInTree(1);
        $resource_home_popular_image_edit->setParent($resource_home_popular_image);
        $resource_home_popular_image_edit->setPermission($editPermissionObj);
        $em->persist($resource_home_popular_image_edit);
        $em->flush();

        $resource_home_popular_image_update = new Resource();
        $resource_home_popular_image_update->setName('Homepage What\'s Popular update');
        $resource_home_popular_image_update->setResource('home_popular_image_update_admin');
        $resource_home_popular_image_update->setIsMenu(0);
        $resource_home_popular_image_update->setDisplayInTree(0);
        $resource_home_popular_image_update->setParent($resource_home_popular_image_edit);
        $resource_home_popular_image_update->setPermission($editPermissionObj);
        $em->persist($resource_home_popular_image_update);
        $em->flush();

        $resource_home_popular_image_delete = new Resource();
        $resource_home_popular_image_delete->setName('Homepage What\'s Popular delete');
        $resource_home_popular_image_delete->setResource('home_popular_image_delete_admin');
        $resource_home_popular_image_delete->setIsMenu(0);
        $resource_home_popular_image_delete->setDisplayInTree(1);
        $resource_home_popular_image_delete->setParent($resource_home_popular_image);
        $resource_home_popular_image_delete->setPermission($deletePermissionObj);
        $em->persist($resource_home_popular_image_delete);
        $em->flush();

        $resource_landing_page = new Resource();
        $resource_landing_page->setName('Landing page');
        $resource_landing_page->setIsMenu(1);
        $resource_landing_page->setDisplayInTree(1);
        $resource_landing_page->setIconClass('fi-page');
        $resource_landing_page->setResourceGroup('landing_page_admin');
        $resource_landing_page->setParent($contentManegment);
        $resource_landing_page->setPermission($viewPermissionObj);
        $em->persist($resource_landing_page);
        $em->flush();

        $resource_landing_page_list = new Resource();
        $resource_landing_page_list->setName('Landing page list');
        $resource_landing_page_list->setResource('landing_page_admin');
        $resource_landing_page_list->setIsMenu(1);
        $resource_landing_page_list->setDisplayInTree(1);
        $resource_landing_page_list->setIconClass('fi-list');
        $resource_landing_page_list->setParent($resource_landing_page);
        $resource_landing_page_list->setPermission($viewPermissionObj);
        $em->persist($resource_landing_page_list);
        $em->flush();

        $resource_landing_page_add = new Resource();
        $resource_landing_page_add->setName('Landing page add');
        $resource_landing_page_add->setResource('landing_page_new_admin');
        $resource_landing_page_add->setIsMenu(1);
        $resource_landing_page_add->setDisplayInTree(1);
        $resource_landing_page_add->setIconClass('fi-plus');
        $resource_landing_page_add->setParent($resource_landing_page);
        $resource_landing_page_add->setPermission($createPermissionObj);
        $em->persist($resource_landing_page_add);
        $em->flush();

        $resource_landing_page_create = new Resource();
        $resource_landing_page_create->setName('Landing page create');
        $resource_landing_page_create->setResource('landing_page_create_admin');
        $resource_landing_page_create->setIsMenu(0);
        $resource_landing_page_create->setDisplayInTree(0);
        $resource_landing_page_create->setParent($resource_landing_page_add);
        $resource_landing_page_create->setPermission($createPermissionObj);
        $em->persist($resource_landing_page_create);
        $em->flush();

        $resource_landing_page_edit = new Resource();
        $resource_landing_page_edit->setName('Landing page edit');
        $resource_landing_page_edit->setResource('landing_page_edit_admin');
        $resource_landing_page_edit->setIsMenu(0);
        $resource_landing_page_edit->setDisplayInTree(1);
        $resource_landing_page_edit->setParent($resource_landing_page);
        $resource_landing_page_edit->setPermission($editPermissionObj);
        $em->persist($resource_landing_page_edit);
        $em->flush();

        $resource_landing_page_update = new Resource();
        $resource_landing_page_update->setName('Landing page update');
        $resource_landing_page_update->setResource('landing_page_update_admin');
        $resource_landing_page_update->setIsMenu(0);
        $resource_landing_page_update->setDisplayInTree(0);
        $resource_landing_page_update->setParent($resource_landing_page_edit);
        $resource_landing_page_update->setPermission($editPermissionObj);
        $em->persist($resource_landing_page_update);
        $em->flush();

        $resource_landing_page_delete = new Resource();
        $resource_landing_page_delete->setName('Landing page delete');
        $resource_landing_page_delete->setResource('landing_page_delete_admin');
        $resource_landing_page_delete->setIsMenu(0);
        $resource_landing_page_delete->setDisplayInTree(1);
        $resource_landing_page_delete->setParent($resource_landing_page);
        $resource_landing_page_delete->setPermission($deletePermissionObj);
        $em->persist($resource_landing_page_delete);
        $em->flush();

        $resource_static_page = new Resource();
        $resource_static_page->setName('Static page');
        $resource_static_page->setIsMenu(1);
        $resource_static_page->setDisplayInTree(1);
        $resource_static_page->setIconClass('fi-page');
        $resource_static_page->setResourceGroup('static_page_admin');
        $resource_static_page->setParent($contentManegment);
        $resource_static_page->setPermission($viewPermissionObj);
        $em->persist($resource_static_page);
        $em->flush();

        $resource_static_page_list = new Resource();
        $resource_static_page_list->setName('Static page list');
        $resource_static_page_list->setResource('static_page_admin');
        $resource_static_page_list->setIsMenu(1);
        $resource_static_page_list->setDisplayInTree(1);
        $resource_static_page_list->setIconClass('fi-list');
        $resource_static_page_list->setParent($resource_static_page);
        $resource_static_page_list->setPermission($viewPermissionObj);
        $em->persist($resource_static_page_list);
        $em->flush();

        $resource_static_page_add = new Resource();
        $resource_static_page_add->setName('Static page add');
        $resource_static_page_add->setResource('static_page_new_admin');
        $resource_static_page_add->setIsMenu(1);
        $resource_static_page_add->setDisplayInTree(1);
        $resource_static_page_add->setIconClass('fi-plus');
        $resource_static_page_add->setParent($resource_static_page);
        $resource_static_page_add->setPermission($createPermissionObj);
        $em->persist($resource_static_page_add);
        $em->flush();

        $resource_static_page_create = new Resource();
        $resource_static_page_create->setName('Static page create');
        $resource_static_page_create->setResource('static_page_create_admin');
        $resource_static_page_create->setIsMenu(0);
        $resource_static_page_create->setDisplayInTree(0);
        $resource_static_page_create->setParent($resource_static_page_add);
        $resource_static_page_create->setPermission($createPermissionObj);
        $em->persist($resource_static_page_create);
        $em->flush();

        $resource_static_page_edit = new Resource();
        $resource_static_page_edit->setName('Static page edit');
        $resource_static_page_edit->setResource('static_page_edit_admin');
        $resource_static_page_edit->setIsMenu(0);
        $resource_static_page_edit->setDisplayInTree(1);
        $resource_static_page_edit->setParent($resource_static_page);
        $resource_static_page_edit->setPermission($editPermissionObj);
        $em->persist($resource_static_page_edit);
        $em->flush();

        $resource_static_page_update = new Resource();
        $resource_static_page_update->setName('Static page update');
        $resource_static_page_update->setResource('static_page_update_admin');
        $resource_static_page_update->setIsMenu(0);
        $resource_static_page_update->setDisplayInTree(0);
        $resource_static_page_update->setParent($resource_static_page_edit);
        $resource_static_page_update->setPermission($editPermissionObj);
        $em->persist($resource_static_page_update);
        $em->flush();

        $resource_static_page_delete = new Resource();
        $resource_static_page_delete->setName('Static page delete');
        $resource_static_page_delete->setResource('static_page_delete_admin');
        $resource_static_page_delete->setIsMenu(0);
        $resource_static_page_delete->setDisplayInTree(1);
        $resource_static_page_delete->setParent($resource_static_page);
        $resource_static_page_delete->setPermission($deletePermissionObj);
        $em->persist($resource_static_page_delete);
        $em->flush();

        $resource_static_block = new Resource();
        $resource_static_block->setName('Static block');
        $resource_static_block->setIsMenu(1);
        $resource_static_block->setDisplayInTree(1);
        $resource_static_block->setIconClass('fi-page');
        $resource_static_block->setResourceGroup('static_block_admin');
        $resource_static_block->setParent($contentManegment);
        $resource_static_block->setPermission($viewPermissionObj);
        $em->persist($resource_static_block);
        $em->flush();

        $resource_static_block_list = new Resource();
        $resource_static_block_list->setName('Static block list');
        $resource_static_block_list->setResource('static_block_admin');
        $resource_static_block_list->setIsMenu(1);
        $resource_static_block_list->setDisplayInTree(1);
        $resource_static_block_list->setIconClass('fi-list');
        $resource_static_block_list->setParent($resource_static_block);
        $resource_static_block_list->setPermission($viewPermissionObj);
        $em->persist($resource_static_block_list);
        $em->flush();

        $resource_static_block_add = new Resource();
        $resource_static_block_add->setName('Static block add');
        $resource_static_block_add->setResource('static_block_new_admin');
        $resource_static_block_add->setIsMenu(1);
        $resource_static_block_add->setDisplayInTree(1);
        $resource_static_block_add->setIconClass('fi-plus');
        $resource_static_block_add->setParent($resource_static_block);
        $resource_static_block_add->setPermission($createPermissionObj);
        $em->persist($resource_static_block_add);
        $em->flush();

        $resource_static_block_create = new Resource();
        $resource_static_block_create->setName('Static block create');
        $resource_static_block_create->setResource('static_block_create_admin');
        $resource_static_block_create->setIsMenu(0);
        $resource_static_block_create->setDisplayInTree(0);
        $resource_static_block_create->setParent($resource_static_block_add);
        $resource_static_block_create->setPermission($createPermissionObj);
        $em->persist($resource_static_block_create);
        $em->flush();

        $resource_static_block_edit = new Resource();
        $resource_static_block_edit->setName('Static block edit');
        $resource_static_block_edit->setResource('static_block_edit_admin');
        $resource_static_block_edit->setIsMenu(0);
        $resource_static_block_edit->setDisplayInTree(1);
        $resource_static_block_edit->setParent($resource_static_block);
        $resource_static_block_edit->setPermission($editPermissionObj);
        $em->persist($resource_static_block_edit);
        $em->flush();

        $resource_static_block_update = new Resource();
        $resource_static_block_update->setName('Static block update');
        $resource_static_block_update->setResource('static_block_update_admin');
        $resource_static_block_update->setIsMenu(0);
        $resource_static_block_update->setDisplayInTree(0);
        $resource_static_block_update->setParent($resource_static_block_edit);
        $resource_static_block_update->setPermission($editPermissionObj);
        $em->persist($resource_static_block_update);
        $em->flush();

        $resource_static_block_delete = new Resource();
        $resource_static_block_delete->setName('Static block delete');
        $resource_static_block_delete->setResource('static_block_delete_admin');
        $resource_static_block_delete->setIsMenu(0);
        $resource_static_block_delete->setDisplayInTree(1);
        $resource_static_block_delete->setParent($resource_static_block);
        $resource_static_block_delete->setPermission($deletePermissionObj);
        $em->persist($resource_static_block_delete);
        $em->flush();

        $resource_banner = new Resource();
        $resource_banner->setName('Banners');
        $resource_banner->setIsMenu(1);
        $resource_banner->setDisplayInTree(1);
        $resource_banner->setIconClass('fi-page');
        $resource_banner->setResourceGroup('banner_admin');
        $resource_banner->setParent($contentManegment);
        $resource_banner->setPermission($viewPermissionObj);
        $em->persist($resource_banner);
        $em->flush();

        $resource_banner_list = new Resource();
        $resource_banner_list->setName('Banners list');
        $resource_banner_list->setResource('banner_admin');
        $resource_banner_list->setIsMenu(1);
        $resource_banner_list->setDisplayInTree(1);
        $resource_banner_list->setIconClass('fi-list');
        $resource_banner_list->setParent($resource_banner);
        $resource_banner_list->setPermission($viewPermissionObj);
        $em->persist($resource_banner_list);
        $em->flush();

        $resource_banner_add = new Resource();
        $resource_banner_add->setName('Banner add');
        $resource_banner_add->setResource('banner_new_admin');
        $resource_banner_add->setIsMenu(1);
        $resource_banner_add->setDisplayInTree(1);
        $resource_banner_add->setIconClass('fi-plus');
        $resource_banner_add->setParent($resource_banner);
        $resource_banner_add->setPermission($createPermissionObj);
        $em->persist($resource_banner_add);
        $em->flush();

        $resource_banner_create = new Resource();
        $resource_banner_create->setName('Banner create');
        $resource_banner_create->setResource('banner_create_admin');
        $resource_banner_create->setIsMenu(0);
        $resource_banner_create->setDisplayInTree(0);
        $resource_banner_create->setParent($resource_banner_add);
        $resource_banner_create->setPermission($createPermissionObj);
        $em->persist($resource_banner_create);
        $em->flush();

        $resource_banner_edit = new Resource();
        $resource_banner_edit->setName('Banner edit');
        $resource_banner_edit->setResource('banner_edit_admin');
        $resource_banner_edit->setIsMenu(0);
        $resource_banner_edit->setDisplayInTree(1);
        $resource_banner_edit->setParent($resource_banner);
        $resource_banner_edit->setPermission($editPermissionObj);
        $em->persist($resource_banner_edit);
        $em->flush();

        $resource_banner_update = new Resource();
        $resource_banner_update->setName('Banner update');
        $resource_banner_update->setResource('banner_update_admin');
        $resource_banner_update->setIsMenu(0);
        $resource_banner_update->setDisplayInTree(0);
        $resource_banner_update->setParent($resource_banner_edit);
        $resource_banner_update->setPermission($editPermissionObj);
        $em->persist($resource_banner_update);
        $em->flush();

        $resource_banner_delete = new Resource();
        $resource_banner_delete->setName('Banner delete');
        $resource_banner_delete->setResource('banner_delete_admin');
        $resource_banner_delete->setIsMenu(0);
        $resource_banner_delete->setDisplayInTree(1);
        $resource_banner_delete->setParent($resource_banner);
        $resource_banner_delete->setPermission($deletePermissionObj);
        $em->persist($resource_banner_delete);
        $em->flush();

        $notification_message_list = new Resource();
        $notification_message_list->setName('List notification messages');
        $notification_message_list->setResource('notification_message_admin');
        $notification_message_list->setIsMenu(1);
        $notification_message_list->setDisplayInTree(1);
        $notification_message_list->setIconClass('fi-list');
        $notification_message_list->setParent($contentManegment);
        $notification_message_list->setPermission($viewPermissionObj);
        $em->persist($notification_message_list);
        $em->flush();

        $notification_message_edit = new Resource();
        $notification_message_edit->setName('Edit notification message');
        $notification_message_edit->setResource('notification_message_edit_admin');
        $notification_message_edit->setIsMenu(0);
        $notification_message_edit->setDisplayInTree(1);
        $notification_message_edit->setParent($contentManegment);
        $notification_message_edit->setPermission($editPermissionObj);
        $em->persist($notification_message_edit);
        $em->flush();

        $notification_message_update = new Resource();
        $notification_message_update->setName('Update notification message');
        $notification_message_update->setResource('notification_message_update_admin');
        $notification_message_update->setIsMenu(0);
        $notification_message_update->setDisplayInTree(0);
        $notification_message_update->setParent($notification_message_edit);
        $notification_message_update->setPermission($editPermissionObj);
        $em->persist($notification_message_update);
        $em->flush();

        $newsletter = new Resource();
        $newsletter->setName('Newsletter');
        $newsletter->setIsMenu(1);
        $newsletter->setDisplayInTree(1);
        $newsletter->setIconClass('fi-mail');
        $newsletter->setResourceGroup('dotmailer_admin');
        $newsletter->setParent($resource_root);
        $newsletter->setPermission($viewPermissionObj);
        $em->persist($newsletter);
        $em->flush();

        $newsletter_email_search = new Resource();
        $newsletter_email_search->setName('Create marketing filter');
        $newsletter_email_search->setResource('dotmailer_admin');
        $newsletter_email_search->setIsMenu(1);
        $newsletter_email_search->setDisplayInTree(1);
        $newsletter_email_search->setIconClass('fi-plus');
        $newsletter_email_search->setParent($newsletter);
        $newsletter_email_search->setPermission($viewPermissionObj);
        $em->persist($newsletter_email_search);
        $em->flush();

        $newsletter_email_list = new Resource();
        $newsletter_email_list->setName('List marketing emails');
        $newsletter_email_list->setResource('dotmailer_list_admin');
        $newsletter_email_list->setIsMenu(0);
        $newsletter_email_list->setDisplayInTree(1);
        $newsletter_email_list->setIconClass('fi-list');
        $newsletter_email_list->setParent($newsletter);
        $newsletter_email_list->setPermission($viewPermissionObj);
        $em->persist($newsletter_email_list);
        $em->flush();

        $newsletter_filter_approve = new Resource();
        $newsletter_filter_approve->setName('Approve/review marketing filter');
        $newsletter_filter_approve->setResource('dotmailer_filter_admin');
        $newsletter_filter_approve->setIsMenu(1);
        $newsletter_filter_approve->setDisplayInTree(1);
        $newsletter_filter_approve->setIconClass('fi-list');
        $newsletter_filter_approve->setParent($newsletter);
        $newsletter_filter_approve->setPermission($viewPermissionObj);
        $em->persist($newsletter_filter_approve);
        $em->flush();

        $newsletter_filter_add = new Resource();
        $newsletter_filter_add->setName('Marketing filter add');
        $newsletter_filter_add->setResource('dotmailer_filter_new_admin');
        $newsletter_filter_add->setIsMenu(0);
        $newsletter_filter_add->setDisplayInTree(1);
        $newsletter_filter_add->setIconClass('fi-plus');
        $newsletter_filter_add->setParent($newsletter);
        $newsletter_filter_add->setPermission($viewPermissionObj);
        $em->persist($newsletter_filter_add);
        $em->flush();

        $newsletter_filter_create = new Resource();
        $newsletter_filter_create->setName('Marketing filter create');
        $newsletter_filter_create->setResource('dotmailer_filter_create_admin');
        $newsletter_filter_create->setIsMenu(0);
        $newsletter_filter_create->setDisplayInTree(0);
        $newsletter_filter_create->setParent($newsletter_filter_add);
        $newsletter_filter_create->setPermission($createPermissionObj);
        $em->persist($newsletter_filter_create);
        $em->flush();

        $newsletter_filter_edit = new Resource();
        $newsletter_filter_edit->setName('Marketing filter edit');
        $newsletter_filter_edit->setResource('dotmailer_filter_edit_admin');
        $newsletter_filter_edit->setIsMenu(0);
        $newsletter_filter_edit->setDisplayInTree(1);
        $newsletter_filter_edit->setParent($newsletter);
        $newsletter_filter_edit->setPermission($editPermissionObj);
        $em->persist($newsletter_filter_edit);
        $em->flush();

        $newsletter_filter_update = new Resource();
        $newsletter_filter_update->setName('Marketing filter update');
        $newsletter_filter_update->setResource('dotmailer_filter_update_admin');
        $newsletter_filter_update->setIsMenu(0);
        $newsletter_filter_update->setDisplayInTree(0);
        $newsletter_filter_update->setParent($newsletter_filter_edit);
        $newsletter_filter_update->setPermission($editPermissionObj);
        $em->persist($newsletter_filter_update);
        $em->flush();

        $newsletter_filter_delete = new Resource();
        $newsletter_filter_delete->setName('Marketing filter delete');
        $newsletter_filter_delete->setResource('dotmailer_filter_delete_admin');
        $newsletter_filter_delete->setIsMenu(0);
        $newsletter_filter_delete->setDisplayInTree(1);
        $newsletter_filter_delete->setParent($newsletter);
        $newsletter_filter_delete->setPermission($deletePermissionObj);
        $em->persist($newsletter_filter_delete);
        $em->flush();

        $newsletter_filter_approve = new Resource();
        $newsletter_filter_approve->setName('Marketing filter approve');
        $newsletter_filter_approve->setResource('dotmailer_filter_approve_admin');
        $newsletter_filter_approve->setIsMenu(0);
        $newsletter_filter_approve->setDisplayInTree(1);
        $newsletter_filter_approve->setParent($newsletter);
        $newsletter_filter_approve->setPermission($editPermissionObj);
        $em->persist($newsletter_filter_approve);
        $em->flush();

        $newsletter_filter_export = new Resource();
        $newsletter_filter_export->setName('Marketing filter export');
        $newsletter_filter_export->setResource('dotmailer_filter_export_admin');
        $newsletter_filter_export->setIsMenu(0);
        $newsletter_filter_export->setDisplayInTree(1);
        $newsletter_filter_export->setParent($newsletter);
        $newsletter_filter_export->setPermission($editPermissionObj);
        $em->persist($newsletter_filter_export);
        $em->flush();

        $feedAggregator  = new Resource();
        $feedAggregator ->setName('Feed Aggregator');
        $feedAggregator ->setIsMenu(1);
        $feedAggregator ->setDisplayInTree(1);
        $feedAggregator ->setIconClass('fi-mail');
        $feedAggregator ->setResourceGroup('ad_feed_log_admin');
        $feedAggregator ->setParent($resource_root);
        $feedAggregator ->setPermission($viewPermissionObj);
        $em->persist($feedAggregator );
        $em->flush();

        $adFeedLog = new Resource();
        $adFeedLog->setName('Feed Log');
        $adFeedLog->setResource('ad_feed_log_admin');
        $adFeedLog->setIsMenu(1);
        $adFeedLog->setDisplayInTree(1);
        $adFeedLog->setIconClass('fi-list');
        $adFeedLog->setParent($feedAggregator);
        $adFeedLog->setPermission($viewPermissionObj);
        $em->persist($adFeedLog);
        $em->flush();

        $adFeedLog_delete = new Resource();
        $adFeedLog_delete->setName('Feed Log delete');
        $adFeedLog_delete->setResource('ad_feed_log_delete_admin');
        $adFeedLog_delete->setIsMenu(0);
        $adFeedLog_delete->setDisplayInTree(1);
        $adFeedLog_delete->setParent($adFeedLog);
        $adFeedLog_delete->setPermission($deletePermissionObj);
        $em->persist($adFeedLog_delete);
        $em->flush();

        $adFeedLog_show = new Resource();
        $adFeedLog_show->setName('Feed Log show');
        $adFeedLog_show->setResource('ad_feed_log_show_admin');
        $adFeedLog_show->setIsMenu(0);
        $adFeedLog_show->setDisplayInTree(1);
        $adFeedLog_show->setParent($adFeedLog);
        $adFeedLog_show->setPermission($deletePermissionObj);
        $em->persist($adFeedLog_show);
        $em->flush();

        $adFeedMapping = new Resource();
        $adFeedMapping->setName('Feed Mapping');
        $adFeedMapping->setResource('ad_feed_mapping_admin');
        $adFeedMapping->setIsMenu(1);
        $adFeedMapping->setDisplayInTree(1);
        $adFeedMapping->setIconClass('fi-list');
        $adFeedMapping->setParent($feedAggregator);
        $adFeedMapping->setPermission($viewPermissionObj);
        $em->persist($adFeedMapping);
        $em->flush();

        $adFeedMapping_edit = new Resource();
        $adFeedMapping_edit->setName('Feed Mapping edit');
        $adFeedMapping_edit->setResource('ad_feed_mapping_edit_admin');
        $adFeedMapping_edit->setIsMenu(0);
        $adFeedMapping_edit->setDisplayInTree(1);
        $adFeedMapping_edit->setParent($adFeedMapping);
        $adFeedMapping_edit->setPermission($editPermissionObj);
        $em->persist($adFeedMapping_edit);
        $em->flush();

        $adFeedMapping_update = new Resource();
        $adFeedMapping_update->setName('Feed Mapping update');
        $adFeedMapping_update->setResource('ad_feed_mapping_update_admin');
        $adFeedMapping_update->setIsMenu(0);
        $adFeedMapping_update->setDisplayInTree(0);
        $adFeedMapping_update->setParent($adFeedMapping_edit);
        $adFeedMapping_update->setPermission($editPermissionObj);
        $em->persist($adFeedMapping_update);
        $em->flush();

        $adFeedMapping_delete = new Resource();
        $adFeedMapping_delete->setName('Feed Mapping delete');
        $adFeedMapping_delete->setResource('ad_feed_mapping_delete_admin');
        $adFeedMapping_delete->setIsMenu(0);
        $adFeedMapping_delete->setDisplayInTree(1);
        $adFeedMapping_delete->setParent($adFeedMapping);
        $adFeedMapping_delete->setPermission($deletePermissionObj);
        $em->persist($adFeedMapping_delete);
        $em->flush();

        $cacheManagement = new Resource();
        $cacheManagement->setName('Cache Management');
        $cacheManagement->setIsMenu(1);
        $cacheManagement->setDisplayInTree(1);
        $cacheManagement->setIconClass('fi-asterisk');
        $cacheManagement->setResourceGroup('cache_admin');
        $cacheManagement->setParent($resource_root);
        $cacheManagement->setPermission($viewPermissionObj);
        $em->persist($cacheManagement);
        $em->flush();

        $resource_cache = new Resource();
        $resource_cache->setName('Cache');
        $resource_cache->setIsMenu(1);
        $resource_cache->setDisplayInTree(1);
        $resource_cache->setIconClass('fi-asterisk');
        $resource_cache->setResourceGroup('cache_admin');
        $resource_cache->setParent($cacheManagement);
        $resource_cache->setPermission($viewPermissionObj);
        $em->persist($resource_cache);
        $em->flush();

        $resource_cache_list = new Resource();
        $resource_cache_list->setName('Cache list');
        $resource_cache_list->setResource('cache_admin');
        $resource_cache_list->setIsMenu(1);
        $resource_cache_list->setIconClass('fi-list');
        $resource_cache_list->setDisplayInTree(1);
        $resource_cache_list->setParent($resource_cache);
        $resource_cache_list->setPermission($createPermissionObj);
        $em->persist($resource_cache_list);
        $em->flush();

        $resource_cache_generate = new Resource();
        $resource_cache_generate->setName('Generate cache');
        $resource_cache_generate->setResource('cache_generate_admin');
        $resource_cache_generate->setIsMenu(0);
        $resource_cache_generate->setDisplayInTree(1);
        $resource_cache_generate->setParent($resource_cache);
        $resource_cache_generate->setPermission($createPermissionObj);
        $em->persist($resource_cache_generate);
        $em->flush();

        $resource_cache_flush = new Resource();
        $resource_cache_flush->setName('Flush cache');
        $resource_cache_flush->setResource('cache_clear_admin');
        $resource_cache_flush->setIsMenu(0);
        $resource_cache_flush->setDisplayInTree(1);
        $resource_cache_flush->setParent($resource_cache);
        $resource_cache_flush->setPermission($deletePermissionObj);
        $em->persist($resource_cache_flush);
        $em->flush();
    }

    /**
     * Get order of fixture.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}
