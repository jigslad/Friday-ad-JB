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
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * This command is used to send renew your ad alert to users for before given 1 day
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdNowCharityFreeCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
            ->setName('fa:charity:now-charity-free')
            ->setDescription("Send ad now in charity free.")
           // ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            //->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
            ->setHelp(
                <<<EOF
        Cron: To be setup.
        
        Actions:
        - Send ad now charity free before two day.
        
        Command:
         - php app/console fa:charity:now-charity-free
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

        if (isset($offset)) {
            $this->adNowCharityFreeWithOffset($input, $output);
        } else {
            $this->adNowCharityFree($input, $output);
            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:process-email-queue --email_identifier="now_charity_free"';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Send ad expiration alert before one day with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function adNowCharityFreeWithOffset($input, $output)
    {
        $step        = 100;
        $offset      = $input->getOption('offset');

        $qb          = $this->getAdQueryBuilder();
        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        foreach ($ads as $ad) {
            $user = ($ad->getUser() ? $ad->getUser() : null);

            //send email only if ad has user and status is active.
            $userRoleId = ($ad->getUser() ? $ad->getUser()->getRole()->getId() : 0);

            if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer()) && $userRoleId!=RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('ad_expires_tomorrow', $user, $ad, $this->getContainer());
            }

            $ad->setIsRenewalMailSent(2);
            $this->em->persist($ad);
            $this->em->flush($ad);

            $output->writeln('Renewal email added to queue for AD ID: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
        }
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send ad expiration alert before one day.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function adNowCharityFree( $input, $output)
    {
        $count     = $this->getAdCount();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:charity:now-charity-free '.$commandOptions;
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
    protected function getAdQueryBuilder()
    {
        $query = 'SELECT a.id FROM ad a left join ad_location al on a.id = al.ad_id where a.status_id = 25 AND a.is_paid_ad =0 AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13) ORDER BY a.id ASC';
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $ads = $stmt->fetchAll();
        return $ads;
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount()
    {
        $query = 'SELECT count(a.id) as count FROM ad a left join ad_location al on a.id = al.ad_id where a.status_id = 25 AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13) limit 5';
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchAll();
        return $count[0]['count'];
    }
}
