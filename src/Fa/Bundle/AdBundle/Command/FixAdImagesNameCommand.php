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
use Gedmo\Sluggable\Util\Urlizer;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FixAdImagesNameCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:fix:ad-image-name')
        ->setDescription('Convert images from old site')
        ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'update all images', null)
        ->addOption('aws', null, InputOption::VALUE_OPTIONAL, 'update aws', null)
        ->addOption('update_type', null, InputOption::VALUE_OPTIONAL, 'Update type from migration', null);
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

        $QUERY_BATCH_SIZE = 1000;
        $done             = false;
        $last_id          = 0;
        $update_type   = $input->getOption('update_type');
        $force   = $input->getOption('force');
        $aws     = $input->getOption('aws');

        while (!$done) {
            $images = $this->getImages($last_id, $QUERY_BATCH_SIZE, $update_type);
            if ($images) {
                foreach ($images as $image) {
                    $adObj = $image->getAd();
                    if ($image->getImageName() == '') {
                        $image->setImageName(Urlizer::urlize($adObj->getTitle().'-'.$adObj->getId().'-'.$image->getOrd()));
                        $image->setAws(0);
                        $this->em->persist($image);
                    }
                    echo 'Updated for -> '.$image->getId()."\n";
                    $this->em->flush();
                }
                $last_id = $image->getId();
            } else {
                $done = true;
            }
        }
    }

    /**
     * Get images.
     *
     * @param integer $last_id           Last id.
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getImages($last_id, $QUERY_BATCH_SIZE, $update_type)
    {
        $q = $this->em->getRepository('FaAdBundle:AdImage')->createQueryBuilder(AdImageRepository::ALIAS);
        $q->andWhere(AdImageRepository::ALIAS.'.old_path IS NOT NULL');
        $q->andWhere(AdImageRepository::ALIAS.'.image_name IS NULL OR '.AdImageRepository::ALIAS.'.image_name = :image_name');
        $q->andWhere(AdImageRepository::ALIAS.'.id > :id');
        $q->andWhere(AdImageRepository::ALIAS.'.update_type IS NOT NULL');
        $q->setParameter('image_name', '');
        $q->setParameter('id', $last_id);
        $q->addOrderBy(AdImageRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);
        return $q->getQuery()->getResult();
    }
}
