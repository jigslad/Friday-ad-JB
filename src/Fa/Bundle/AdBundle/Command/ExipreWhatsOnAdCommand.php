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
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to expire what's on community ad if end date passed or no ad with no end date and start date passed.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExipreWhatsOnAdCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:expire-whatson-ad')
        ->setDescription("Expire what's on ad")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Expire what's on community ad if end date passed or no ad with no end date and start date passed.

Command:
 - php app/console fa:update:expire-whatson-ad
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
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->expireWhatsOnAd($input, $output);
    }

    /**
     * Expire what's on ads.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function expireWhatsOnAd($input, $output)
    {
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        $ads = $this->getWhatsOnAds();
        if (count($ads)) {
            foreach ($ads as $ad) {
                $expiredAt  = time();
                $user       = ($ad->getUser() ? $ad->getUser() : null);

                //send email only if ad has user and status is active.
                if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                    //$this->em->getRepository('FaAdBundle:Ad')->sendExpirationEmail($ad, $this->getContainer());
                    $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('ad_is_expired', $user, $ad, $this->getContainer());
                }
                $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID));
                $ad->setExpiresAt($expiredAt);
                $this->em->persist($ad);

                // insert expire stat
                $this->em->getRepository('FaAdBundle:AdStatistics')->insertExpiredStat($ad, $expiredAt);

                // inactivate the package
                $this->em->getRepository('FaAdBundle:Ad')->doAfterAdCloseProcess($ad->getId(), $this->getContainer());
                $this->em->flush();

                $user_id = $ad->getUser() ? $ad->getUser()->getId() : null;
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByOnlyAdId($ad->getId());
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_expired', $ad->getId(), $user_id);
                $output->writeln('Ad has been expired with AD ID: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
            }
            $this->em->clear();

            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:process-email-queue --email_identifier="ad_is_expired"';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get what's on ads which needs to expire.
     *
     * @return array.
     */
    protected function getWhatsOnAds()
    {
        $data                                                         = array();
        $data['query_filters']['item']['status_id']                   = \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['category_id']                 = \Fa\Bundle\EntityBundle\Repository\CategoryRepository::WHATS_ON_ID;
        $data['query_filters']['item_community']['expire_event_date'] = CommonManager::getTimeStampFromEndDate(date('d/m/Y', strtotime('-1 day')));

        $data['select_fields']['item']['status_id'] = 'id';

        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');
        $solrManager->init('ad', null, $data, 1, 1000);
        $solrAds = $solrManager->getSolrResponseDocs();

        if (count($solrAds)) {
            $ids = array();
            foreach ($solrAds as $ad) {
                $ids[] = $ad['id'];
            }

            return $this->em->getRepository('FaAdBundle:Ad')->findBy(array('id' => $ids));
        }

        return array();
    }
}
