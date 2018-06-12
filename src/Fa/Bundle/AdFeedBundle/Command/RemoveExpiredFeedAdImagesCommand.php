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
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;

/**
 * This command is used to remove expired feed ads.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveExpiredFeedAdImagesCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

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
     * Ad feed site array
     *
     * @var array
     */
    private $adFeedSiteArray;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove:expired-feed-ad-images')
        ->setDescription("Remove expired feed ad images.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Ad feed status', 'E,R')
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to remove expired feed ad images.

Command:
 - php app/console fa:remove:expired-feed-ad-images
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
        $this->adFeedSiteArray      = $this->entityManager->getRepository('FaAdFeedBundle:AdFeedSite')->getFeedSiteRefSiteIdsArray();

        //update expired ad to ad_feed table
        $this->executeRawQuery("update ad_feed as af inner join ad as a on af.ad_id = a.id set af.status = 'E' where a.status_id != 25 and af.status = 'A';", $this->entityManager);
        //update archieve ad to ad_feed table
        $this->executeRawQuery("update ad_feed as af  inner join archive_ad as a on af.ad_id = a.ad_main set af.status = 'E' where af.status = 'A';", $this->entityManager);

        //get arguments passed in command
        $offset = $input->getOption('offset');
        $status = $input->getOption('status');
        $status = explode(',', $status);

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
            $output->writeln('Total expired ads: '.$this->getExpiredAdCount($status));
        }

        // insert ads statistics.
        if (isset($offset)) {
            $this->removeExpiredFeedAdImagesWithOffset($input, $output);
        } else {
            $this->removeExpiredFeedAdImages($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Remove expired feed ad images.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function removeExpiredFeedAdImages($input, $output)
    {
        $status = $input->getOption('status');
        $status = explode(',', $status);

        $count  = $this->getExpiredAdCount($status);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:remove:expired-feed-ad-images '.$commandOptions;
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
    protected function removeExpiredFeedAdImagesWithOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $status = $input->getOption('status');
        $status = explode(',', $status);

        $feedExpiredAds = $this->getExpiredAdResult($status, $offset, $this->limit);

        foreach ($feedExpiredAds as $feedExpiredAd) {
            $feedAdDataPath = $this->getContainer()->getParameter('fa.feed.data.dir');
            $feedAdImagePath = $feedAdDataPath.'/images/';
            $refSiteId = $feedExpiredAd->getRefSiteId();

            if (isset($this->adFeedSiteArray[$refSiteId])) {
                if (in_array($refSiteId, array(10, 11))) {
                    $group_dir = substr($feedExpiredAd->getUniqueId(), 0, 3);
                } else {
                    $group_dir =substr($feedExpiredAd->getUniqueId(), 0, 8);
                }
                $feedAdImagePath .= $this->adFeedSiteArray[$refSiteId].'/'.$group_dir.'/';
                $expiredAdImages = glob($feedAdImagePath.$feedExpiredAd->getUniqueId().'_*.jpg');
                if (count($expiredAdImages)) {
                    foreach ($expiredAdImages as $expiredAdImage) {
                        if (is_file($expiredAdImage)) {
                            if (unlink($expiredAdImage)) {
                                $output->writeln('Image deleted for Ad: '.($feedExpiredAd->getAd() ? $feedExpiredAd->getAd()->getId() : '-').' - '.basename($expiredAdImage), true);
                            } else {
                                $output->writeln('Problem in deleting image for Ad: '.($feedExpiredAd->getAd() ? $feedExpiredAd->getAd()->getId() : '-').' - '.basename($expiredAdImage), true);
                            }
                        }else {
                            $output->writeln('Image not found for Ad: '.($feedExpiredAd->getAd() ? $feedExpiredAd->getAd()->getId() : '-').' - '.basename($expiredAdImage), true);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get query builder for expired ads count.
     *
     * @param array $status Status array.
     *
     * @return count
     */
    protected function getExpiredAdCount($status)
    {
        $adFeedRepository  = $this->entityManager->getRepository('FaAdFeedBundle:AdFeed');

        $query = $adFeedRepository->getBaseQueryBuilder()
            ->select('COUNT('.AdFeedRepository::ALIAS.'.id)')
            ->andWhere(AdFeedRepository::ALIAS.'.status IN (:status)')
            ->setParameter('status', $status);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get query builder for expired ads.
     *
     * @param array   $status Status array.
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getExpiredAdResult($status, $offset, $limit)
    {
        $adFeedRepository  = $this->entityManager->getRepository('FaAdFeedBundle:AdFeed');

        $query = $adFeedRepository->getBaseQueryBuilder()
        ->andWhere(AdFeedRepository::ALIAS.'.status IN (:status)')
        ->setParameter('status', $status)
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        return $query->getQuery()->getResult();
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
}
