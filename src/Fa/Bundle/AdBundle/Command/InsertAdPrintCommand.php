<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;

/**
 * This command is used to insert missing print entries.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class InsertAdPrintCommand extends ContainerAwareCommand
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
        ->setName('fa:insert:ad-print')
        ->setDescription("Update ad report statistics.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to insert missing print entries.

Command:
 - php app/console fa:insert:ad-print --date="2015-09-23"
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
        $date   = $input->getOption('date');

        // insert ads statistics.
        if (isset($offset)) {
            $this->insertAdPrintWithOffset($input, $output);
        } else {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);

            $this->insertAdPrint($input, $output);

            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
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
     * Insert missing ad print with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function insertAdPrint($input, $output)
    {
        $date   = $input->getOption('date');
        $count  = $this->getAdPrintCount($date);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:insert:ad-print '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Insert missing ad print with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function insertAdPrintWithOffset($input, $output)
    {
        $date   = $input->getOption('date');
        $offset = $input->getOption('offset');
        $adPrints = $this->getAdPrintResult($date, $offset, $this->limit);
        $adRepository  = $this->entityManager->getRepository('FaAdBundle:Ad');

        foreach ($adPrints as $adPrint) {
            $packageValues = unserialize($adPrint['value']);
            if (isset($packageValues['packagePrint'])) {
                $adObj = $adRepository->findOneBy(array('id' => $adPrint['ad_id']));

                $sql = 'SELECT value as total_print_editions FROM '.$this->mainDbName.'.ad_user_package_upsell
                    WHERE  upsell_id
                    IN (
                        SELECT id
                        FROM '.$this->mainDbName.'.upsell
                        WHERE TYPE = '.UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID.'
                    ) AND ad_user_package_id = '.$adPrint['id'].'  LIMIT 1';
                $stmt = $this->executeRawQuery($sql, $this->entityManager);
                $res = $stmt->fetch();
                if (isset($res['total_print_editions']) && $res['total_print_editions'] && isset($packageValues['packagePrint']['duration']) && $packageValues['packagePrint']['duration']) {
                    $this->addPrintAd($output, $res['total_print_editions'], $packageValues['packagePrint']['duration'], $adObj, false, true, false, $adPrint['started_at'], false);
                }
            }
        }
    }

    /**
     * Get query builder for ads.
     *
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getAdPrintCount($date, $searchParams = array())
    {
        list($startDate, $endDate) = $this->getDateInTimeStamp($date);
        $sql = 'SELECT COUNT(id) as total_ad_user_package FROM '.$this->mainDbName.'.ad_user_package WHERE ad_id IN
                (
                    SELECT
                    a0_.ad_id
                    FROM
                    '.$this->historyDbName.'.ad_report_daily a0_
                    WHERE
                    (a0_.created_at BETWEEN '.$startDate.' AND '.$endDate.')
                    AND a0_.is_renewed = 1
                    ORDER BY a0_.id DESC
                )
                AND status <> 1
                AND package_id IN
                (
                    SELECT id
                    FROM '.$this->mainDbName.'.package as p
                    INNER JOIN package_upsell as pu ON ( p.id = pu.package_id )
                    AND pu.upsell_id
                    IN
                    (
                        SELECT id
                        FROM '.$this->mainDbName.'.upsell
                        WHERE TYPE = '.UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID.'
                    )
                )';

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        $res = $stmt->fetch();

        return $res['total_ad_user_package'];
    }

    /**
     * Get query builder for ads results.
     *
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdPrintResult($date, $offset, $limit, $searchParam = array())
    {
        list($startDate, $endDate) = $this->getDateInTimeStamp($date);
        $sql = 'SELECT * FROM '.$this->mainDbName.'.ad_user_package WHERE ad_id IN
                (
                    SELECT
                    a0_.ad_id
                    FROM
                    '.$this->historyDbName.'.ad_report_daily a0_
                    WHERE
                    (a0_.created_at BETWEEN '.$startDate.' AND '.$endDate.')
                    AND a0_.is_renewed = 1
                    ORDER BY a0_.id DESC
                )
                AND status <> 1
                AND package_id IN
                (
                    SELECT id
                    FROM '.$this->mainDbName.'.package as p
                    INNER JOIN package_upsell as pu ON ( p.id = pu.package_id )
                    AND pu.upsell_id
                    IN
                    (
                        SELECT id
                        FROM '.$this->mainDbName.'.upsell
                        WHERE TYPE = '.UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID.'
                    )
                ) LIMIT '.$limit.' OFFSET '.$offset;

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        return $stmt->fetchAll();
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
     * Add print ad.
     *
     * @param integer $limit             Print edition limit.
     * @param string  $duration          Duration od print upsell.
     * @param object  $adObj             Ad object.
     * @param boolean $addAdToModeration Send ad to moderate or not.
     * @param boolean $isPaid            Flag for print ad is paid or free.
     * @param boolean $batchUpdate       if call from batch update
     * @param string  $rundate           Run date.
     * @param boolean $futureAdPostFlag  Future advert post flag.
     */
    public function addPrintAd($output, $limit, $duration, $adObj, $addAdToModeration = true, $isPaid = false, $batchUpdate = false, $rundate = null, $futureAdPostFlag = false)
    {
        //get print editions based on ad location group
        $adPrintRepository        = $this->entityManager->getRepository('FaAdBundle:AdPrint');
        $duration                 = (int) $duration;
        $printEditions            = $adPrintRepository->getPrintEditionForAd($limit, $adObj->getId());
        $defaultPrinEditionValues = $this->entityManager->getRepository('FaAdBundle:PrintEdition')->getDefaultPrinEditionValues();
        if (!$duration) {
            $duration = $defaultPrinEditionValues['no_of_week'];
        }

        // for future post ad
        if ($futureAdPostFlag && !$rundate) {
            $rundate = $adObj->getFuturePublishAt();
        }

        // get latest sequence for ad.
        $prevAdPrintObj = $adPrintRepository->findOneBy(array('ad' => $adObj->getId()), array('id' => 'desc'));
        $cntr = 1;
        foreach ($printEditions as $printEdition) {
            //get insert date.
            $insertDate = $adPrintRepository->getInsertDateForPrintEdition($printEdition, $defaultPrinEditionValues, $rundate);

            $sequence = 1;
            if ($prevAdPrintObj && $prevAdPrintObj->getSequence()) {
                $sequence = $prevAdPrintObj->getSequence() + 1;
            }
            //insert duration wise print ad.
            for ($i=0; $i < $duration; $i++) {
                $oldAdPrintObj = $adPrintRepository->findOneBy(array('ad' => $adObj->getId(), 'print_edition' => $printEdition->getId(), 'is_paid' => '1', 'insert_date' => strtotime('+'.$i.' weeks', $insertDate)), array('id' => 'desc'));
                if (!$oldAdPrintObj) {
                    if ($cntr == 1) {
                        $output->writeln('Print dates added for ad id:'.$adObj->getId(), true);
                    }
                    $adPrint = new AdPrint();
                    $adPrint->setAd($adObj);
                    $adPrint->setPrintEdition($printEdition);
                    $adPrint->setDuration($duration.' weeks');
                    $adPrint->setSequence($sequence);

                    if ($isPaid) {
                        $adPrint->setIsPaid(1);
                    } else {
                        $adPrint->setIsPaid(0);
                    }

                    $adPrint->setPrintQueue(AdPrintRepository::PRINT_QUEUE_STATUS_SEND);

                    if (!$addAdToModeration && !$futureAdPostFlag) {
                        $adPrint->setAdModerateStatus(AdModerateRepository::MODERATION_QUEUE_STATUS_OKAY);
                    } else {
                        $adPrint->setAdModerateStatus(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT);
                    }
                    $adPrint->setInsertDate(strtotime('+'.$i.' weeks', $insertDate));

                    $this->entityManager->persist($adPrint);
                    $output->writeln('Entry: '.$cntr.' => Print edition: '.$printEdition->getId().' Insert date: '.date('Y-m-d', strtotime('+'.$i.' weeks', $insertDate)).'('.strtotime('+'.$i.' weeks', $insertDate).')', true);
                    $sequence++;
                    $cntr++;
                }
            }

            if ($batchUpdate == false) {
                $this->entityManager->flush();
            }
        }
        if ($cntr > 1) {
            $output->writeln('----------------------------------', true);
        }
    }
}
