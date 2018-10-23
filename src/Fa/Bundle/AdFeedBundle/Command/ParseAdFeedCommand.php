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
use Doctrine\DBAL\Logging\EchoSQLLogger;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:parse:feed parse  --type="boat" --site_id="1"
 * php app/console fa:parse:feed parse  --type="gun"  --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ParseAdFeedCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:feed:parse')
        ->setDescription('Parse feed file for given type and modified time')
        ->addArgument('action', InputArgument::REQUIRED, 'parse or image')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', '512M')
        ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Ad type', null)
        ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Ad type', null)
        ->addOption('site_id', null, InputOption::VALUE_REQUIRED, 'Referance site id', null)
        ->addOption('modified_since', null, InputOption::VALUE_OPTIONAL, 'modified since', null)
        ->addOption('force', null, InputOption::VALUE_REQUIRED, 'Referance site id', null);
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $feedReader = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $type           = $input->getOption('type');
        $site_id        = $input->getOption('site_id');

        if (!in_array($type, array('BoatAdvert', 'ClickEditVehicleAdvert', 'HorseAdvert', 'PropertyAdvert', 'PetAdvert', 'MerchandiseAdvert', 'MotorhomeAdvert', 'LivestockAdvert', 'JobAdvert', 'TradeIt', 'Wightbay', 'BusinessAdvert', 'CaravanAdvert'))) {
            $output->writeln('Invalid ad type argument', true);
            return false;
        }

        if (!in_array($site_id, array(10, 1, 2))) {
            $output->writeln('Invalid site argument', true);
            return false;
        }

        $modified_since = $this->getLastModifiedTime($type, $site_id, 'P');

        if (!$modified_since) {
            $output->writeln('No pending parsing found', true);
            return false;
        }

        $modified_since = $modified_since->getModifiedSince();

        echo "Modified time".$modified_since->format('Y-m-d H:i:s')."\n";

        switch ($input->getArgument('action'))
        {
            case 'image':
                if ($input->getOption('file')) {
                    $feedReader->downloadImage($input->getOption('type'), $input->getOption('file'), $input->getOption('site_id'), $modified_since, $input->getOption('force'));
                    echo 'Images downloaded for '.$input->getOption('file')."\n";
                    exit;
                }

                $files = $this->getFeedFiles($type, $modified_since, $site_id);
                foreach ($files as $file) {
                    $commandOptions = null;

                    foreach ($input->getOptions() as $option => $value) {
                        if ($option == 'verbose') {
                            $commandOptions .= ' --'.$option;
                        } elseif ($value) {
                            $commandOptions .= ' --'.$option.'="'.$value.'"';
                        }
                    }

                    $commandOptions .= ' --file='.$file;

                    $memoryLimit = '';
                    if ($input->hasOption('memory_limit') && $input->getOption('memory_limit')) {
                        $memoryLimit = ' -d memory_limit='.$input->getOption('memory_limit');
                    }

                    $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:feed:parse '.$commandOptions.' '.$input->getArgument('action');
                    $output->writeln($command, true);
                    passthru($command, $returnVar);
                    if ($returnVar !== 0) {
                        $output->writeln('Error occurred during subtask', true);
                    }


                }
                break;
            case 'parse':

                if ($input->getOption('file')) {
                    $feedReader->parseJsonFile($input->getOption('type'), $input->getOption('file'), $input->getOption('site_id'), $modified_since, $input->getOption('force'));
                    echo 'parse for '.$input->getOption('file')."\n";
                    exit;
                }

                $ad_feed_site = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $site_id));
                $this->resetIsUpdated($ad_feed_site);

                $files = $this->getFeedFiles($type, $modified_since, $site_id);

                $ad_feed_site_download = $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->findOneBy(array('modified_since' => $modified_since, 'status' => 'P', 'ad_feed_site' => $ad_feed_site));
                date_default_timezone_set('UTC');
                $run_time = gmdate('Y-m-d\TH:i:s\Z', strtotime('-1 hour'));
                date_default_timezone_set(ini_get('date.timezone'));
                $lastRunTime = new \DateTime($run_time);
                $ad_feed_site_download->setLastRunTime($lastRunTime);
                $this->em->persist($ad_feed_site_download);
                $this->em->flush();

                foreach ($files as $file) {
                    $commandOptions = null;
                    foreach ($input->getOptions() as $option => $value) {
                        if ($option == 'verbose') {
                            $commandOptions .= ' --'.$option;
                        } elseif ($value) {
                            $commandOptions .= ' --'.$option.'="'.$value.'"';
                        }
                    }

                    $commandOptions .= ' --file='.$file;

                    $memoryLimit = '';
                    if ($input->hasOption('memory_limit') && $input->getOption('memory_limit')) {
                        $memoryLimit = ' -d memory_limit='.$input->getOption('memory_limit');
                    }

                    $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:feed:parse '.$commandOptions.' '.$input->getArgument('action');
                    $output->writeln($command, true);
                    passthru($command, $returnVar);
                    if ($returnVar !== 0) {
                        $output->writeln('Error occurred during subtask', true);
                    }
                }

                break;
              case 'remap':
                  $ad_feed_site = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $site_id));
                  $this->remapAll($ad_feed_site);
                  break;
        }

        echo "\n"."Command Ended At: ".date('Y-m-d H:i:s', time())."\n"."\n";
    }


    /**
     * Parse json file.
     *
     * @param string  $type           Ad type.
     * @param string  $modifiedSince  Modified since given time.
     * @param integer $siteID         Site id.
     */
    public function getFeedFiles($type, $modifiedSince, $siteID)
    {
        $adFeedSite = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $siteID));

        if ($adFeedSite) {
            $adSiteDownload = $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->findOneBy(array('modified_since' => $modifiedSince, 'ad_feed_site' => $adFeedSite->getId(), 'status' => 'P'));
            return unserialize($adSiteDownload->getFiles());
        } else {
            return \Exception("Something invalid");
        }
    }

    /**
     * Get last modified time.
     *
     * @param string  $type
     * @param integer $site_id
     * @param string  $status
     */
    public function getLastModifiedTime($type, $site_id, $status = "P")
    {
        $ad_feed_site = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $site_id));

        return $this->getContainer()->get('doctrine')->getManager()->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->getLatestModifiedTime($ad_feed_site->getId(), $status);
    }

    protected function resetIsUpdated($ad_feed_site)
    {
        // handle deadlock for 3 times
        $retry = 0;
        $notdone = true;
        while ($notdone && $retry < 3) {
            try {
                $query = 'UPDATE ad_feed SET is_updated = 0 WHERE ref_site_id = "'.$ad_feed_site->getId().'"';
                $stmt = $this->em->getConnection()->prepare($query);
                $stmt->execute();
                $notdone = false;
            } catch(\Exception $e ) {
                // here we could differentiate basic SQL errors and deadlock/serializable errors
                $retry++;
                sleep(5);
                if (3 == $retry) {
                    echo 'Error occurred during subtask: '.$e->getMessage();
                }
            }
        }

        // handle deadlock for 3 times
        $retry = 0;
        $notdone = true;
        while ($notdone && $retry < 3) {
            try {
                $query = 'UPDATE ad_feed SET is_updated = 1 WHERE ref_site_id = "'.$ad_feed_site->getId().'" AND status = "R"';
                $stmt = $this->em->getConnection()->prepare($query);
                $stmt->execute();
                $notdone = false;
            } catch(\Exception $e ) {
                // here we could differentiate basic SQL errors and deadlock/serializable errors
                $retry++;
                sleep(5);
                if (3 == $retry) {
                    echo 'Error occurred during subtask: '.$e->getMessage();
                }
            }
        }

        // handle deadlock for 3 times
        $retry = 0;
        $notdone = true;
        while ($notdone && $retry < 3) {
            try {
                $query = 'UPDATE ad_feed SET is_updated = 1 WHERE ref_site_id = "'.$ad_feed_site->getId().'" AND status = "A" AND ad_id IS NULL';
                $stmt = $this->em->getConnection()->prepare($query);
                $stmt->execute();
                $notdone = false;
            } catch(\Exception $e ) {
                // here we could differentiate basic SQL errors and deadlock/serializable errors
                $retry++;
                sleep(5);
                if (3 == $retry) {
                    echo 'Error occurred during subtask: '.$e->getMessage();
                }
            }
        }
    }

    protected function remapAll($ad_feed_site)
    {
        // handle deadlock for 3 times
        $retry = 0;
        $notdone = true;
        while ($notdone && $retry < 3) {
            try {
                $query = 'UPDATE ad_feed SET is_updated = 1 WHERE ref_site_id = "'.$ad_feed_site->getId().'"';
                $stmt = $this->em->getConnection()->prepare($query);
                $stmt->execute();
                $notdone = false;
            } catch(\Exception $e ) {
                // here we could differentiate basic SQL errors and deadlock/serializable errors
                $retry++;
                sleep(5);
                if (3 == $retry) {
                    echo 'Error occurred during subtask: '.$e->getMessage();
                }
            }
        }
    }
}
