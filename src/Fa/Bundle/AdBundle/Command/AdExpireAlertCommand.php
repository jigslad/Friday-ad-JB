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
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdExpireAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-expire-alert')
        ->setDescription("Send ad expire alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('last_months', null, InputOption::VALUE_OPTIONAL, 'Last months', null)
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send ad expire alert to users when ad is expired

Command:
 - php app/console fa:update:ad-expire-alert
 - php app/console fa:update:ad-expire-alert --last_months="1"
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
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $offset     = $input->getOption('offset');
        $lastMonths = $input->getOption('last_months');

        $searchParam                     = array();
        $searchParam['entity_ad_status'] = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));

        $dateFrom = null;
        $dateTo   = date('d/m/Y', time());
        if ($lastMonths) {
            $lastMonths = (int) $lastMonths;
            $dateFrom   = date('d/m/Y', strtotime('-'.$lastMonths.' months'));
        }
        $searchParam['ad']['expires_at_from_to'] =  $dateFrom.'|'.$dateTo;
        $searchParam['ad']['is_feed_ad']         = 0;
        $searchParam['ad']['is_blocked_ad']      = 0;

        if (isset($offset)) {
            $this->updateAdExpirationWithOffset($searchParam, $input, $output);
        } else {
            $this->updateAdExpiration($searchParam, $input, $output);
            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:process-email-queue --email_identifier="ad_is_expired"';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdExpirationWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        foreach ($ads as $ad) {
            $expiredAt     = time();
            $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
            $adStatRepository  = $this->em->getRepository('FaAdBundle:AdStatistics');
            $user = ($ad->getUser() ? $ad->getUser() : null);

            //send email only if ad has user and status is active.
            if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                //$adRepository->sendExpirationEmail($ad, $this->getContainer());
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('ad_is_expired', $user, $ad, $this->getContainer());
            }
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID));
            $ad->setExpiresAt($expiredAt);
            $this->em->persist($ad);

            // insert expire stat
            $adStatRepository->insertExpiredStat($ad, $expiredAt);

            // inactivate the package
            $this->em->getRepository('FaAdBundle:Ad')->doAfterAdCloseProcess($ad->getId(), $this->getContainer());
            $this->em->flush();

            $user_id = $ad->getUser() ? $ad->getUser()->getId() : null;
            $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByOnlyAdId($ad->getId());
            $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_expired', $ad->getId(), $user_id);
            $output->writeln('Ad has been expired with AD ID: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
        }
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdExpiration($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-expire-alert '.$commandOptions;
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
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($searchParam)
    {
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array ('id' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount($searchParam)
    {
        $qb = $this->getAdQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        return $qb->getQuery()->getSingleScalarResult();
    }
}
