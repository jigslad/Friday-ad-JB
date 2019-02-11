<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerFilterRepository;

/**
 * This command is used to update dotmailer data in bulk.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerExportFailedFilterCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:export-failed-filter')
        ->setDescription("Export filter")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Filter id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at 6am

Actions:
- Daily bulk upload the data to master address book.

Command:
 - php app/console fa:dotmailer:export-failed-filter --id="xxxx"
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
        $searchParam = array();

        //get options passed in command
        $ids      = $input->getOption('id');
        $offset   = $input->getOption('offset');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        if ($ids) {
            $searchParam['dotmailer_filter'] = array('id' => $ids);
        }

        $searchParam['dotmailer_filter']['status'] = DotmailerFilterRepository::STATUS_FAILED;
        if (isset($offset)) {
            $this->dotmailerExportFailedFilterWithOffset($searchParam, $input, $output);
        } else {
            $this->dotmailerExportFailedFilter($searchParam, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array   $searchParam Search parameters.
     * @param integer $masterId    Id of master address book.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerExportFailedFilterWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getDotmailerFilterQueryBuilder($searchParam);
        $step          = 1;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $dotmailerFilters = $qb->getQuery()->getResult();

        if (count($dotmailerFilters) > 0) {
            foreach ($dotmailerFilters as $dotmailerFilter) {
                $dotmailerFilter = $this->createAddressBook($dotmailerFilter);
                $masterId = $dotmailerFilter->getAddressBookId();
                $dotmailerFilter->setFailedRetryCount($dotmailerFilter->getFailedRetryCount()+1);
                $entityManager->persist($dotmailerFilter);
                $entityManager->flush($dotmailerFilter);
                if ($masterId) {
                    $output->writeln('Failed filter re-processed: '.$dotmailerFilter->getId(), true);
                    exec('nohup'.' '.$this->getContainer()->getParameter('fa.php.path').' '.$this->getContainer()->getParameter('project_path').'/console fa:dotmailer:export-filter-result --filterId='.$dotmailerFilter->getId().' --masterId='.$masterId.' --criteria=\''.$dotmailerFilter->getFilters().'\' >/dev/null &');
                }
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        sleep(120);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array   $searchParam Search parameters.
     * @param integer $masterId    Id of master address book.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerExportFailedFilter($searchParam, $input, $output)
    {
        $count     = $this->getDotmailerFilterCount($searchParam);
        $output->writeln('Total filters: '.$count, true);
        $step      = 1;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i < $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:dotmailer:export-failed-filter '.$commandOptions;
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
     * Create address book.
     *
     * @param object $dotmailerFilter
     *
     * @return integer
     */
    private function createAddressBook($dotmailerFilter)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        // check whether address book already exists
        if (!$dotmailerFilter->getAddressBookId()) {
            // create new address book
            $createAddressBook = $this->getContainer()->get('fa.dotmailer.createaddressbook.resource');
            if ($createAddressBook->create($dotmailerFilter->getId(), $dotmailerFilter->getName(), 'Private')) {
                $responseBody = $createAddressBook->getResponseBody();
                $responseBody = json_decode($responseBody, true);
                if (isset($responseBody['id'])) {
                    $dotmailerFilter->setAddressBookId($responseBody['id']);
                    $entityManager->persist($dotmailerFilter);
                    $entityManager->flush($dotmailerFilter);
                }
            }
        }

        return $dotmailerFilter;
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerFilterQueryBuilder($searchParam)
    {
        $entityManager             = $this->getContainer()->get('doctrine')->getManager();
        $dotMailerFilterRepository = $entityManager->getRepository('FaDotMailerBundle:DotmailerFilter');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        //$data['static_filters'] = DotmailerFilterRepository::ALIAS.'.failed_retry_count < 3';
        //$data['query_sorter']  = array('dotmailer_filter' => array ('updated_at' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($dotMailerFilterRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for dotmailer.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerFilterCount($searchParam)
    {
        $qb = $this->getDotmailerFilterQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
