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
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send ad to moderation.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SendAdForReModerationCommand extends ContainerAwareCommand
{
    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:ad-for-re-moderation')
        ->setDescription("Read moderation queue and send ad for remoderation")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'days', '5')
        ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'action', 'other')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Send ad for moderation

Command:
 - php app/console fa:send:ad-for-re-moderation --days="5"
 - php app/console fa:send:ad-for-re-moderation --days="5" --action="delete"

EOF
        );
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mainDbName    = $this->getContainer()->getParameter('database_name');
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();

        $searchParam = array();

        //get options passed in command
        $days   = $input->getOption('days');
        $action = $input->getOption('action');
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->processAdForModerationWithOffset($input, $output, $searchParam);
        } else {
            $this->processAdForModeration($input, $output, $searchParam);
        }
    }

    /**
     * Send ad for moderation with given offset.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     * @param object $manualModerationReason
     */
    protected function processAdForModerationWithOffset($input, $output, $searchParam)
    {
        $offset = $input->getOption('offset');
        $days   = $input->getOption('days');
        $action = $input->getOption('action');
        $objQB  = $this->getMainQueryBuilder($searchParam, $input, $action);
        $step   = 100;

        $objQB->setFirstResult($offset);
        $objQB->setMaxResults($step);

        $objAds = $objQB->getQuery()->execute();

        foreach ($objAds as $objAd) {
            $objAdModerates = $this->entityManager->getRepository('FaAdBundle:AdModerate')->findByAd($objAd);
            if ($objAdModerates && count($objAdModerates)) {
                $objAdModerate = $objAdModerates[0];
            }

            if ($action == 'delete') {
                $expiredAt     = time();
                $adRepository  = $this->entityManager->getRepository('FaAdBundle:Ad');
                $adStatRepository  = $this->entityManager->getRepository('FaAdBundle:AdStatistics');
                $user = ($objAd->getUser() ? $objAd->getUser() : null);

                //send email only if ad has user and status is active.
                /*if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                    $adRepository->sendExpirationEmail($objAd, $this->getContainer());
                }*/

                $objAd->setStatus($this->entityManager->getReference('FaEntityBundle:Entity', \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID));
                $objAd->setExpiresAt($expiredAt);
                $this->entityManager->persist($objAd);

                // insert expire stat
                $adStatRepository->insertExpiredStat($objAd, $expiredAt);

                // inactivate the package
                $this->entityManager->getRepository('FaAdBundle:Ad')->doAfterAdCloseProcess($objAd->getId(), $this->getContainer());
                $this->entityManager->flush();

                $user_id = $objAd->getUser() ? $objAd->getUser()->getId() : null;
                //$this->entityManager->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByOnlyAdId($objAd->getId());
                //$this->entityManager->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_expired', $objAd->getId(), $user_id);

                if ($objAdModerate) {
                    $deleteManager = $this->getContainer()->get('fa.deletemanager');
                    $deleteManager->delete($objAdModerate);
                }
                $output->writeln('Ad has been expired with AD ID: '.$objAd->getId(), true);
            } else {
                if ($objAdModerates && count($objAdModerates)) {
                    $objAdModerate = $objAdModerates[0];

                    $buildRequest      = $this->getContainer()->get('fa_ad.moderation.request_build');
                    $moderationRequest = $buildRequest->init($objAd, $objAdModerate->getValue());
                    $moderationRequest = json_encode($moderationRequest);

                    if ($buildRequest->sendRequest($moderationRequest)) {
                        $objAdModerate->setModerationQueue(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT);
                        $this->getContainer()->get('doctrine')->getManager()->persist($objAdModerate);
                        $output->writeln('Ad has been resend for moderation with AD ID: '.$objAd->getId(), true);
                    }
                    sleep(1);
                }
            }
        }
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send ad for moderation.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function processAdForModeration($input, $output, $searchParam)
    {
        $action = $input->getOption('action');
        $objQB  = $this->getMainQueryBuilder($searchParam, $input, $action, true);
        $count  = $objQB->getQuery()->getSingleScalarResult();

        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln("Total records to send for re-moderation are: ".$count);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:send:ad-for-re-moderation '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get query builder for ad moderate.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdModerateQueryBuilder($searchParam)
    {
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $adModerateRepository  = $entityManager->getRepository('FaAdBundle:Ad');
        $data                  = array();

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adModerateRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Delete records from moderation.
     *
     * @param string $days Number of days
     *
     * @return Doctrine_Query Object.
     */
    protected function deleteRecordsFromModeration($days, $output)
    {
        $fewDaysBeforeDate = strtotime("-".$days.' day', strtotime(date('Y-m-d 00:00:00')));
        $adModerateTableName = $this->entityManager->getClassMetadata('FaAdBundle:AdModerate')->getTableName();

        $moderationStatuses = AdModerateRepository::MODERATION_QUEUE_STATUS_SEND . ', ' . AdModerateRepository::MODERATION_QUEUE_STATUS_SENT;
        $deleteSql          = "DELETE FROM '.$this->mainDbName.'.'.$adModerateTableName.' WHERE moderation_queue IN (".$moderationStatuses.") AND created_at < ".$fewDaysBeforeDate;
        $this->executeRawQuery($deleteSql, $this->entityManager);
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
     * Get count for Ad to be moderated.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getMainQueryBuilder($searchParam, $input, $action = '', $onlyCount = false)
    {
        $moderationStatusArray = array(AdModerateRepository::MODERATION_QUEUE_STATUS_SEND, AdModerateRepository::MODERATION_QUEUE_STATUS_SENT, AdModerateRepository::MODERATION_QUEUE_STATUS_MANUAL_MODERATION);

        $qb = $this->getAdModerateQueryBuilder($searchParam);

        if ($onlyCount) {
            $qb = $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        }

        $qb = $qb->innerJoin('FaAdBundle:AdModerate', AdModerateRepository::ALIAS, 'WITH', $qb->getRootAlias().'.id = '.AdModerateRepository::ALIAS.'.ad')
                 ->where($qb->getRootAlias().'.status = '.EntityRepository::AD_STATUS_IN_MODERATION_ID)
                 ->andWhere(AdModerateRepository::ALIAS.'.moderation_queue IN (:moderationQueue)')
                 ->setParameter('moderationQueue', $moderationStatusArray);

        $days = $input->getOption('days');

        if (isset($days) && !empty($days) && $days > 0) {
            $fewDaysBeforeDate = date('Y-m-d', strtotime("-".$days.' day', time()));
            $startDate = CommonManager::getTimeStampFromStartDate($fewDaysBeforeDate);
            $fewDaysBeforeDate = date('Y-m-d', strtotime('-1 day', time()));
            $endDate = CommonManager::getTimeStampFromEndDate($fewDaysBeforeDate);
            if ($action == 'delete') {
                $qb = $qb->andWhere(AdModerateRepository::ALIAS.'.created_at < '.$startDate);
            } else {
                $qb = $qb->andWhere(AdModerateRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND '.$endDate);
            }
        }

        return $qb;
    }
}
