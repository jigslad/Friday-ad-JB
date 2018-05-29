<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

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

/**
 * This command is used to update user ad shop detail.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserAdShopDetailCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 200;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:user-ad-shop-detail')
        ->setDescription("Update user ad shop detail.")
        ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'User id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update user's ad shop detail to solr.

Command:
 - php app/console fa:update:user-ad-shop-detail --user_id=1
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
        //get arguments passed in command
        $offset         = $input->getOption('offset');
        $userId         = $input->getOption('user_id');
        $this->em       = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $this->em->getRepository('FaUserBundle:User');
        $userObj  = $userRepository->find($userId);


        if (!$userObj) {
            $output->writeln('No user found.', true);
        } else {
            if (isset($offset)) {
                $this->updateUserAdShopDetailWithOffset($input, $output);
            } else {
                $this->updateUserAdShopDetail($input, $output);
            }
        }
    }

    /**
     * Update user ad yac number.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserAdShopDetail($input, $output)
    {
        $userId    = $input->getOption('user_id');
        $count     = $this->getUserActiveAdCount($userId);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:user-ad-shop-detail '.$commandOptions;
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
     * Update user ad shop detail with offset.
     *
     * @param object $input  Input object
     * @param object $output Output object
     */
    protected function updateUserAdShopDetailWithOffset($input, $output)
    {
        $offset  = $input->getOption('offset');
        $userId  = $input->getOption('user_id');
        $userAds = $this->getUserActiveAdResult($userId, $offset, $this->limit);

        foreach ($userAds as $userAd) {
            $solrClient = $this->getContainer()->get('fa.solr.client.ad');
            if (!$solrClient->ping()) {
                $output->writeln('Solr is not started.', true);
            }

            $adSolrIndex = $this->getContainer()->get('fa.ad.solrindex');
            $solrIndexed = $adSolrIndex->update($solrClient, $userAd, $this->getContainer(), false);
            if ($solrIndexed) {
                $output->writeln('Solr index updated for ad id: '.$userAd->getId(), true);
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for active ads.
     *
     * @param integer $userId User id.
     *
     * @return integer
     */
    protected function getUserActiveAdCount($userId)
    {
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $query = $adRepository->getBaseQueryBuilder()
            ->select('COUNT('.AdRepository::ALIAS.'.id) as ad_count')
            ->andWhere(AdRepository::ALIAS.'.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere(AdRepository::ALIAS.'.status IN (:statusIds)')
            ->setParameter('statusIds', array(EntityRepository::AD_STATUS_LIVE_ID));

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get user active ad results.
     *
     * @param integer $userId User id.
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserActiveAdResult($userId, $offset, $limit)
    {
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $query = $adRepository->getBaseQueryBuilder()
            ->andWhere(AdRepository::ALIAS.'.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere(AdRepository::ALIAS.'.status IN (:statusIds)')
            ->setParameter('statusIds', array(EntityRepository::AD_STATUS_LIVE_ID))
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getResult();
    }
}
