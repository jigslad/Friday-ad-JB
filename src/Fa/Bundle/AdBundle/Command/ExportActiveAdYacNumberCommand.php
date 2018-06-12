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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Entity\Ad;

/**
 * This command is used to update user statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ExportActiveAdYacNumberCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 500;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

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
        ->setName('fa:export:active-ad-yac-number')
        ->setDescription("Update user ad statistics.")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update active ad yac number.

Command:
 - php app/console fa:export:active-ad-yac-number
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
        $this->mainDbName = $this->getContainer()->getParameter('database_name');
        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->exportActiveAdYacNumberWithOffset($input, $output);
        } else {
            $output->writeln('Total ads to process: '.$this->getActiveTotalAdCount(), true);
            $file  = fopen($this->getContainer()->get('kernel')->getRootDir()."/../activeAdYacNumber.csv", "a+");
            fputcsv($file, array('YAC Number', 'Original Number',''));
            fclose($file);
            $this->exportActiveAdYacNumber($input, $output);
        }
    }

    /**
     * Update user ad yac number.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function exportActiveAdYacNumber($input, $output)
    {
        $count     = $this->getActiveTotalAdCount();
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export:active-ad-yac-number '.$commandOptions.' --verbose';
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
     * Update user total ad count with offset.
     *
     * @param object $input  Input object
     * @param object $output Output object
     */
    protected function exportActiveAdYacNumberWithOffset($input, $output)
    {
        $offset             = $input->getOption('offset');
        $adRepository       = $this->em->getRepository('FaAdBundle:Ad');

        $activeAds = $this->getActiveAdCountResult($offset, $this->limit);
        $cntr = 1;
        $file  = fopen($this->getContainer()->get('kernel')->getRootDir()."/../activeAdYacNumber.csv", "a+");

        foreach ($activeAds as $activeAd) {
            if ($activeAd['privacy_number']) {
                $adObj = $adRepository->find($activeAd['ad_id']);
                $adUser = ($adObj->getUser() ? $adObj->getUser() : null);

                if ($adUser && $adUser->getPhone() && $adUser->getIsPrivatePhoneNumber()) {
                    fputcsv($file, array($activeAd['privacy_number'], $adUser->getPhone(), ''));
                }
            }
            $cntr++;
        }
        fclose($file);

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for ads.
     *
     * @return integer
     */
    protected function getActiveAdQueryBuilder()
    {
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $query = $adRepository->getBaseQueryBuilder()
        ->andWhere(AdRepository::ALIAS.'.privacy_number IS NOT NULL')
        ->andWhere(AdRepository::ALIAS.'.status IN (:statusIds)')
        ->setParameter('statusIds', array(EntityRepository::AD_STATUS_LIVE_ID))
        ->andWhere(AdRepository::ALIAS.'.is_blocked_ad = 0')
        ->orderBy(AdRepository::ALIAS.'.id');

        return $query;
    }

    /**
     * Get count for active ads.
     *
     * @return integer
     */
    protected function getActiveTotalAdCount()
    {
        $query = $this->getActiveAdQueryBuilder();

        $query->select('COUNT('.AdRepository::ALIAS.'.id) as ad_count');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get active ad count results.
     *
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getActiveAdCountResult($offset, $limit)
    {
        $query = $this->getActiveAdQueryBuilder();
        $query->select(AdRepository::ALIAS.'.id as ad_id', AdRepository::ALIAS.'.privacy_number', AdRepository::ALIAS.'.expires_at', AdRepository::ALIAS.'.future_publish_at', AdRepository::ALIAS.'.use_privacy_number', AdRepository::ALIAS.'.phone')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getArrayResult();
    }
}
