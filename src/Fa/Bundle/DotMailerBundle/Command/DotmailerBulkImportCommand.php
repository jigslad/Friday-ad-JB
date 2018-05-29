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
class DotmailerBulkImportCommand extends ContainerAwareCommand
{
    private $response;

    private $httpcode;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:bulk-import')
        ->setDescription("Daily bulk upload the data to master address book.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('master_id', null, InputOption::VALUE_OPTIONAL, 'Master address book id', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Dotmailer id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'update for last few days only', null)
        ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'updated at start date in d/m/Y format', null)
        ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'updated at end date in d/m/Y format', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at 6am

Actions:
- Daily bulk upload the data to master address book.

Command:
 - php app/console fa:dotmailer:bulk-import --master_id="xxxx"
 - php app/console fa:dotmailer:bulk-import --last_days=7
 - php app/console fa:dotmailer:bulk-import --last_days=30
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
        $ids       = $input->getOption('id');
        $offset    = $input->getOption('offset');
        $lastDays  = $input->getOption('last_days');
        $masterId  = $input->getOption('master_id');
        $startDate = $input->getOption('start_date');
        $endDate   = $input->getOption('end_date');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        if (!$masterId) {
            $masterId = $this->getContainer()->getParameter('fa.dotmailer.master.addressbook.id');
        }

        if (!$lastDays) {
            $lastDays = 1;
        }

        if ($ids) {
            $searchParam['dotmailer'] = array('id' => $ids);
        } else if ($startDate && $endDate) {
            $searchParam['dotmailer']['updated_at_from_to'] =  $startDate.'|'.$endDate;
        } else if ($lastDays) {
            $date = date('d/m/Y', strtotime('-'.$lastDays.' day'));
            $searchParam['dotmailer']['updated_at_from_to'] =  $date.'|'.$date;
        }

        if (isset($offset)) {
            $this->dotmailerBulkImportWithOffset($searchParam, $masterId, $input, $output);
        } else {
            $this->dotmailerBulkImport($searchParam, $masterId, $input, $output);
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
    protected function dotmailerBulkImportWithOffset($searchParam, $masterId, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getDotmailerQueryBuilder($searchParam);
        $step          = 1000;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $dotmailers = $qb->getQuery()->getResult();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        if (count($dotmailers) > 0) {
            $dotmailerRepository         = $entityManager->getRepository('FaDotMailerBundle:Dotmailer');
            $dotmailerResponseRepository =  $entityManager->getRepository('FaDotMailerBundle:DotmailerResponse');
            $file = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/dotmailer/masteraddressbook_".date('Ymd').".csv", "w");
            fputcsv($file, $dotmailerRepository->generateDotmailerBulkImportLabelArray($this->getContainer()));
            foreach ($dotmailers as $dotmailer) {
                fputcsv($file, $dotmailerRepository->generateDotmailerBulkImportArray($dotmailer, $this->getContainer()));
                //fputcsv($file,explode(',',$line));
            }
            fclose($file);

            $this->sendRequest($masterId);

            $dotmailerResponseRepository->updateResponse($this->httpcode, serialize($this->response), DotmailerResponseRepository::DOTMAILER_RESPONSE_BULK_IMPORT);

            sleep(5);
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
    protected function dotmailerBulkImport($searchParam, $masterId, $input, $output)
    {
        $count     = $this->getDotmailerCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:dotmailer:bulk-import '.$commandOptions;
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
    protected function getDotmailerQueryBuilder($searchParam)
    {
        $entityManager       = $this->getContainer()->get('doctrine')->getManager();
        $dotMailerRepository = $entityManager->getRepository('FaDotMailerBundle:Dotmailer');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('dotmailer' => array ('updated_at' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($dotMailerRepository, $data);

        $qb = $searchManager->getQueryBuilder();
        $qb->andWhere(DotmailerRepository::ALIAS.'.opt_in = 1');
        $qb->andWhere(DotmailerRepository::ALIAS.'.is_suppressed = 0');

        return $qb;
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
        $qb = $this->getDotmailerQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Send request to ad moderation url.
     *
     * @param integer $masterId Id of master address book.
     *
     * @return boolean
     */
    public function sendRequest($masterId)
    {
        $url = $this->getContainer()->getParameter('fa.dotmailer.api.url').'/'.$this->getContainer()->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        //https://api.dotmailer.com/v2/address-books/{addressBookId}/contacts/import
        $url = $url.'address-books/'.$masterId.'/contacts/import';

        $username = $this->getContainer()->getParameter('fa.dotmailer.api.username');
        $password = $this->getContainer()->getParameter('fa.dotmailer.api.password');

        // Build the HTTP Request Headers
        $ch = curl_init($url);

        $data['FileName'] = 'masteraddressbook.csv';
        $data['Data']     = base64_encode(file_get_contents($this->getContainer()->get('kernel')->getRootDir()."/../data/dotmailer/masteraddressbook_".date('Ymd').".csv"));
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
