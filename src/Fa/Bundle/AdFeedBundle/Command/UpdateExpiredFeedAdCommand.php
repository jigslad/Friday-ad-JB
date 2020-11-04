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

/**
 * This command is used to remove active ad but ecpired in feed.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateExpiredFeedAdCommand extends ContainerAwareCommand
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
     * Configure.
     */
    protected function configure()
    {
        $this->setName('fa:update:expired-feed-ad')
            ->setDescription("Update expired feed ads.")
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            ->setHelp(<<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to Update expired feed ads..

Command:
 - php app/console fa:update:expired-feed-ad
EOF
            );
    }

    /**
     * Execute.
     *
     * @param InputInterface $input
     *            InputInterface object.
     * @param OutputInterface $output
     *            OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set entity manager.
        $this->entityManager = $this->getContainer()
            ->get('doctrine')
            ->getManager();

        // get arguments passed in command
        $offset = $input->getOption('offset');

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME ' . date('d-m-Y H:i:s', $start_time), true);
        }

        if (isset($offset)) {
            $this->updateExpiredFeedAdWithOffset($input, $output);
        } else {
            $output->writeln('Total entries to process: ' . $this->getAdCount(), true);
            $this->updateExpiredFeedAd($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME ' . date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT ' . ((time() - $start_time) / 60), true);
        }
    }

    /**
     * Execute raw query.
     *
     * @param string $sql
     *            Sql query to run.
     * @param object $entityManager
     *            Entity manager.
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
     * @param object $input
     *            Input object.
     * @param object $output
     *            Output object.
     */
    protected function updateExpiredFeedAd($input, $output)
    {
        $count = $this->getAdCount();

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --' . $option . '=' . $value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset=' . $low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit=' . $input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path') . $memoryLimit . ' ' . $this->getContainer()
                    ->get('kernel')
                    ->getRootDir() . '/console fa:update:expired-feed-ad ' . $commandOptions . ' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update expired feed ads with offset.
     *
     * @param object $input
     *            Input object.
     * @param object $output
     *            Output object.
     */
    protected function updateExpiredFeedAdWithOffset($input, $output)
    {
        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');
        $offset = 0; // $input->getOption('offset')
        $ads = $this->getAdResult($offset, $this->limit);
        foreach ($ads as $ad) {
            try {
                $adObj = $adRepository->findOneBy(array(
                    'id' => $ad['ad_id']
                ));
                if ($adObj) {
                    $adObj->setStatus($this->entityManager->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_EXPIRED_ID));
                    $this->entityManager->persist($adObj);

                    $phpPath = $this->getContainer()->getParameter('fa.php.path');
                    $appRootDirectory = rtrim($this->getContainer()->get('kernel')->getRootDir(), '/');
                    $website = $this->getContainer()->getParameter('nmp.site.name');
                    $command = "{$phpPath} {$appRootDirectory}/console fa:update:ad-solr-index-new --id='{$ad['ad_id']}' --status='A,E' update --website='{$website}' &> /dev/null";
                    passthru($command, $returnVar);

                    $output->writeln('Ad feed status updated to expired: ' . $ad['ad_id'], true);
                }
            } catch (\Exception $e) {
                $output->writeln('Error occurred during subtask' . $ad['ad_id'], true);
                $output->writeln($e->getMessage(), true);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Get query builder for ads.
     *
     * @return count
     */
    protected function getAdCount()
    {
        $sql = 'SELECT COUNT(af.id) as total FROM  `ad_feed` af INNER JOIN ad a ON af.ad_id = a.id WHERE af.status = "E" AND a.status_id ="live"';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $countRes = $stmt->fetch();

        return $countRes['total'];
    }

    /**
     * Get ad count results.
     *
     * @param integer $offset
     *            Offset.
     * @param integer $limit
     *            Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdResult($offset, $limit)
    {
        $sql = 'SELECT a.id as ad_id FROM  `ad_feed` af INNER JOIN ad a ON af.ad_id = a.id WHERE af.status = "E" AND a.status_id ="live" LIMIT ' . $limit . ' OFFSET ' . $offset . ';';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
