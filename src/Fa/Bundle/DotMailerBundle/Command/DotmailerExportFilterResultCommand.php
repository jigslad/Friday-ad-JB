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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerResponseRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;

/**
 * This command is used to update dotmailer data in bulk.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerExportFilterResultCommand extends ContainerAwareCommand
{
    private $response;

    private $httpcode;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:export-filter-result')
        ->setDescription("Export filter result")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('criteria', null, InputOption::VALUE_REQUIRED, 'Filter criteria in serialized formate', null)
        ->addOption('filterId', null, InputOption::VALUE_REQUIRED, 'Id of dotmailer filter', null)
        ->addOption('masterId', null, InputOption::VALUE_REQUIRED, 'Id of master address book', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Total result of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run as a child cron from dotamailer export filter

Actions:
- Upload the data to dotmailer.

Command:
 - php app/console fa:dotmailer:export-filter-result --criteria="xxxx"
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
        $criteria      = $input->getOption('criteria');
        $offset   = $input->getOption('offset');

        if ($criteria) {
            $searchParam = unserialize($criteria);
        }

        //exit;

        if (isset($offset)) {
            $this->dotmailerExportFilterResultWithOffset($searchParam, $input, $output);
        } else {
            $this->dotmailerExportFilterResult($searchParam, $input, $output);
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
    protected function dotmailerExportFilterResultWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getDotmailerQueryBuilder($searchParam);
        $step          = 50000;
        $offset        = $input->getOption('offset');
        $filterId      = $input->getOption('filterId');
        $masterId      = $input->getOption('masterId');
        $count         = $input->getOption('count');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $results = $qb->getQuery()->getArrayResult();

        $csvFile = $this->getContainer()->get('kernel')->getRootDir()."/../data/dotmailer/filter_".$filterId."_".date('Ymd').".csv";

        //if (!$offset) {
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
        //}

        $file = fopen($csvFile, "w");
        fputcsv($file, array('Email'));
        foreach ($results as $result) {
            fputcsv($file, array($result['email']));
        }
        fclose($file);

        //if ($masterId && $filterId && ($offset+10000) >= $count) {
        $dotmailerFilter = $entityManager->getRepository('FaDotMailerBundle:DotmailerFilter')->findOneBy(array('id' => $filterId));
        $this->sendRequest($masterId, $dotmailerFilter);
        $entityManager->getRepository('FaDotMailerBundle:DotmailerFilter')->updateResponse($this->httpcode, $this->response, $dotmailerFilter);
        //}

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array   $searchParam Search parameters.
     * @param integer $masterId    Id of master address book.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerExportFilterResult($searchParam, $input, $output)
    {
        $count     = $this->getDotmailerCount($searchParam);
        $step      = 50000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total export emails : '.$count, true);
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
                    if ($option == 'criteria') {
                        $commandOptions .= ' --'.$option.'=\''.$value.'\'';
                    } else {
                        $commandOptions .= ' --'.$option.'="'.$value.'"';
                    }
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            if (isset($count)) {
                $commandOptions .= ' --count='.$count;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:dotmailer:export-filter-result '.$commandOptions;
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
    protected function getDotmailerQueryBuilder($searchParam, $isCount = false)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $sqlSearchParameters = array();
        $sqlSearchParameters['keywords'] = '';
        $sqlSearchParameters['sort_field'] = '';
        $sqlSearchParameters['sort_ord'] = '';
        $sqlSearchParameters['page']  = 1;

        // initialize search filter manager service and prepare filter data for searching
        $sqlSearchParameters['search'] = $searchParam;

        $this->getContainer()->get('fa.searchfilters.manager')->init($entityManager->getRepository('FaDotMailerBundle:Dotmailer'), $entityManager->getClassMetadata('FaDotMailerBundle:Dotmailer')->getTableName(), 'search', $sqlSearchParameters);
        $data = $this->getContainer()->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
            'dotmailer' => array('email'),
        );

        $this->getContainer()->get('fa.sqlsearch.manager')->init($entityManager->getRepository('FaDotMailerBundle:Dotmailer'), $data);
        $queryBuilder = $this->getContainer()->get('fa.sqlsearch.manager')->getQueryBuilder();

        $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.opt_in = 1');
        $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.is_suppressed = 0');

        if (isset($searchParam['dotmailer__fad_user']) && isset($searchParam['dotmailer__ti_user'])) {
            $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.fad_user = 1 OR '.DotmailerRepository::ALIAS.'.ti_user = 1');
        } elseif (isset($searchParam['dotmailer__fad_user'])) {
            $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.fad_user = 1');
        } elseif (isset($searchParam['dotmailer__ti_user'])) {
            $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.ti_user = 1');
        }

        if (!$isCount) {
            $queryBuilder->distinct();
        }

        return $queryBuilder;
    }

    /**
     * Get query builder for dotmailer.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerCount($searchParam)
    {
        $qb = $this->getDotmailerQueryBuilder($searchParam, true);
        $qb->select('COUNT(DISTINCT '.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Send request to ad moderation url.
     *
     * @param integer $masterId        Id of master address book.
     * @param object  $dotmailerFilter Object.
     *
     * @return boolean
     */
    public function sendRequest($masterId, $dotmailerFilter)
    {
        $url = $this->getContainer()->getParameter('fa.dotmailer.api.url').'/'.$this->getContainer()->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        //https://api.dotmailer.com/v2/address-books/{addressBookId}/contacts/import
        $url = $url.'address-books/'.$masterId.'/contacts/import';

        $username = $this->getContainer()->getParameter('fa.dotmailer.api.username');
        $password = $this->getContainer()->getParameter('fa.dotmailer.api.password');

        // Build the HTTP Request Headers
        $ch = curl_init($url);

        $data['FileName'] = $dotmailerFilter->getName();
        $data['Data']     = base64_encode(file_get_contents($this->getContainer()->get('kernel')->getRootDir()."/../data/dotmailer/filter_".$dotmailerFilter->getId()."_".date('Ymd').".csv"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));


        //curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $this->response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->response = $body = substr($this->response, $header_size);

        $this->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
    }
}
