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
use Symfony\Component\Validator\Constraints\Date;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\AdViewCounter;

/**
 * This command is used for update counter.
 * php app/console fa:update:ad-view-counter
 * php app/console fa:update:ad-view-counter --date=2015-01-01
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdViewCounterCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-view-counter')
        ->setDescription("Update ad view counter from cache to database.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date in YYYY-MM-DD format', '')
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update ad view counter from cache to database.
- Update ad view counter of previous day if date is not passed.

Command:
 - php app/console fa:update:ad-view-counter
 - php app/console fa:update:ad-view-counter --date="YYYY-mm-dd H:0"
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
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateAdViewCounterWithOffset($input, $output);
        } else {
            $this->updateAdViewCounter($input, $output);
        }
    }

    /**
     * Update ad view counter with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdViewCounterWithOffset($input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $step          = 500;
        $offset        = 0;

        $date = $input->getOption('date');

        if ($date) {
            $date = strtotime($date);
        } else {
            // Last previous hour
            $date = strtotime(date('Y-m-d H:0', time())) - 3600;
        }

        $adCounterKeys = CommonManager::getCacheCounterUsingZIncr($this->getContainer(), 'ad_view_counter_'.$date, $offset, $step);

        if (count($adCounterKeys)) {
            foreach ($adCounterKeys as $adId => $adViewCounter) {
                $objAd = $entityManager->getRepository('FaAdBundle:Ad')->find($adId);

                if ($objAd) {
                    $adViewCounterObj = $entityManager->getRepository('FaAdBundle:AdViewCounter')->findOneBy(array('ad' => $adId, 'created_at' => $date));
                    if (!$adViewCounterObj) {
                        $adViewCounterObj = new AdViewCounter();
                        $adViewCounterObj->setAd($entityManager->getReference('FaAdBundle:Ad', $adId));
                    }
                    $oldHits = $adViewCounterObj->getHits();
                    $adViewCounterObj->setHits($oldHits + $adViewCounter);
                    $adViewCounterObj->setCreatedAt($date);
                    $output->writeln('Counter updated for ad id: '.$adId);

                    $entityManager->persist($adViewCounterObj);
                } else {
                    $message  = 'Counter NOT updated for ad id: '.$adId.' due to ad was not found in database!';
                    $output->writeln($message);
                }
            }
            $entityManager->flush();
            // Remove from counter cache for this ad.
            CommonManager::removeCacheUsingZDeleteRangeByRank($this->getContainer(), 'ad_view_counter_'.$date, $offset, $step);
        } else {
            $output->writeln('No ad counter found to update.');
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update ad view counter.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdViewCounter($input, $output)
    {
        $date = $input->getOption('date');

        if ($date) {
            $date = strtotime($date);
        } else {
            // Last previous hour
            $date = strtotime(date('Y-m-d H:0', time())) - 3600;
        }

        $count     = $this->getAdCount($date);
        $step      = 500;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:ad-view-counter '.$commandOptions;
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
     * @param integer $date
     *
     * @return integer
     */
    protected function getAdCount($date)
    {
        $adCounter = CommonManager::getCacheZSize($this->getContainer(), 'ad_view_counter_'.$date);

        return $adCounter;
    }
}
