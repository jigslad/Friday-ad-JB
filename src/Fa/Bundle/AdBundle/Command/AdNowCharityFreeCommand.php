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
 * @author Konda Reddy <kondar.reddy@fridaymediagroup.com>
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
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of the query', "256M")
            ->addArgument('emailid', null, InputArgument::REQUIRED, 'konda.reddy@fridaymediagroup.com')
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

        //get options passed in command
        $offset   = $input->getOption('offset');
        if (isset($offset)) {
            $this->adNowCharityFreeWithOffset($input, $output);
        } else {
            $this->adNowCharityFree($input, $output);
            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:process-email-queue --email_identifier="furniture_now_charity"';
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
        $ads          = $this->getAdQueryBuilder($offset, $step);

        foreach ($ads as $ad) {
            $userId = ($ad['id'] ? $ad['id'] : null);
            $userId = 1293152;
            $user = $this->em->getRepository('FaUserBundle:User')->find($userId);
            $ad = $this->em->getRepository('FaAdBundle:Ad')->find($ad['adid']);

            //send email only if ad has user and status is active.
            $userRoleId = $this->adUserRoleId($userId);
            $userRoleId = ($user ? $userRoleId : 0);

            if ($user && CommonManager::checkSendEmailToUser($userId, $this->getContainer()) && $userRoleId!=RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('furniture_now_charity', $user, $ad, $this->getContainer());
            }

            $ad->setIsRenewalMailSent(2);
            $this->em->persist($ad);
            $this->em->flush($ad);

            $output->writeln('Email added to queue for AD ID: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
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

            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:charity:now-charity-free'.$commandOptions;
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
        $query = 'SELECT u.*,a.id as adid
        from fridayad_prod_restore.user as u
        inner join fridayad_prod_restore.ad as a on a.user_id = u.id
        inner join  fridayad_prod_restore.ad_location as al on al.ad_id = a.id
        inner join fridayad_prod_restore.ad_user_package as aup on aup.user_id = u.id
        where a.status_id = 25
        AND aup.price = 0
        AND a.type_id = 4
        AND a.category_id in (159,160,165,178,188,194,198,203,204,205,206,214,218,221,223,224,225,229,230,231,236,239,246,247,261,264,265,266,267,268,269,270,272,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,305,306,310,311,312,313,318,319,320,321,322,323,327,329,330,331,332,336,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,358,359,360,4277,57,71,84,88,95,98,102,58,59,60,64,65,66,67,68,69,70,72,73,74,75,76,77,78,79,80,81,82,83,85,86,87,89,90,91,92,93,96,97,99,100,101)
        AND (al.town_id in (SELECT id from fridayad_prod_restore.location where id = 326 or parent_id = 326))
        AND a.created_at >= ( CURDATE() - INTERVAL 2 DAY ) group by u.id';
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
        $query = 'SELECT count(u.id) as count 
        from fridayad_prod_restore.user as u
        inner join fridayad_prod_restore.ad as a on a.user_id = u.id
        inner join  fridayad_prod_restore.ad_location as al on al.ad_id = a.id
        inner join fridayad_prod_restore.ad_user_package as aup on aup.user_id = u.id
        where a.status_id = 25
        AND aup.price = 0
        AND a.type_id = 4
        AND a.category_id in (159,160,165,178,188,194,198,203,204,205,206,214,218,221,223,224,225,229,230,231,236,239,246,247,261,264,265,266,267,268,269,270,272,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,305,306,310,311,312,313,318,319,320,321,322,323,327,329,330,331,332,336,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,358,359,360,4277,57,71,84,88,95,98,102,58,59,60,64,65,66,67,68,69,70,72,73,74,75,76,77,78,79,80,81,82,83,85,86,87,89,90,91,92,93,96,97,99,100,101)
        AND (al.town_id in (SELECT id from fridayad_prod_restore.location where id = 326 or parent_id = 326))
        AND a.created_at >= ( CURDATE() - INTERVAL 2 DAY ) group by u.id';
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchAll();
        return $count[0]['count'];
    }

    protected function adUserRoleId($userId){
        $query = "SELECT role_id FROM fridayad_prod_restore.user as a WHERE a.id =".$userId;
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $userRoleId = $stmt->fetch();
        return $userRoleId;
    }
}
