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

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CleanS3LocalImagesCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:clean-s3-local-image')
        ->setDescription('Upload images to s3')
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad ID', null);
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';

        $QUERY_BATCH_SIZE = 100;
        $done             = false;
        $last_id          = 0;
        $last_updated     = strtotime('-2 hour');

        $ids = $input->getOption('ad_id');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        while (!$done) {
            $images = $this->getImages($last_id, $ids, $QUERY_BATCH_SIZE, $last_updated);
            if ($images) {
                foreach ($images as $image) {
                    if ($image->getAd()) {
                        $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($image->getAd()->getId());
                        $adImageManager = new AdImageManager($this->getContainer(), $image->getAd()->getId(), null, $imagePath);
                        $adImageManager->removS3ImagesFromLocal($image);
                        echo "Image removed from local: ".$image->getId()."\n";
                    }
                }
                $last_id = $image->getId();
            } else {
                $done = true;
            }

            $this->em->flush();
            $this->em->clear();
        }
    }

    /**
     * Get images.
     *
     * @param array $ids
     * @param integer $last_id
     * @param integer $QUERY_BATCH_SIZE
     */
    public function getImages($last_id, $ids, $QUERY_BATCH_SIZE, $last_updated)
    {
        $q = $this->em->getRepository('FaAdBundle:AdImage')->createQueryBuilder(AdImageRepository::ALIAS);

        if ($ids) {
            $q->andWhere(AdImageRepository::ALIAS.'.ad IN (:ids)');
            $q->setParameter('ids', $ids);
        }

        $q->andWhere(AdImageRepository::ALIAS.'.aws = :p');
        $q->andWhere(AdImageRepository::ALIAS.'.updated_at < :updated_at');
        $q->andWhere(AdImageRepository::ALIAS.'.id > :id');

        $q->setParameter('id', $last_id);
        $q->setParameter('p', 1);
        $q->setParameter('updated_at', $last_updated);
        $q->addOrderBy(AdImageRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);
        return $q->getQuery()->getResult();
    }
}
