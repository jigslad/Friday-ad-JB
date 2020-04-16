<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\PaaLiteEmailNotificationRepository;

/**
 * This command is used to send 7 days after an ad first expires if the user has not reposted the ad and it is still inactive.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CompleteAdvertNotificationCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:complete-advert-notification')
        ->setDescription("This notification will sent to people who silently placed an ad with their phone number, 2 hours after an ad is placed via a PAA Lite form")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run continously.

Actions:
- Update notification sent status.

Command:
 - php app/console fa:send:complete-advert-notification"
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
       
        //get options passed in command
        $offset   = $input->getOption('offset');

        if (isset($offset)) {
            $this->completeAdvertNotificationsWithOffset($input, $output);
        } else {
            $this->completeAdvertNotifications($input, $output);

            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
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
    protected function completeAdvertNotificationsWithOffset($input, $output)
    {
        $records          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = 0;
        $container = $this->getContainer();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityCache =   $container->get('fa.entity.cache.manager');

        foreach ($records as $record) {
            $paaLiteEmailNotification = $this->em->getRepository('FaAdBundle:PaaLiteEmailNotification')->find($record['id']);
            $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('complete_advert', $record['ad_id'], $record['user_id'], strtotime('+3 minute'), true);

            $paaLiteEmailNotification->setIsAdConfirmationNotificationSent(1);
            $this->em->persist($paaLiteEmailNotification);
            $this->em->flush($paaLiteEmailNotification);
            $output->writeln('Complete your advert notification sent to User Id:'.($record['user_id'] ? $record['user_id'] : null), true);
        }
    }
    
    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function completeAdvertNotifications($input, $output)
    {
        $resultArr     = $this->getAdQueryBuilder(true, $input);
        $count  = $resultArr[0]['cnt'];

        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total users : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:send:complete-advert-notification '.$commandOptions;
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
     * @param boolean $onlyCount count only.
     * @param array $input input parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        if ($onlyCount) {
            $sql = 'SELECT count(id) as cnt ';
        } else {
            $sql = 'SELECT * ';
        }

        $sql .= ' FROM paa_lite_email_notification as '.PaaLiteEmailNotificationRepository::ALIAS.' WHERE UNIX_TIMESTAMP(date_add(FROM_UNIXTIME('.PaaLiteEmailNotificationRepository::ALIAS.'.created_at), interval +2 HOUR)) <= UNIX_TIMESTAMP(NOW()) AND '.PaaLiteEmailNotificationRepository::ALIAS.'.is_ad_confirmation_notification_sent = 0 ORDER BY '.PaaLiteEmailNotificationRepository::ALIAS.'.id ASC';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $arrResult = $stmt->fetchAll();
        return $arrResult;
    }
}
