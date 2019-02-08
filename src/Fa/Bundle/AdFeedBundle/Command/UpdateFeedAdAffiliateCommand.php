<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;
use Fa\Bundle\ReportBundle\Repository\AdReportDailyRepository;
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;
use Fa\Bundle\ReportBundle\Repository\AdPrintReportDailyRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;

/**
 * This command is used to update affiliate logo of feed ad.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateFeedAdAffiliateCommand extends ContainerAwareCommand
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
     * Search parameters
     *
     * @var array
     */
    private $searchParam = array();

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:feed-ad-affiliate-logo')
        ->setDescription("Update feed ad affiliate logo.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('ad_id', null, InputOption::VALUE_REQUIRED, 'Ad IDs', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to Update feed ad affiliate logo.

Command:
 - php app/console fa:update:feed-ad-affiliate-logo
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
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();

        //get arguments passed in command
        $offset = $input->getOption('offset');

        $this->searchParam = array();

        if ($input->getOption('ad_id')) {
            $this->searchParam['ad_id'] = $input->getOption('ad_id');
        }

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        if (isset($offset)) {
            $this->updateFeedAdAffiliateLogoWithOffset($input, $output);
        } else {
            $output->writeln('Total entries to process: '.$this->getAdCount(), true);
            $this->updateFeedAdAffiliateLogo($input, $output);
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
     * Update total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateFeedAdAffiliateLogo($input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:feed-ad-affiliate-logo '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update feed ads with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateFeedAdAffiliateLogoWithOffset($input, $output)
    {
        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');
        $offset = 0;
        $ads = $this->getAdResult($offset, $this->limit);
        foreach ($ads as $ad) {
            try {
                $adObj = $adRepository->findOneBy(array('id' => $ad['ad_id']));
                $adText = unserialize($ad['ad_text']);
                $fullData = (isset($adText['full_data']) ? unserialize($adText['full_data']) : array());
                $advertSource = null;
                if ($adObj && count($fullData)) {
                    if (isset($fullData['SiteVisibility']) && is_array($fullData['SiteVisibility'])) {
                        foreach ($fullData['SiteVisibility'] as $site) {
                            if (($site['IsMainSite'] === 'true') || ($site['IsMainSite'] === true)) {
                                $advertSource = CommonManager::addHttpToUrl($site['Site']);
                            }
                        }
                    }
                    if ($advertSource) {
                        $adObj->setSource($advertSource);
                        $this->entityManager->persist($adObj);
                        $this->entityManager->flush($adObj);
                        $output->writeln('Ad feed affiliate logo updated : '.$ad['ad_id'], true);
                    }
                }
            } catch (\Exception $e) {
                $output->writeln('Error occurred during subtask'.$ad['ad_id'], true);
                $output->writeln($e->getMessage(), true);
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
        $whereSql = '';
        if (isset($this->searchParam['ad_id'])) {
            $whereSql .= ' AND af.ad_id IN ('.$this->searchParam['ad_id'].')';
        }
        $sql = 'SELECT COUNT(af.id) as total FROM  `ad_feed` af INNER JOIN ad a ON af.ad_id = a.id WHERE a.affiliate = "1" AND (a.source like "%Not Specified%" OR a.source like "%Kapow Scrape%")'.$whereSql;

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $countRes = $stmt->fetch();

        return $countRes['total'];
    }

    /**
     * Get ad count results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdResult($offset, $limit)
    {
        $whereSql = '';
        if (isset($this->searchParam['ad_id'])) {
            $whereSql .= ' AND af.ad_id IN ('.$this->searchParam['ad_id'].')';
        }
        $sql = 'SELECT a.id as ad_id, af.ad_text FROM  `ad_feed` af INNER JOIN ad a ON af.ad_id = a.id WHERE a.affiliate = "1" AND (a.source like "%Not Specified%" OR a.source like "%Kapow Scrape%") '.$whereSql.' LIMIT '.$limit.' OFFSET '.$offset.';';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
