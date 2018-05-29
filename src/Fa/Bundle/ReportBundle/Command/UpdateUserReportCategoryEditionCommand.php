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

/**
 * This command is used to update user report statistics for category and print edition.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserReportCategoryEditionCommand extends ContainerAwareCommand
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
        ->setName('fa:update:user-report-category-edition')
        ->setDescription("Update user report statistics for category & edition.")
        ->addArgument('action', InputArgument::OPTIONAL, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', "512M")
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update user report statistics for category and edition.

Command:
 - php app/console fa:update:user-report-category-edition all
 - php app/console fa:update:user-report-category-edition beforeoneday
 - php app/console fa:update:user-report-category-edition --date="2015-04-28"
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
        $this->updateUserCategoryEdition($input, $output);
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
     * Update user category & edition statistics.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserCategoryEdition($input, $output)
    {
        $date                   = $input->getOption('date');
        $action                 = $input->getArgument('action');
        $userTableName          = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
        $adTableName            = $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
        $adPrintTableName       = $this->entityManager->getClassMetadata('FaAdBundle:AdPrint')->getTableName();
        $userPackageTableName   = $this->entityManager->getClassMetadata('FaUserBundle:UserPackage')->getTableName();
        $packageTableName       = $this->entityManager->getClassMetadata('FaPromotionBundle:Package')->getTableName();
        $userReportCatTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportCategoryDaily')->getTableName();
        $userReportEdtTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportEditionDaily')->getTableName();
        $userReportPPTableName  = $this->historyEntityManager->getClassMetadata('FaReportBundle:UserReportProfilePackageDaily')->getTableName();

        if ($action == '' || $action == 'beforeoneday') {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $this->historyEntityManager->getRepository('FaReportBundle:userReportCategoryDaily')->removeUserReportCategoryDailyByDate($date);
            $output->writeln('Inserting into user_report_category_daily....', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportCatTableName.' (user_id, role_id, category_id, created_at) SELECT a.user_id, u.role_id, a.category_id, '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).' FROM '.$this->mainDbName.'.'.$adTableName.' a LEFT JOIN '.$this->mainDbName.'.'.$userTableName.' u ON (a.user_id = u.id) WHERE (a.created_at BETWEEN '.$startDate.' AND  '.$endDate.') AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.';', $this->historyEntityManager);

            $this->historyEntityManager->getRepository('FaReportBundle:userReportEditionDaily')->removeUserReportEditionDailyByDate($date);
            $output->writeln('Inserting into user_report_edition_daily....', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportEdtTableName.' (user_id, role_id, edition_id, created_at) SELECT a.user_id, u.role_id, ap.print_edition_id, '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).' FROM '.$this->mainDbName.'.'.$adPrintTableName.' ap INNER JOIN '.$this->mainDbName.'.'.$adTableName.' a ON (ap.ad_id = a.id) INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON (a.user_id = u.id) WHERE (ap.created_at BETWEEN '.$startDate.' AND  '.$endDate.') AND ap.is_paid = 1 AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.' GROUP BY ap.print_edition_id;', $this->historyEntityManager);
        } else {
            $deleteSQL = "TRUNCATE TABLE ".$this->historyDbName.".".$userReportCatTableName;
            $this->executeRawQuery($deleteSQL, $this->historyEntityManager);
            $this->historyEntityManager->getRepository('FaReportBundle:userReportCategoryDaily')->removeUserReportCategoryDailyByDate($date);
            $output->writeln('Inserting into user_report_category_daily....', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportCatTableName.' (user_id, role_id, category_id, created_at) SELECT a.user_id, u.role_id, a.category_id, '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).' FROM '.$this->mainDbName.'.'.$adTableName.' a LEFT JOIN '.$this->mainDbName.'.'.$userTableName.' u ON (a.user_id = u.id) WHERE a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.';', $this->historyEntityManager);

            $deleteSQL = "TRUNCATE TABLE ".$this->historyDbName.".".$userReportEdtTableName;
            $this->executeRawQuery($deleteSQL, $this->historyEntityManager);
            $this->historyEntityManager->getRepository('FaReportBundle:userReportEditionDaily')->removeUserReportEditionDailyByDate($date);
            $output->writeln('Inserting into user_report_edition_daily....', true);
            $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$userReportEdtTableName.' (user_id, role_id, edition_id, created_at) SELECT a.user_id, u.role_id, ap.print_edition_id, '.($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60)).' FROM '.$this->mainDbName.'.'.$adPrintTableName.' ap INNER JOIN '.$this->mainDbName.'.'.$adTableName.' a ON (ap.ad_id = a.id) INNER JOIN '.$this->mainDbName.'.'.$userTableName.' u ON (a.user_id = u.id) WHERE ap.is_paid = 1 AND a.status_id = '.EntityRepository::AD_STATUS_LIVE_ID.' GROUP BY ap.print_edition_id;', $this->historyEntityManager);
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
}
