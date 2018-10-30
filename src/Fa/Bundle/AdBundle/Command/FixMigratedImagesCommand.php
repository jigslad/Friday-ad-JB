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
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
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
class FixMigratedImagesCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:fix-migrated-images')
        ->setDescription('Fix migrated images')
        ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'update all images', null)
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
        $done          = false;
        $last_id      = 0;
        $update_type   = $input->getOption('update_type');

        while (!$done) {
            $ads = $this->getAds($last_id, $QUERY_BATCH_SIZE, $update_type);
            if ($ads) {
                foreach ($ads as $ad) {
                    $images =  $this->em->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId());
                    if ($images) {
                        $ord = 1;
                        foreach ($images as $image) {
                            $image->setOrd($ord);
                            $image->setImageName(Urlizer::urlize($ad->getTitle().'-'.$ad->getId().'-'.$ord));
                            $this->em->persist($image);
                            $ord++;
                        }

                        echo 'Updated for -> '.$ad->getId()."\n";
                    }
                }

                $last_id = $ad->getId();
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
     * @param integer $last_id
     * @param integer $QUERY_BATCH_SIZE
     */
    public function getAds($last_id, $QUERY_BATCH_SIZE, $update_type)
    {
        $q = $this->em->getRepository('FaAdBundle:Ad')->createQueryBuilder(AdRepository::ALIAS);
        $q->andWhere(AdRepository::ALIAS.'.id > :id');
        $q->setParameter('id', $last_id);
        $q->addOrderBy(AdRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);

        if ($update_type != '') {
            $q->andWhere(AdImageRepository::ALIAS.'.update_type LIKE :update_type');
            $q->setParameter('update_type', $update_type);
        }

        return $q->getQuery()->getResult();
    }
}
