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

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdRenewAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-renew-alert')
        ->setDescription("Send ad renewal alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send ad renewal alert to users before expiration

Command:
 - php app/console fa:update:ad-renew-alert
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

        $offset   = $input->getOption('offset');

        $searchParam                     = array();
        $searchParam['entity_ad_status'] = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));

        $identifier = 'ad_needs_renewing_4_days_left';
        $parameters = $this->em->getRepository('FaEmailBundle:EmailTemplate')->getSchedualParameterArray($identifier, CommonManager::getCurrentCulture($this->getContainer()));
        $lastDays   = isset($parameters['advert_with_x_days_left_to_expire']) && $parameters['advert_with_x_days_left_to_expire'] > 0 ? $parameters['advert_with_x_days_left_to_expire'] : 4;

        if ($lastDays) {
            $date = date('d/m/Y', strtotime($lastDays.' day'));
            $searchParam['ad']['expires_at_from_to'] =  $date.'|'.$date;
        }

        // Skip detached ads for sending renew email
        $searchParam['ad']['is_detached_ad'] = 0;
        $searchParam['ad']['is_feed_ad']     = 0;
        $searchParam['ad']['is_blocked_ad']  = 0;

        if (isset($offset)) {
            $this->updateAdRenewalWithOffset($searchParam, $input, $output);
        } else {
            $this->updateAdRenewal($searchParam, $input, $output);
            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:process-email-queue --email_identifier="ad_needs_renewing_4_days_left"';
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
    protected function updateAdRenewalWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = 0;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        foreach ($ads as $ad) {
            $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
            $user          = ($ad->getUser() ? $ad->getUser() : null);

            //send email only if ad has user and status is active.
            if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                //$adRepository->sendRenewalEmail($ad, $this->getContainer());
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('ad_needs_renewing_4_days_left', $user, $ad, $this->getContainer());
                $ad->setIsRenewalMailSent(1);
                $this->em->persist($ad);
            }
            $user_id = $ad->getUser() ? $ad->getUser()->getId() : null;
            $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_24_days', $ad->getId(), $user_id);
            $output->writeln('Renewal email sent to for AD ID: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
            $this->em->flush();
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
    protected function updateAdRenewal($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-renew-alert '.$commandOptions;
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
        $data['query_sorter']  = array('ad' => array('id' => 'asc'));
        $data['static_filters'] = AdRepository::ALIAS.'.is_renewal_mail_sent IS NULL OR '.AdRepository::ALIAS.'.is_renewal_mail_sent = 0';

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
