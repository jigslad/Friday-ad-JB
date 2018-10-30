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
 * This command is used to update published date same as renewed date if renewed date is greater than published date.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateAdPublishedDateCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-published-date')
        ->setDescription("Update date for ad weekly or monthly refresh.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update published date same as renewed date if renewed date is greater than published date.

Command:
 - php app/console fa:update:ad-published-date
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

        $searchParam = array();
        //$searchParam['entity_ad_status'] = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));

        if (isset($offset)) {
            $this->updateAdPublishedDateWithOffset($searchParam, $input, $output);
        } else {
            $this->updateAdPublishedDate($searchParam, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdPublishedDateWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = 0;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        foreach ($ads as $ad) {
            $ad->setPublishedAt($ad->getRenewedAt());
            $entityManager->persist($ad);
            $entityManager->flush($ad);

            $output->writeln('Published date is changed for ad id: '.$ad->getId(), true);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdPublishedDate($searchParam, $input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-published-date '.$commandOptions;
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
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');

        $data                   = array();
        $data['query_filters']  = $searchParam;
        $data['query_sorter']   = array('ad' => array('id' => 'asc'));
        $data['static_filters'] = AdRepository::ALIAS.'.renewed_at IS NOT NULL AND '.AdRepository::ALIAS.'.renewed_at > '.AdRepository::ALIAS.'.published_at';

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
