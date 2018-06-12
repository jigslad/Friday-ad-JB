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
use Fa\Bundle\DotMailerBundle\Repository\DotmailerFilterRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;

/**
 * This command is used to update dotmailer data in bulk.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerExportUpdateStatusCommand extends ContainerAwareCommand
{
    private $response;

    private $httpcode;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:export-update-status')
        ->setDescription("Update status of exported filter")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Filter id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run every 15 minute

Actions:
- Daily update the status of exported filter.

Command:
 - php app/console fa:dotmailer:export-update-status --id="xxxx"
 - php app/console fa:dotmailer:export-update-status
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
        } else {
            $searchParam['dotmailer_filter']['status'] = array(DotmailerFilterRepository::STATUS_SENT, DotmailerFilterRepository::STATUS_FAILED);
        }

        if (isset($offset)) {
            $this->dotmailerExportUpdateStatusWithOffset($searchParam, $input, $output);
        } else {
            $this->dotmailerExportUpdateStatus($searchParam, $input, $output);
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
    protected function dotmailerExportUpdateStatusWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getDotmailerFilterQueryBuilder($searchParam);
        $step          = 1000;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $dotmailerFilters = $qb->getQuery()->getResult();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        if (count($dotmailerFilters) > 0) {

            foreach ($dotmailerFilters as $dotmailerFilter) {

                $value = array();
                if ($dotmailerFilter->getValue()) {
                    $value = json_decode($dotmailerFilter->getValue(), true);
                }
                if (isset($value['id'])) {
                    $this->sendRequest($value['id'], $dotmailerFilter);
                    if ($this->httpcode == '200') {
                        $responseBody = json_decode($this->response, true);
                        if (isset($responseBody['status'])) {
                            switch ($responseBody['status']) {
                                case 'Finished':
                                    $dotmailerFilter->setStatus(DotmailerFilterRepository::STATUS_SUCCESS);
                                    break;
                                case 'NotFinished':
                                    break;
                                default:
                                    $dotmailerFilter->setStatus(DotmailerFilterRepository::STATUS_FAILED);
                                    break;
                            }
                        }
                        $entityManager->persist($dotmailerFilter);
                        $entityManager->flush($dotmailerFilter);
                    }
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
    protected function dotmailerExportUpdateStatus($searchParam, $input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:dotmailer:export-update-status '.$commandOptions;
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
     * Send request to get updated status to export filter in dotmailer.
     *
     * @param integer $id              Id of dotmailer import filter.
     * @param object  $dotmailerFilter Object.
     *
     * @return boolean
     */
    public function sendRequest($id, $dotmailerFilter)
    {
        $url = $this->getContainer()->getParameter('fa.dotmailer.api.url').'/'.$this->getContainer()->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        //https://api.dotmailer.com/v2/address-books/{addressBookId}/contacts/import
        $url = $url.'contacts/import/'.$id;

        $username = $this->getContainer()->getParameter('fa.dotmailer.api.username');
        $password = $this->getContainer()->getParameter('fa.dotmailer.api.password');

        // Build the HTTP Request Headers
        $ch = curl_init($url);

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
