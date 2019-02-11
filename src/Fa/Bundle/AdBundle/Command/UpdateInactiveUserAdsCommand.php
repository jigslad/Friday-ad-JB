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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to inactive ads which users are already inactivated.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateInactiveUserAdsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:inactive-user-ads')
        ->setDescription("Update inactive user ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User id', 0)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update inactive user's adverts.

Command:
 - php app/console fa:update:inactive-user-ads
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        //get options passed in command
        $offset   = $input->getOption('offset');

        $searchParam = null;

        if (isset($offset)) {
            $this->updateAdStatusWithOffset($input, $output);
        } else {
            $this->updateAdStatus($input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdStatusWithOffset($input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = $input->getOption('offset');
        $em          = $this->getContainer()->get('doctrine')->getManager();

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $objAds = $qb->getQuery()->execute();
        foreach ($objAds as $objAd) {
            $objAd->setIsBlockedAd(1);
            $this->em->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByAdId($objAd->getId(), $this->getContainer());
            $this->em->persist($objAd);
            $this->em->flush();
            $output->writeln('Inactivation process is completed for ad id: '.$objAd->getId(), true);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update solr index.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdStatus($input, $output)
    {
        $qb        = $this->getAdQueryBuilder(true, $input);
        $count     = $qb->getQuery()->getSingleScalarResult();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:inactive-user-ads '.$commandOptions;
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
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        $userId = intval($input->getOption('user_id'));
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdRepository::ALIAS.'.id)');
        }

        $qb->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS, 'WITH', AdRepository::ALIAS.'.user = '.UserRepository::ALIAS.'.id')
           ->where(UserRepository::ALIAS.'.status <> :userStatus')
           ->setParameter('userStatus', EntityRepository::USER_STATUS_ACTIVE_ID);
        
        if (!empty($userId)) {
            $qb->andWhere(UserRepository::ALIAS.'.id = :userId')
               ->setParameter('userId', $userId);
        }
        return $qb;
    }
}
