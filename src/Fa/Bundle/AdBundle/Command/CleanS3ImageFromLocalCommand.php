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
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Doctrine\DBAL\Logging\EchoSQLLogger;

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CleanS3ImageFromLocalCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:clean-s3-from-local-image')
        ->setDescription('Upload images to s3')
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad ID', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null);
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $managerRegistry = $this->getContainer()->get('doctrine');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new EchoSQLLogger());
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        //$managerRegistry->resetEntityManager();

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $this->adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';

        $QUERY_BATCH_SIZE = 500;
        $done             = false;
        $last_id          = 0;
        //$last_updated     = strtotime('-3 day');
        $last_updated     = '';

        $ids = $input->getOption('ad_id');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        $offset     = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateAdExpirationWithOffset($last_updated, $ids, $input, $output);
        } else {
            $this->updateAdExpiration($last_updated, $ids, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdExpirationWithOffset($last_updated, $ids, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($last_updated, $ids);
        $step        = 500;
        $offset      = 0;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $images = $qb->getQuery()->getResult();

        if ($images) {
            foreach ($images as $image) {
                if ($image->getAd()) {
                    $imagePath  = $this->adImageDir.CommonManager::getGroupDirNameById($image->getAd()->getId());
                    $adImageManager = new AdImageManager($this->getContainer(), $image->getAd()->getId(), null, $imagePath);
                    $adImageManager->removS3ImagesFromLocal($image);
                }
            }
            $last_id = $image->getId();
            $this->em->flush();
            $this->em->clear();
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
    protected function updateAdExpiration($last_updated, $ids, $input, $output)
    {
        $count     = $this->getAdCount($last_updated, $ids);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:clean-s3-from-local-image '.$commandOptions;
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
     * Get images.
     *
     * @param integer $last_id
     */
    public function getAdQueryBuilder($last_updated, $ids)
    {
        $q = $this->em->getRepository('FaAdBundle:AdImage')->createQueryBuilder(AdImageRepository::ALIAS);

        if ($ids) {
            $q->andWhere(AdImageRepository::ALIAS.'.ad IN (:ids)');
            $q->setParameter('ids', $ids);
        }

        $q->andWhere(AdImageRepository::ALIAS.'.aws = :p');
        $q->andWhere(AdImageRepository::ALIAS.'.local = :local');
        //$q->andWhere(AdImageRepository::ALIAS.'.created_at < :created_at');

        $q->setParameter('p', 1);
        $q->setParameter('local', 1);
        //$q->setParameter('created_at', $last_updated);

        return $q;
    }


    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount($last_updated, $ids)
    {
        $qb = $this->getAdQueryBuilder($last_updated, $ids);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        return $qb->getQuery()->getSingleScalarResult();
    }
}
