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
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\UserPackageRepository;

/**
 * This command is used to update user report statistics for category and print edition.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateProfilePackageRevenueReportCommand extends ContainerAwareCommand
{
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
        ->setName('fa:update:profile-package-revenue-report')
        ->setDescription("Update user profile package revenue.")
        ->addArgument('action', InputArgument::OPTIONAL, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', "512M")
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update user's profile package revenue.

Command:
 - php app/console fa:update:profile-package-revenue-report beforeoneday
 - php app/console fa:update:profile-package-revenue-report --date="2015-04-28"
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

        $start_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        // update user report.
        $this->insertProfilePackages($input, $output);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
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
     * Insert user profile packages.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function insertProfilePackages($input, $output)
    {
        $date                   = $input->getOption('date');
        $action                 = $input->getArgument('action');
        $userTableName          = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
        $userPackageTableName   = $this->entityManager->getClassMetadata('FaUserBundle:UserPackage')->getTableName();
        $packageTableName       = $this->entityManager->getClassMetadata('FaPromotionBundle:Package')->getTableName();
        $userReportPPTableName  = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportProfilePackageDaily')->getTableName();

        if ($action == '' || $action == 'beforeoneday') {
            if ($date == '') {
                $date = date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60));
            }
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $this->historyEntityManager->getRepository('FaReportBundle:userReportProfilePackageDaily')->removeUserReportPPDailyByDate($date);
            $rowUserPackages = $this->getUserProfilePackages($date);

            if ($rowUserPackages) {
                $totalRecords = count($rowUserPackages);
                $output->writeln('##### NUMBER OF RECORDS TO BE INSERTED: '.$totalRecords.' #####', true);
                $valuesSTR = '';
                $insertSQL = 'INSERT INTO '.$this->historyDbName.'.'.$userReportPPTableName.' (user_id, role_id, package_id, package_name, package_price, package_remark, is_trial_package, package_cancelled_at, created_at, package_category_id) VALUES ';
                $i = 1;
                foreach ($rowUserPackages as $record) {
                    $valuesSTR .= '("'.$record['user_id'].'", "'.$record['role_id'].'", "'.$record['package_id'].'", "'.$record['package_text'].'", "'.$record['price'].'", "'.$record['remark'].'", "'.$record['trial'].'", "'.$record['cancelled_at'].'", "'.$record['created_at'].'", "'.$record['shop_category_id'].'"),';
                    $output->writeln($i.' records prepared for insertion out of '.$totalRecords, true);
                    $i++;
                }

                $valuesSTR = trim($valuesSTR, ',');
                $insertSQL = $insertSQL . $valuesSTR;
                $this->executeRawQuery($insertSQL, $this->historyEntityManager);
                $output->writeln('');
            }

            $rowCancelledUserPackages = $this->getCancelledUserProfilePackages($date);
            if ($rowCancelledUserPackages) {
                $i = 1;
                $totalRecords = count($rowCancelledUserPackages);
                $output->writeln('##### NUMBER OF RECORDS TO BE UPDATED: '.$totalRecords.' #####', true);
                foreach ($rowCancelledUserPackages as $record) {
                    $updateSQL = "UPDATE ".$this->historyDbName.'.'.$userReportPPTableName."
                                    SET package_cancelled_at = '".$record['cancelled_at']."'
                                    WHERE created_at = '".$record['created_at']."' AND
                                          user_id = '".$record['user_id']."' AND
                                          package_id = '".$record['package_id']."' AND
                                          (package_cancelled_at IS NULL OR package_cancelled_at = 0)";
                    $this->executeRawQuery($updateSQL, $this->historyEntityManager);
                    $output->writeln($i.' records updated successfully out of '.$totalRecords);
                    $i++;
                }
            }
        } else {
            $deleteSQL = "TRUNCATE TABLE ".$this->historyDbName.".".$userReportPPTableName;
            $this->executeRawQuery($deleteSQL, $this->historyEntityManager);
            $this->historyEntityManager->getRepository('FaReportBundle:userReportProfilePackageDaily')->removeUserReportPPDailyByDate($date);
            $output->writeln('Inserting into user_report_profile_package_daily....', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportPPTableName.' (user_id, role_id, package_id, package_name, package_price, package_remark, is_trial_package, package_cancelled_at, created_at) SELECT u.id, u.role_id, up.package_id, p.package_text, p.price, up.remark, up.trial, up.cancelled_at, up.created_at FROM '.$this->mainDbName.'.'.$userPackageTableName.' up INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON (up.user_id = u.id) INNER JOIN '.$this->mainDbName.'.'.$packageTableName.' p ON (p.id = up.package_id);', $this->historyEntityManager);
        }
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
     * Get total updated or created profile packages.
     *
     * @param string $action       Action name.
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getUserProfilePackages($date)
    {
        $userPackageRepository = $this->entityManager->getRepository('FaUserBundle:UserPackage');

        $query = $userPackageRepository->getBaseQueryBuilder()
                ->select('IDENTITY('.UserPackageRepository::ALIAS.'.user) as user_id', 'IDENTITY('.UserPackageRepository::ALIAS.'.package) as package_id', UserPackageRepository::ALIAS.'.created_at', UserPackageRepository::ALIAS.'.updated_at', UserPackageRepository::ALIAS.'.cancelled_at', UserPackageRepository::ALIAS.'.closed_at', UserPackageRepository::ALIAS.'.status', UserPackageRepository::ALIAS.'.remark', UserPackageRepository::ALIAS.'.trial', 'IDENTITY('.UserRepository::ALIAS.'.role) as role_id', PackageRepository::ALIAS.'.price', PackageRepository::ALIAS.'.package_text', 'IDENTITY('.PackageRepository::ALIAS.'.shop_category) as shop_category_id')
                ->innerJoin(UserPackageRepository::ALIAS.'.user', UserRepository::ALIAS)
                ->innerJoin(UserPackageRepository::ALIAS.'.package', PackageRepository::ALIAS);

        if ($date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.UserPackageRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.UserPackageRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')');
            $query->andWhere(UserPackageRepository::ALIAS.'.status = :activeStatus AND '. UserPackageRepository::ALIAS.'.cancelled_at IS NULL');
            $query->setParameter('activeStatus', 'A');
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Get total cancelled profile packages.
     *
     * @param string $action       Action name.
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getCancelledUserProfilePackages($date)
    {
        $userPackageRepository = $this->entityManager->getRepository('FaUserBundle:UserPackage');

        $query = $userPackageRepository->getBaseQueryBuilder()
        ->select('IDENTITY('.UserPackageRepository::ALIAS.'.user) as user_id', 'IDENTITY('.UserPackageRepository::ALIAS.'.package) as package_id', UserPackageRepository::ALIAS.'.created_at', UserPackageRepository::ALIAS.'.cancelled_at');

        if ($date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere(UserPackageRepository::ALIAS.'.cancelled_at BETWEEN '.$startDate.' AND  '.$endDate);
        }

        return $query->getQuery()->getResult();
    }
}
