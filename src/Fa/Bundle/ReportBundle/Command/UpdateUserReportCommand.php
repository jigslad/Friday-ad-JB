<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\UserSiteViewCounterRepository;

/**
 * This command is used to update user report statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserReportCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * History entity manager
     *
     * @var object
     */
    private $historyEntityManager;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * History db name
     *
     * @var object
     */
    private $historyDbName;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:user-report')
        ->setDescription("Update user report statistics.")
        ->addArgument('action', InputArgument::OPTIONAL, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('type', null, InputOption::VALUE_REQUIRED, 'ad or user or both', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update user report statistics.

Command:
 - php app/console fa:update:user-report all --type="both"
 - php app/console fa:update:user-report beforeoneday --type="user"
 - php app/console fa:update:user-report beforeoneday --type="ad"
 - php app/console fa:update:user-report --date="2015-04-28" --type="user"
 - php app/console fa:update:user-report --date="2015-04-28" --type="ad"
EOF
        );
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set entity manager.
        $this->entityManager        = $this->getContainer()->get('doctrine')->getManager();
        $this->historyEntityManager = $this->getContainer()->get('doctrine')->getManager('history');
        $this->historyDbName        = $this->getContainer()->getParameter('database_name_history');
        $this->mainDbName           = $this->getContainer()->getParameter('database_name');


        //get arguments passed in command
        $offset = $input->getOption('offset');
        $type   = $input->getOption('type');
        $date   = $input->getOption('date');
        $action = $input->getArgument('action');

        if (!in_array($type, array('user', 'ad', 'both'))) {
            $output->writeln('Invalid option type, it must be one of ad, user or both', true);
        } elseif ($action == 'all' && $type == 'both') {
            $userTableName       = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
            $adTableName         = $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
            $userReportTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReport')->getTableName();
            $start_time          = time();

            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);

            $output->writeln('Truncating user_report records...', true);
            $this->executeRawQuery('TRUNCATE TABLE '.$this->historyDbName.'.'.$userReportTableName.';', $this->historyEntityManager);

            $output->writeln('Copying user records to history DB...', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportTableName.' (user_id, role_id, username, name, business_name, phone, email, is_active, signup_date, postcode, town_id, is_facebook_verified, is_paypal_vefiried, business_category_id, created_at) SELECT id, role_id, username, concat_ws(" ", first_name, last_name), business_name, phone, email, is_active, created_at, zip, town_id, is_facebook_verified, is_paypal_vefiried, business_category_id, '.time().' FROM '.$this->mainDbName.'.'.$userTableName.';', $this->historyEntityManager);

            $output->writeln('Updating first_paa to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.first_paa = (select a.created_at FROM '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id ORDER BY a.id asc LIMIT 1);', $this->historyEntityManager);

            $output->writeln('Updating last_paa to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.last_paa = (select a.created_at FROM '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id ORDER BY a.id desc LIMIT 1);', $this->historyEntityManager);

            $output->writeln('Updating total_ad to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.total_ad = (select count(a.id) FROM '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id);', $this->historyEntityManager);

            $output->writeln('Updating total_active_ad to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.total_active_ad = (select count(a.id) FROM '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.');', $this->historyEntityManager);

            $output->writeln('Updating last_paa to user....', true);
            $this->executeRawQuery('UPDATE '.$this->mainDbName.'.'.$userTableName.' u SET u.last_paa = (select a.created_at from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = u.id ORDER BY a.id desc LIMIT 1);', $this->entityManager);

            $output->writeln('Updating total_ad to user....', true);
            $this->executeRawQuery('UPDATE '.$this->mainDbName.'.'.$userTableName.' u SET u.total_ad = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = u.id);', $this->entityManager);

            $output->writeln('Updating last_paa_expires_at to user....', true);
            $this->executeRawQuery('UPDATE '.$this->mainDbName.'.'.$userTableName.' u SET u.last_paa_expires_at = (select a.expires_at from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = u.id ORDER BY a.expires_at desc LIMIT 1);', $this->entityManager);


            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        } elseif ($action == 'beforeoneday' || $date) {
            if (!isset($offset)) {
                $start_time = time();
                $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
            }
            // update user report.
            if ($type == 'user') {
                if (isset($offset)) {
                    $this->updateUserWithOffset($input, $output);
                } else {
                    $this->updateUser($input, $output);
                }
            }

            // update total ads.
            if ($type == 'ad') {
                if (isset($offset)) {
                    $this->updateUserTotalAdCountWithOffset($input, $output);
                } else {
                    $this->updateUserTotalAdCount($input, $output);
                }
            }

            if (!isset($offset)) {
                $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
                $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
            }
        } elseif (!$action && !$date) {
            $output->writeln('Please enter either action argument or date option.', true);
        }
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Update user.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUser($input, $output)
    {
        $action = $input->getArgument('action');
        $date   = $input->getOption('date');
        $count  = $this->getUserCount($action, $date);

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:user-report '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserWithOffset($input, $output)
    {
        $action              = $input->getArgument('action');
        $date                = $input->getOption('date');
        $userTableName       = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
        $userSiteTableName   = $this->entityManager->getClassMetadata('FaUserBundle:UserSite')->getTableName();
        $userReportTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReport')->getTableName();
        $offset              = $input->getOption('offset');

        list($startDate, $endDate)              = $this->getDateInTimeStamp($date);
        $userReportCategoryDailyTableName       = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportCategoryDaily')->getTableName();
        $userReportDailyTableName               = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportDaily')->getTableName();
        $userReportEditionDailyTableName        = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportEditionDaily')->getTableName();
        $userReportProfilePackageDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportProfilePackageDaily')->getTableName();
        $adReportDailyTableName                 = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdReportDaily')->getTableName();
        $userSiteViewCounterTableName           = $this->entityManager->getClassMetadata('FaUserBundle:UserSiteViewCounter')->getTableName();

        $userIdArray = array();
        $userCounts = $this->getUserCountResult($action, $date, $offset, $this->limit);
        foreach ($userCounts as $userCount) {
            $userIdArray[] = $userCount['user_id'];
        }
        if (count($userIdArray)) {
            array_unique($userIdArray);
            $userReportUserIds    = $this->historyEntityManager->getRepository('FaReportBundle:UserReport')->getUserReportUsersByIds($userIdArray);
            $nonUserReportUserIds = array_diff($userIdArray, $userReportUserIds);
            // inserting new user to user_report.
            if (count($nonUserReportUserIds)) {
                $output->writeln('Inserting into user_report....', true);
                $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportTableName.' (user_id, role_id, name, business_name, phone, email, is_active, signup_date, postcode, town_id, is_facebook_verified, is_paypal_vefiried, business_category_id, created_at, username, image, path, banner_path, company_welcome_message, company_address, phone1, phone2, website_link, about_us, about_you) SELECT u.id, u.role_id, concat_ws(" ", u.first_name, u.last_name), u.business_name, u.phone, u.email, u.is_active, u.created_at, u.zip, u.town_id, u.is_facebook_verified, u.is_paypal_vefiried, u.business_category_id, '.($date ? strtotime($date) : strtotime(date('Y-m-d', strtotime('-1 day')))).', u.username, us.banner_path, u.image, us.path, us.company_welcome_message, us.company_address, us.phone1, us.phone2, us.website_link, us.about_us, u.about_you FROM '.$this->mainDbName.'.'.$userTableName.' u LEFT JOIN '.$this->mainDbName.'.'.$userSiteTableName.' us ON us.user_id = u.id WHERE u.id IN ('.implode(',', $nonUserReportUserIds).') GROUP BY u.id', $this->historyEntityManager);
            }

            //$userIdsToBeInsertedInUserDailyTable = $nonUserReportUserIds;
            $userIdsToBeInsertedInUserDailyTable = array();
            $usersFromSiteViewCounterArray       = $this->getUsersFromUserSiteViewCounterTable($action, $date, $offset, $this->limit);
            foreach ($usersFromSiteViewCounterArray As $usersFromSiteViewCounter) {
                $userIdsToBeInsertedInUserDailyTable[] = $usersFromSiteViewCounter['user_id'];
            }

            $existingUserDailyReportUserIds = $this->historyEntityManager->getRepository('FaReportBundle:UserReportDaily')->getUserDailyReportUsersByIdsAndDate($userIdsToBeInsertedInUserDailyTable, $startDate);
            $this->historyEntityManager->getRepository('FaReportBundle:UserReportDaily')->removeUserReportDailyUsersByIds($existingUserDailyReportUserIds, $date);
            $existingUserDailyReportUserIds = $this->historyEntityManager->getRepository('FaReportBundle:UserReportDaily')->getUserDailyReportUsersByIdsAndDate($userIdsToBeInsertedInUserDailyTable, $startDate);
            $userIdsToBeInsertedInUserDailyTable = array_diff($userIdsToBeInsertedInUserDailyTable, $existingUserDailyReportUserIds);
            // inserting new user to user_report.
            if (count($userIdsToBeInsertedInUserDailyTable)) {
                $insertSQL = 'INSERT INTO '.$this->historyDbName.'.'.$userReportDailyTableName.'
                    (user_id, created_at, profile_page_view_count, profile_page_email_sent_count, profile_page_website_url_click_count, profile_page_phone_click_count, profile_page_social_links_click_count, profile_page_map_click_count, role_id)
                    SELECT usvc.user_id, '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).', usvc.hits, usvc.profile_page_email_sent_count, usvc.profile_page_website_url_click_count, usvc.profile_page_phone_click_count, usvc.profile_page_social_links_click_count, usvc.profile_page_map_click_count, u.role_id
                    FROM '.$this->mainDbName.'.'.$userSiteViewCounterTableName.' usvc
                    INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON usvc.user_id = u.id
                    WHERE usvc.user_id IN ('.implode(',', $userIdsToBeInsertedInUserDailyTable).') AND (usvc.created_at BETWEEN '.$startDate.' AND  '.$endDate.');';
                $this->executeRawQuery($insertSQL, $this->historyEntityManager);
                $output->writeln($insertSQL, true);
            }

            // updating new user to user_report.
            if (count($userReportUserIds)) {
                $output->writeln('Updating user_report....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON ur.user_id = u.id LEFT JOIN '.$this->mainDbName.'.'.$userSiteTableName.' us ON us.user_id = u.id SET ur.role_id = u.role_id, ur.name = concat_ws(" ", u.first_name, u.last_name), ur.business_name = u.business_name, ur.phone = u.phone, ur.email = u.email, ur.is_active = u.is_active, ur.signup_date = u.created_at, ur.postcode = u.zip, ur.town_id = u.town_id, ur.is_facebook_verified = u.is_facebook_verified, ur.is_paypal_vefiried = u.is_paypal_vefiried, ur.updated_at = '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).', ur.username = u.username, ur.business_category_id = u.business_category_id, ur.banner_path = us.banner_path, ur.company_welcome_message = us.company_welcome_message, ur.company_address = us.company_address, ur.phone1 = us.phone1, ur.phone2 = us.phone2, ur.website_link = us.website_link, ur.about_us = us.about_us, ur.about_you = u.about_you WHERE u.id IN ('.implode(',', $userReportUserIds).');', $this->historyEntityManager);

                $output->writeln('Updating user_report_category_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportCategoryDailyTableName.' urcd INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON urcd.user_id = u.id SET urcd.role_id = u.role_id WHERE u.id IN ('.implode(',', $userReportUserIds).');', $this->historyEntityManager);

                $output->writeln('Updating user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON urd.user_id = u.id SET urd.role_id = u.role_id WHERE u.id IN ('.implode(',', $userReportUserIds).');', $this->historyEntityManager);

                $output->writeln('Updating user_report_edition_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportEditionDailyTableName.' ured INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON ured.user_id = u.id SET ured.role_id = u.role_id WHERE u.id IN ('.implode(',', $userReportUserIds).');', $this->historyEntityManager);

                $output->writeln('Updating user_report_profile_package_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportProfilePackageDailyTableName.' urppd INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON urppd.user_id = u.id SET urppd.role_id = u.role_id WHERE u.id IN ('.implode(',', $userReportUserIds).');', $this->historyEntityManager);

                $output->writeln('Updating ad_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$adReportDailyTableName.' ard INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON ard.user_id = u.id SET ard.role_id = u.role_id WHERE u.id IN ('.implode(',', $userReportUserIds).');', $this->historyEntityManager);
            }
        }
    }

    /**
     * Update user total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserTotalAdCount($input, $output)
    {
        $action = $input->getArgument('action');
        $date   = $input->getOption('date');
        $count  = $this->getUserTotalAdCount($action, $date);

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:user-report '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserTotalAdCountWithOffset($input, $output)
    {
        $action              = $input->getArgument('action');
        $date                = $input->getOption('date');
        $userTableName       = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
        $adTableName         = $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
        $userReportTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReport')->getTableName();
        $offset              = $input->getOption('offset');

        $userIdArray       = array();
        $userTotalAdCounts = $this->getUserAdCountResult($action, $date, $offset, $this->limit);
        $userIdArray       = array();
        foreach ($userTotalAdCounts as $userTotalAdCount) {
            $userIdArray[] = $userTotalAdCount['user_id'];
        }
        if (count($userIdArray)) {
            array_unique($userIdArray);

            $output->writeln('Updating first_paa to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.first_paa = (select a.created_at from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id ORDER BY a.id asc LIMIT 1) WHERE ur.user_id in ('.implode(',', $userIdArray).');', $this->historyEntityManager);

            $output->writeln('Updating last_paa to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.last_paa = (select a.created_at from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id ORDER BY a.id desc LIMIT 1) WHERE ur.user_id in ('.implode(',', $userIdArray).');', $this->historyEntityManager);

            $output->writeln('Updating last_paa to user....', true);
            $this->executeRawQuery('UPDATE '.$this->mainDbName.'.'.$userTableName.' u SET u.last_paa = (select a.created_at from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = u.id ORDER BY a.id desc LIMIT 1) WHERE u.id in ('.implode(',', $userIdArray).');', $this->entityManager);

            $output->writeln('Updating total_ad to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.total_ad = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id) WHERE ur.user_id in ('.implode(',', $userIdArray).');', $this->historyEntityManager);

            $output->writeln('Updating total_ad to user....', true);
            $this->executeRawQuery('UPDATE '.$this->mainDbName.'.'.$userTableName.' u SET u.total_ad = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = u.id) WHERE u.id in ('.implode(',', $userIdArray).');', $this->entityManager);

            $output->writeln('Updating last_paa_expires_at to user....', true);
            $this->executeRawQuery('UPDATE '.$this->mainDbName.'.'.$userTableName.' u SET u.last_paa_expires_at = (select a.expires_at from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = u.id ORDER BY a.expires_at desc LIMIT 1) WHERE u.id in ('.implode(',', $userIdArray).');', $this->entityManager);

            $output->writeln('Updating total_active_ad to user_report....', true);
            $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportTableName.' ur SET ur.total_active_ad = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = ur.user_id AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.') WHERE ur.user_id in ('.implode(',', $userIdArray).');', $this->historyEntityManager);

            // update user daily report.
            $this->updateUserReportDaily($userIdArray, $date, $output);
        }
    }

    /**
     * Update user report daily statistics.
     *
     * @param array  $userIdArray User id array.
     * @param string $date        Date.
     * @param object $output      Output object.
     */
    private function updateUserReportDaily($userIdArray, $date, $output)
    {
        if (count($userIdArray)) {
            $userReportDailyTableName     = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportDaily')->getTableName();
            $userTableName                = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
            $adTableName                  = $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
            $userSearchAgentTableName     = $this->entityManager->getClassMetadata('FaUserBundle:UserSearchAgent')->getTableName();
            $paymentTableName             = $this->entityManager->getClassMetadata('FaPaymentBundle:Payment')->getTableName();
            $userSiteViewCounterTableName = $this->entityManager->getClassMetadata('FaUserBundle:UserSiteViewCounter')->getTableName();

            $this->historyEntityManager->getRepository('FaReportBundle:UserReportDaily')->removeUserReportDailyUsersByIds($userIdArray, $date);
            // inserting new user to user_report.
            $output->writeln('Inserting into user_report_daily....', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportDailyTableName.' (user_id, role_id, created_at) SELECT id, role_id, '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).' FROM '.$this->mainDbName.'.'.$userTableName.' WHERE id IN ('.implode(',', $userIdArray).');', $this->historyEntityManager);

            $lastInsertedIds = array();
            $stmt = $this->executeRawQuery('SELECT id FROM '.$this->historyDbName.'.'.$userReportDailyTableName.' ORDER BY id DESC LIMIT '.count($userIdArray).';', $this->historyEntityManager);
            foreach ($stmt->fetchAll() as $lastInsertVal) {
                $lastInsertedIds[] = $lastInsertVal['id'];
            }

            if (count($lastInsertedIds)) {
                list($startDate, $endDate) = $this->getDateInTimeStamp($date);

                $output->writeln('Updating number_of_active_ads in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.number_of_active_ads = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = urd.user_id AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.') WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating renewed_ads in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.renewed_ads = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = urd.user_id AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.' AND (a.renewed_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating expired_ads in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.expired_ads = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = urd.user_id AND a.status_id = '.EntityRepository::AD_STATUS_EXPIRED_ID.' AND (a.expires_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating number_of_ads_to_renew in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.number_of_ads_to_renew = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = urd.user_id AND a.status_id = '.EntityRepository::AD_STATUS_EXPIRED_ID.' AND (a.expires_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating number_of_ad_placed in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.number_of_ad_placed = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = urd.user_id AND (a.created_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating number_of_ad_sold in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.number_of_ad_sold = (select count(a.id) from '.$this->mainDbName.'.'.$adTableName.' a WHERE a.user_id = urd.user_id AND (a.sold_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating saved_searches in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.saved_searches = (select count(usa.id) from '.$this->mainDbName.'.'.$userSearchAgentTableName.' usa WHERE usa.user_id = urd.user_id AND (usa.created_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating total_spent in user_report_daily....', true);
                $this->executeRawQuery('UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd SET urd.total_spent = (select SUM(p.amount) from '.$this->mainDbName.'.'.$paymentTableName.' p WHERE p.user_id = urd.user_id AND (p.created_at BETWEEN '.$startDate.' AND  '.$endDate.')) WHERE urd.user_id IN ('.implode(',', $userIdArray).') AND urd.id IN ('.implode(',', $lastInsertedIds).');', $this->historyEntityManager);

                $output->writeln('Updating from user_site_view counter table in user_report_daily....', true);
                $updateSql = 'UPDATE '.$this->historyDbName.'.'.$userReportDailyTableName.' urd
                              INNER JOIN '.$this->mainDbName.'.'.$userSiteViewCounterTableName.' usvc ON urd.user_id = usvc.user_id
                              SET
                                urd.profile_page_view_count = usvc.hits,
                                urd.profile_page_email_sent_count = usvc.profile_page_email_sent_count,
                                urd.profile_page_website_url_click_count = usvc.profile_page_website_url_click_count,
                                urd.profile_page_phone_click_count = usvc.profile_page_phone_click_count,
                                urd.profile_page_social_links_click_count = usvc.profile_page_social_links_click_count,
                                urd.profile_page_map_click_count = usvc.profile_page_map_click_count
                              WHERE (urd.created_at BETWEEN '.$startDate.' AND  '.$endDate.') AND
                                     urd.user_id IN ('.implode(',', $userIdArray).') AND
                                     urd.id IN ('.implode(',', $lastInsertedIds).');';
                $output->writeln($updateSql);
                $this->executeRawQuery($updateSql, $this->historyEntityManager);
            }
        }
    }

    /**
     * Get total updated or created user.
     *
     * @param string $action       Action name.
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getUserCount($action, $date, $searchParams = array())
    {
        $userRepository = $this->entityManager->getRepository('FaUserBundle:User');

        $query = $userRepository->getBaseQueryBuilder()
            ->select('COUNT('.UserRepository::ALIAS.'.id)');

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.UserRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.UserRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get user ad count results.
     *
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserCountResult($action, $date, $offset, $limit, $searchParam = array())
    {
        $userRepository = $this->entityManager->getRepository('FaUserBundle:User');

        $query = $userRepository->getBaseQueryBuilder()
        ->select(UserRepository::ALIAS.'.id as user_id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.UserRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.UserRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get query builder for ads.
     *
     * @param string $action       Action name.
     * @param string  $date        Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getUserTotalAdCount($action, $date, $searchParams = array())
    {
        $adTableName   = $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
        $where         = '';
        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $where = ' WHERE ('.AdRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')';
        }

        $sql = 'SELECT COUNT(*) as total_user
            FROM (
                SELECT COUNT('.AdRepository::ALIAS.'.id)
                FROM '.$adTableName.' as '.AdRepository::ALIAS.'
                '.$where.'
                GROUP BY '.AdRepository::ALIAS.'.user_id
            ) '.$adTableName;

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $useradCount = $stmt->fetch();

        return $useradCount['total_user'];
    }

    /**
     * Get user ad count results.
     *
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserAdCountResult($action, $date, $offset, $limit, $searchParam = array())
    {
        $adRepository  = $this->entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder()
        ->addSelect(UserRepository::ALIAS.'.id as user_id')
        ->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS)
        ->groupBy(UserRepository::ALIAS.'.id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get date in time stamp
     *
     * @param string $date Date.
     *
     * @return array
     */
    private function getDateInTimeStamp($date)
    {
        if ($date) {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime($date)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime($date)));
        } else {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60)));
        }

        return array($startDate, $endDate);
    }

    /**
     * Get user entries recently added or updated in user_site_view_counter table.
     *
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUsersFromUserSiteViewCounterTable($action, $date, $offset, $limit, $searchParam = array())
    {
        $userSiteViewCounterRepository = $this->entityManager->getRepository('FaUserBundle:UserSiteViewCounter');

        $query = $userSiteViewCounterRepository->getBaseQueryBuilder()
        ->select('IDENTITY('.UserSiteViewCounterRepository::ALIAS.'.user) as user_id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.UserSiteViewCounterRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        return $query->getQuery()->getArrayResult();
    }
}
