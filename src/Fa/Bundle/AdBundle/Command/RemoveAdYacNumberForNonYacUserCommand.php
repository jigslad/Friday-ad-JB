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
use Fa\Bundle\AdBundle\Entity\Ad;

/**
 * This command is used to remove non yac users ad yac number.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveAdYacNumberForNonYacUserCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

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
        ->setName('fa:remove:ad-yac-number-for-non-yac-user')
        ->setDescription("Remove non yac users ad yac number.")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to remove non yac users ad yac number.

Command:
 - php app/console fa:remove:ad-yac-number-for-non-yac-user
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
            $this->updateNonYacUserAdYacNumberWithOffset($input, $output);
        } else {
            $this->updateNonYacUserAdYacNumber($input, $output);
        }
    }

    /**
     * Update user ad yac number.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateNonYacUserAdYacNumber($input, $output)
    {
        $count     = $this->getNonYacUserAdYacNumberAdCount();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:remove:ad-yac-number-for-non-yac-user '.$commandOptions.' --verbose';
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
    protected function updateNonYacUserAdYacNumberWithOffset($input, $output)
    {
        $offset             = 0;
        $adRepository       = $this->em->getRepository('FaAdBundle:Ad');
        $yacManager         = $this->getContainer()->get('fa.yac.manager');
        $yacManager->init();

        $nonYacUserAds = $this->getNonYacUserAdYacNumberResult($offset, $this->limit);
        foreach ($nonYacUserAds as $nonYacUserAd) {
            if ($nonYacUserAd['privacy_number']) {
                $adObj = $adRepository->find($nonYacUserAd['ad_id']);
                $sql = 'UPDATE '.$this->mainDbName.'.ad SET privacy_number = null WHERE id = '.$nonYacUserAd['ad_id'].';';
                $this->executeRawQuery($sql, $this->em);
                $this->updateAdToSolr($adObj);
                $output->writeln('Yac no removed for ad id:'.$nonYacUserAd['ad_id'], true);
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for ads.
     *
     * @return integer
     */
    protected function getNonYacUserAdYacNumberQueryBuilder()
    {
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $query = $adRepository->getBaseQueryBuilder()
        ->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS)
        ->andWhere(AdRepository::ALIAS.'.privacy_number IS NOT NULL AND '.AdRepository::ALIAS.'.privacy_number <> \'\'')
        ->andWhere(AdRepository::ALIAS.'.is_blocked_ad = 0')
        ->andWhere(UserRepository::ALIAS.'.is_private_phone_number = 0')
        ->orderBy(AdRepository::ALIAS.'.id');

        return $query;
    }

    /**
     * Get count for active ads.
     *
     * @param integer $adId Ad id.
     *
     * @return integer
     */
    protected function getNonYacUserAdYacNumberAdCount()
    {
        $query = $this->getNonYacUserAdYacNumberQueryBuilder();

        $query->select('COUNT('.AdRepository::ALIAS.'.id) as ad_count');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get active ad count results.
     *
     * @param integer $adId   Ad id.
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getNonYacUserAdYacNumberResult($offset, $limit)
    {
        $query = $this->getNonYacUserAdYacNumberQueryBuilder();
        $query->select(AdRepository::ALIAS.'.id as ad_id', AdRepository::ALIAS.'.privacy_number')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getArrayResult();
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
