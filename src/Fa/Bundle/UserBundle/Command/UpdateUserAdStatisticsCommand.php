<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Entity\UserStatistics;

/**
 * This command is used to update user statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserAdStatisticsCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:user-ad-statistics')
        ->setDescription("Update user ad statistics.")
        ->addArgument('action', InputArgument::REQUIRED, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update user statistics for all ads or for previos day.

Command:
 - php app/console fa:update:user-ad-statistics all
 - php app/console fa:update:user-ad-statistics beforeoneday
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
        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateUserTotalAdCountWithOffset($input, $output);
        } else {
            $this->updateUserTotalAdCount($input, $output);
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
        $count     = $this->getUserTotalAdCount($action);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:user-ad-statistics '.$commandOptions.' '.$input->getArgument('action');
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
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserTotalAdCountWithOffset($input, $output)
    {
        $action = $input->getArgument('action');
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $userStatisticRepository = $entityManager->getRepository('FaUserBundle:UserStatistics');
        $userRepository = $entityManager->getRepository('FaUserBundle:User');

        $offset = $input->getOption('offset');

        $userTotalAdCounts = $this->getUserAdCountResult($action, $offset, $this->limit);

        foreach ($userTotalAdCounts as $userTotalAdCount) {
            $userStatistic = $userStatisticRepository->findOneBy(array('user' => $userTotalAdCount['user_id']));
            if (!$userStatistic) {
                $userStatistic = new UserStatistics();
                $userStatistic->setUser($userRepository->find($userTotalAdCount['user_id']));
            }

            if ($action == 'beforeoneday') {
                $userStatistic->setTotalAd($userStatistic->getTotalAd() + $userTotalAdCount['ad_count']);
            } else {
                $userStatistic->setTotalAd($userTotalAdCount['ad_count']);
            }
            $entityManager->persist($userStatistic);
        }

        $entityManager->flush();
        $entityManager->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for ads.
     *
     * @param string $action       Action name.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getUserTotalAdCount($action, $searchParams = array())
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adTableName   = $entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
        $where         = '';
        if ($action == 'beforeoneday') {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime(' -1 day')));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime(' -1 day')));
            $where = ' WHERE ('.AdRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')';
        }

        $sql = 'SELECT COUNT(*) as total_user
            FROM (
                SELECT COUNT('.AdRepository::ALIAS.'.id)
                FROM '.$adTableName.' as '.AdRepository::ALIAS.'
                '.$where.'
                GROUP BY '.AdRepository::ALIAS.'.user_id
            ) '.$adTableName;

        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $useradCount = $stmt->fetch();

        return $useradCount['total_user'];
    }

    /**
     * Get user ad count results.
     *
     * @param string  $action      Action name.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserAdCountResult($action, $offset, $limit, $searchParam = array())
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder()
        ->select('COUNT('.AdRepository::ALIAS.') as ad_count')
        ->addSelect(UserRepository::ALIAS.'.id as user_id')
        ->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS)
        ->groupBy(UserRepository::ALIAS.'.id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        if ($action == 'beforeoneday') {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime(' -1 day')));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime(' -1 day')));
            $query->andWhere('('.AdRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        return $query->getQuery()->getArrayResult();
    }
}
