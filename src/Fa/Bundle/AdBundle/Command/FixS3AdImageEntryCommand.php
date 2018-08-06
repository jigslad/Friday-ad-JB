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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\AdBundle\Entity\Ad;

/**
 * This command is used to fix s3 ad image entries.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FixS3AdImageEntryCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 10;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:fix:s3-ad-image-entry')
        ->setDescription("Fix s3 ad image entries.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to fix s3 ad image entries.

Command:
 - php app/console fa:fix:s3-ad-image-entry
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
        // set entity manager.
        $this->entityManager        = $this->getContainer()->get('doctrine')->getManager();
        $this->mainDbName           = $this->getContainer()->getParameter('database_name');

        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        // insert ads statistics.
        if (isset($offset)) {
            $this->fixAdEntryWithOffset($input, $output);
        } else {
            $output->writeln('Total entries to process: '.$this->getAdCount(), true);
            $this->fixAdEntry($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Update user total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function fixAdEntry($input, $output)
    {
        $count  = $this->getAdCount();

        for ($i = 0; $i < $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:fix:s3-ad-image-entry '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function fixAdEntryWithOffset($input, $output)
    {
        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');
        $offset = 0;
        $ads = $this->getAdResult($offset, $this->limit);
        foreach ($ads as $ad) {
            if ($this->is_url_exist('http://friday-ad.s3-website-eu-west-1.amazonaws.com/'.$ad['path'].'/'.$ad['image_name'].'_300X225.jpg')) {
                $output->writeln('Updating records in ad_image for ad: '.$ad['ad_id'].'('.$ad['id'].')', true);
                $updateSql = 'update ad_image set aws = 1, local = 0 where id = '.$ad['id'].';';
                $this->executeRawQuery($updateSql, $this->entityManager);
                $adObj = $adRepository->find($ad['ad_id']);
                $this->updateAdToSolr($adObj);
            }
        }
    }

    /**
     * Get query builder for ads.
     *
     * @return count
     */
    protected function getAdCount()
    {
        list($startDate, $endDate) = $this->getDateInTimeStamp(strtotime('-1 day'));
        $sql = 'select count(ai.id) as total from ad_image as ai inner join ad as a on ai.ad_id = a.id where local = 1 and aws = 0 and a.status_id = 25  and ai.created_at <= '.$endDate.';';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $countRes = $stmt->fetch();

        return $countRes['total'];
    }

    /**
     * Get user ad count results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdResult($offset, $limit)
    {
        list($startDate, $endDate) = $this->getDateInTimeStamp(strtotime('-1 day'));
        $sql = 'select ai.id, ai.ad_id,ai.path,ai.image_name from ad_image as ai inner join ad as a on ai.ad_id = a.id where local = 1 and aws = 0 and a.status_id = 25  and ai.created_at <= '.$endDate.' order by ai.id desc limit '.$offset.','.$limit.';';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get date in time stamp
     *
     * @param string $date Date.
     *
     * @return array
     */
    private function getDateInTimeStamp($date)
    {
        $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', $date));
        $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', $date));

        return array($startDate, $endDate);
    }

    private function is_url_exist($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($code == 200){
            $status = true;
        }else{
            $status = false;
        }
        curl_close($ch);
        return $status;
    }

    /**
     * Update solr index.
     *
     * @param Ad $ad
     *
     * return boolean
     */
    public function updateAdToSolr(Ad $ad)
    {
        $solrClient = $this->getContainer()->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $adSolrIndex = $this->getContainer()->get('fa.ad.solrindex');
        return $adSolrIndex->update($solrClient, $ad, $this->getContainer(), false);
    }
}
