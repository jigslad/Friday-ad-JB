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
use Fa\Bundle\AdBundle\Repository\AdImageRepository;

/**
 * This command is used to remove multiple images of affilliate ad images
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CleanAffiliateAdMultipleImagesCommand extends ContainerAwareCommand
{
    /**
     * Default entity manager
     *
     * @var object
     */
    private $em;

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
        ->setName('fa:clean:affiliate-multiple-images')
        ->setDescription("Remove multiple images of affilliate ad images")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Remove multiple images of affilliate ad images

Command:
 - php app/console fa:clean:affiliate-multiple-images
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
        $this->adFeedSiteArray = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->getFeedSiteRefSiteIdsArray();

        $offset = $input->getOption('offset');

        $searchParam                      = array();
        $searchParam['ad_feed']['status'] = 'A';
        $searchParam['ad']['affiliate']   = 1;

        if (isset($offset)) {
            $this->removeAffiliateMultipleImagesWithOffset($searchParam, $input, $output);
        } else {
            $output->writeln('Total ads:'.$this->getAdCount($searchParam), true);
            $this->removeAffiliateMultipleImages($searchParam, $input, $output);
        }
    }

    /**
     * Send ad edit live email with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function removeAffiliateMultipleImagesWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 10;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $feedAds = $qb->getQuery()->getResult();

        foreach ($feedAds as $feedAd) {
            $feedAdDetails = unserialize($feedAd->getAdText());
            $feedData = (isset($feedAdDetails['full_data']) ? unserialize($feedAdDetails['full_data']) : array());

            $feedAdDataPath = $this->getContainer()->getParameter('fa.feed.data.dir');
            $feedAdImagePath = $feedAdDataPath.'/images/';
            $refSiteId = $feedAd->getRefSiteId();

            if (isset($this->adFeedSiteArray[$refSiteId]) && isset($feedData['AdvertImages'])) {
                $feedAdImagePath .= $this->adFeedSiteArray[$refSiteId].'/'.substr($feedAd->getUniqueId(), 0, 8).'/';
                $feedAdImages = glob($feedAdImagePath.$feedAd->getUniqueId().'_*.jpg');
                $mainImage = null;
                foreach ($feedData['AdvertImages'] as $image) {
                    if ($image['IsMainImage']) {
                        $mainImage = $feedAd->getUniqueId().'_'.basename($image['Uri']);
                    }
                }
                if (count($feedAdImages) && $mainImage) {
                    foreach ($feedAdImages as $feedAdImage) {
                        if ($mainImage != basename($feedAdImage)) {
                            if (is_file($feedAdImage)) {
                                if (unlink($feedAdImage)) {
                                    $output->writeln('Image deleted for Ad: '.($feedAd->getAd() ? $feedAd->getAd()->getId() : '-').' - '.basename($feedAdImage), true);
                                } else {
                                    $output->writeln('Problem in deleting image for Ad: '.($feedAd->getAd() ? $feedAd->getAd()->getId() : '-').' - '.basename($feedAdImage), true);
                                }
                            } else {
                                $output->writeln('Image not found for Ad: '.($feedAd->getAd() ? $feedAd->getAd()->getId() : '-').' - '.basename($feedAdImage), true);
                            }
                        }
                    }
                }
            }
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
    protected function removeAffiliateMultipleImages($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 10;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:clean:affiliate-multiple-images '.$commandOptions;
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
        $adFeedRepository  = $this->em->getRepository('FaAdFeedBundle:AdFeed');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array('id' => 'asc'));
        //$data['static_filters'] = AdRepository::ALIAS.'.original_published_at > 0';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adFeedRepository, $data);
        $qb = $searchManager->getQueryBuilder();

        return $qb;
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
