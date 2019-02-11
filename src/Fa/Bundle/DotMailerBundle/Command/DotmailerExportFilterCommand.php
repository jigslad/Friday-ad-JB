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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerFilterRepository;

/**
 * This command is used to update dotmailer data in bulk.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerExportFilterCommand extends ContainerAwareCommand
{
    private $response;

    private $httpcode;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:export-filter')
        ->setDescription("Export filter")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Filter id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'update for last few days only', null)
        ->addOption('is_24h_loop', null, InputOption::VALUE_OPTIONAL, 'is filter have 24 hour loop', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at 6am

Actions:
- Daily bulk upload the data to master address book.

Command:
 - php app/console fa:dotmailer:export-filter --id="xxxx"
 - php app/console fa:dotmailer:export-filter --last_days=7
 - php app/console fa:dotmailer:export-filter --last_days=30
 - php app/console fa:dotmailer:export-filter --is_24h_loop=1
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
        $lastDays = $input->getOption('last_days');
        $is24hLoop = $input->getOption('is_24h_loop');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        if ($ids) {
            $searchParam['dotmailer_filter'] = array('id' => $ids);
        } elseif ($is24hLoop) {
            $searchParam['dotmailer_filter']['is_24h_loop'] =  $is24hLoop;
            $searchParam['dotmailer_filter']['status'] = DotmailerFilterRepository::STATUS_SUCCESS;
        } elseif (!$lastDays) {
            $lastDays = 1;
        }

        if ($lastDays) {
            $date = date('d/m/Y', strtotime('-'.$lastDays.' day'));
            $searchParam['dotmailer_filter']['updated_at_from_to'] =  $date.'|'.$date;
        }

        if (isset($offset)) {
            $this->dotmailerExportFilterWithOffset($searchParam, $input, $output);
        } else {
            $this->dotmailerExportFilter($searchParam, $input, $output);
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
    protected function dotmailerExportFilterWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getDotmailerFilterQueryBuilder($searchParam);
        $step          = 1000;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $dotmailerFilters = $qb->getQuery()->getResult();

        if (count($dotmailerFilters) > 0) {
            foreach ($dotmailerFilters as $dotmailerFilter) {
                $dotmailerFilter = $this->createAddressBook($dotmailerFilter);
                $masterId = $dotmailerFilter->getAddressBookId();

                if ($masterId) {
                    /*$file = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/dotmailer/filter_".$dotmailerFilter->getId()."_".date('Ymd').".csv","w");
                    fputcsv($file, array('Email'));
                    $results = $this->executeFilter($dotmailerFilter);
                    foreach ($results as $result) {
                        fputcsv($file, array($result['email']));
                    }
                    fclose($file);
                    $this->sendRequest($masterId, $dotmailerFilter);
                    $entityManager->getRepository('FaDotMailerBundle:DotmailerFilter')->updateResponse($this->httpcode, serialize($this->response), $dotmailerFilter);*/

                    // call another command for result
                    /*$output->writeln('Import started for filter: '.$dotmailerFilter->getName());
                    $command = $this->getApplication()->find('fa:dotmailer:export-filter-result');
                    $arguments = array(
                        '--filterId' => $dotmailerFilter->getId(),
                        '--masterId' => $masterId,
                        '--criteria' => '\''.$dotmailerFilter->getFilters().'\'',

                    );
                    $inputN  = new ArrayInput($arguments);
                    $outputN = new NullOutput();
                    var_dump($inputN);
                    $returnCode = $command->run($inputN, $outputN);
                    if($returnCode == 0) {
                        $output->writeln('Import completed successfully for filter: '.$dotmailerFilter->getName());
                    } else {
                        $output->writeln('Import failed for filter: '.$dotmailerFilter->getName());
                    }*/
                    exec('nohup'.' '.$this->getContainer()->getParameter('fa.php.path').' '.$this->getContainer()->getParameter('project_path').'/console fa:dotmailer:export-filter-result --filterId='.$dotmailerFilter->getId().' --masterId='.$masterId.' --criteria=\''.$dotmailerFilter->getFilters().'\' >/dev/null &');
                }
            }
        }

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
    protected function dotmailerExportFilter($searchParam, $input, $output)
    {
        $count     = $this->getDotmailerFilterCount($searchParam);
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:dotmailer:export-filter '.$commandOptions;
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
     * Execute filter
     *
     * @param object $dotmailerFilter
     *
     * @return array
     */
    public function executeFilter($dotmailerFilter)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        // initialize search filter manager service and prepare filter data for searching
        $arr['search'] = unserialize($dotmailerFilter->getFilters());
        $this->getContainer()->get('fa.searchfilters.manager')->init($entityManager->getRepository('FaDotMailerBundle:Dotmailer'), $entityManager->getClassMetadata('FaDotMailerBundle:Dotmailer')->getTableName(), 'search', $arr);
        $data = $this->getContainer()->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
            'dotmailer' => array('email'),
        );

        $this->getContainer()->get('fa.sqlsearch.manager')->init($entityManager->getRepository('FaDotMailerBundle:Dotmailer'), $data);
        $queryBuilder = $this->getContainer()->get('fa.sqlsearch.manager')->getQueryBuilder();
        $query = $queryBuilder->getQuery();
        return $query->getArrayResult();
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
