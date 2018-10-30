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
use Fa\Bundle\UserBundle\Repository\UserSearchAgentRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\UserBundle\Entity\UserSearchAgentEmailAd;
use Fa\Bundle\UserBundle\Repository\UserSearchAgentEmailAdRepository;

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SearchAgentAlertCommand extends ContainerAwareCommand
{
    /**
     * Default entity manager
     *
     * @var object
     */
    private $jobsCatMappingArray;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:search-agent-alert')
        ->setDescription("Send agent alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Id of search agent', null)
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send search agent alert for latest ads

Command:
 - php app/console fa:update:search-agent-alert
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
        $reader = new \EasyCSV\Reader(__DIR__."/../../EntityBundle/Command/job_mapping.csv");
        $reader->setDelimiter(';');
        $this->jobsCatMappingArray = array();
        while ($row = $reader->getRow()) {
            $this->jobsCatMappingArray[$row['old_id']] = $row['new_id'];
        }

        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->searchAgentAlertWithOffset($input, $output);
        } else {
            $this->searchAgentAlert($input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function searchAgentAlertWithOffset($input, $output)
    {
        $id          = $input->getOption('id');
        $qb          = $this->getAgentQueryBuilder($id);
        $step        = 100;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $searchAgents = $qb->getQuery()->getResult();

        foreach ($searchAgents as $searchAgent) {
            $searchParams = unserialize($searchAgent->getCriteria());
            if (isset($searchParams['search']) && isset($searchParams['search']['item__category_id']) && isset($this->jobsCatMappingArray[$searchParams['search']['item__category_id']])) {
                $searchParams['search']['item__category_id'] = $this->jobsCatMappingArray[$searchParams['search']['item__category_id']];
                $searchAgent->setCriteria(serialize($searchParams));
                $this->em->persist($searchAgent);
                $this->em->flush();
            }

            $adIdsToBeIgnored = $this->em->getRepository('FaUserBundle:UserSearchAgentEmailAd')->getRecentlySentUserAdList($searchAgent->getUser()->getId(), '35');
            if ($adIdsToBeIgnored && !empty($adIdsToBeIgnored)) {
                $searchParams['ad_ids_to_be_ignored'] = str_replace(',', ' ', $adIdsToBeIgnored);
            }

            $ads = $this->getAds($searchParams);

            if (count($ads) > 0) {
                $user = ($searchAgent->getUser() ? $searchAgent->getUser() : null);

                //send email only if ad has user and status is active.
                if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                    $userSearchAgentRepository  = $this->em->getRepository('FaUserBundle:UserSearchAgent');
                    $userSearchAgentRepository->sendSearchAgentAlertEmail($searchAgent, $ads, $this->getContainer());

                    $adIds = array();
                    foreach ($ads as $key => $adDetail) {
                        $adIds[] = $adDetail['id'];
                    }

                    $objSearchAgentEmailAd = new UserSearchAgentEmailAd();
                    $objSearchAgentEmailAd->setUser($user);
                    $objSearchAgentEmailAd->setSearchAgent($searchAgent);
                    $objSearchAgentEmailAd->setAdIds(implode(',', $adIds));
                    $this->em->persist($objSearchAgentEmailAd);
                    $this->em->flush();

                    $output->writeln('Email alert send for id: '.$searchAgent->getId(), true);
                }
            }
        }
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }



    protected function getAds($searchParams)
    {
        $solrSearchParameters = array();
        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $solrSearchParameters['keywords'] = null;
        if (isset($searchParams['search'])) {
            $solrSearchParameters['keywords'] = isset($searchParams['search']['keywords']) ? $searchParams['search']['keywords'] : null;
        }

        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');

        $solrSearchParameters['sort_field'] = isset($searchParams['sorter']['sort_field']) && $searchParams['sorter']['sort_field'] != '' ? $searchParams['sorter']['sort_field'] : 'item__published_at';
        $solrSearchParameters['sort_ord'] = isset($searchParams['sorter']['sort_ord']) && $searchParams['sorter']['sort_ord'] != '' ? $searchParams['sorter']['sort_ord'] : 'desc';
        $solrSearchParameters['page']  = 1;

        $parameters = $this->em->getRepository('FaEmailBundle:EmailTemplate')->getSchedualParameterArray('email_alerts', CommonManager::getCurrentCulture($this->getContainer()));

        $last_hours = isset($parameters['new_adverts_in_last_x_hours']) && $parameters['new_adverts_in_last_x_hours'] > 0 ? $parameters['new_adverts_in_last_x_hours'] : 24;

        $solrSearchParameters['search'] = isset($searchParams['search']) ? $searchParams['search'] : array();
        $solrSearchParameters['search']['item__published_at_from'] = strtotime('-'.$last_hours.' hours');

        $this->getContainer()->get('fa.searchfilters.manager')->init($this->em->getRepository('FaAdBundle:Ad'), $this->em->getClassMetadata('FaAdBundle:Ad')->getTableName(), 'search', $solrSearchParameters);
        $data = $this->getContainer()->get('fa.searchfilters.manager')->getFiltersData();
        //FFR-2365
        //$data['static_filters'] = ' AND -'.AdSolrFieldMapping::WEEKLY_REFRESH_COUNT.': [1 TO *] AND -'.AdSolrFieldMapping::RENEWED_AT.': [1 TO *]';
        if (isset($searchParams['ad_ids_to_be_ignored']) && $searchParams['ad_ids_to_be_ignored']) {
            $data['static_filters'] = ' AND -'.AdSolrFieldMapping::ID.': ('.$searchParams['ad_ids_to_be_ignored'].')';
        }
        
        
        //if category not exist then Adult Category will not consider in Search Agent like same as frontend search
        if (isset($searchParams['search']) && !isset($searchParams['search']['item__category_id'])) {
            if (isset($data['static_filters'])) {
                $data['static_filters'] .= ' AND -'.AdSolrFieldMapping::ROOT_CATEGORY_ID.': ('.\Fa\Bundle\EntityBundle\Repository\CategoryRepository::ADULT_ID.')';
            } else {
                $data['static_filters'] = ' AND -'.AdSolrFieldMapping::ROOT_CATEGORY_ID.': ('.\Fa\Bundle\EntityBundle\Repository\CategoryRepository::ADULT_ID.')';
            }
        }
        
        $data['query_filters']['item']['status_id'] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID;

        // ad location filter with distance
        if (isset($data['search']['item__location']) && $data['search']['item__location']) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'. (isset($data['search']['item__distance']) ? $data['search']['item__distance'] : '');
        }

        $total_ads = isset($parameters['max_x_adverts']) && $parameters['max_x_adverts'] > 0 ? $parameters['max_x_adverts'] : 24;

        // perform exact search
        $solrManager->init('ad', $solrSearchParameters['keywords'], $data, 1, $total_ads, 0, true);
        $solrResponse = $solrManager->getSolrResponse();

        // fetch result set from solr
        $result      = $solrManager->getSolrResponseDocs($solrResponse);
        $resultCount = $solrManager->getSolrResponseDocsCount($solrResponse);

        $adArray = array();
        if ($resultCount > 0) {
            foreach ($result as $key => $ad) {
                $adTitle = (property_exists($ad, AdSolrFieldMapping::TITLE) ? $ad->{AdSolrFieldMapping::TITLE} : null);
                if ($adTitle) {
                    $adTitle = CommonManager::hideOrRemovePhoneNumber($adTitle, 'remove');
                    $adTitle = CommonManager::hideOrRemoveEmail($ad->id, $adTitle, 'remove');
                }
                $adDesc = (property_exists($ad, AdSolrFieldMapping::DESCRIPTION) ? CommonManager::hideOrRemovePhoneNumber($ad->{AdSolrFieldMapping::DESCRIPTION}, 'remove') : null);
                if ($adDesc) {
                    $adDesc = CommonManager::hideOrRemoveEmail($ad->id, $adDesc, 'remove');
                }
                $index = $key + 1;
                $adArray[$index]['id']              = $ad->id;
                $adArray[$index]['text_item_title'] = $adTitle;
                $adArray[$index]['text_item_category'] = $entityCache->getEntityNameById('FaEntityBundle:Category', $ad->a_category_id_i);
                $adArray[$index]['text_item_description'] = ($adDesc ? substr($adDesc, 0, 400) : null);
                $adArray[$index]['text_item_price'] =  (property_exists($ad, AdSolrFieldMapping::PRICE) ? CommonManager::formatCurrency($ad->{AdSolrFieldMapping::PRICE}, $this->getContainer()) : null);
                $adArray[$index]['url_item_main_photo'] = $this->getMainImageThumbUrlFromAd($ad, $this->getContainer());
                $adArray[$index]['url_ad_view'] = $this->getContainer()->get('router')->generate('ad_detail_page_by_id', array('id' => $ad->id), true);
                $adArray[$index]['text_posted_time'] = CommonManager::formatDate($ad->a_published_at_i, $this->getContainer(), null, null, 'dd/MM/yyyy hh:mm a');
            }
        }

        return $adArray;
    }

    /**
     * Get main image thumb url from ad.
     *
     * @param object $ad
     * @param object $container
     *
     * @return string
     */
    public function getMainImageThumbUrlFromAd($ad, $container)
    {
        //image url
        $adMainPhoto = null;
        $imageUrl = $this->em->getRepository('FaAdBundle:AdImage')-> getImagePath($container, $ad, '300X225', 1);
        if ($imageUrl) {
            if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                $imageUrl = str_replace('//', 'http://', $imageUrl);
            }
            $adMainPhoto = $imageUrl;
        } else {
            $adMainPhoto = $container->getParameter('fa.url.scheme').":".$container->getParameter('fa.static.url').'/fafrontend/images/no-image-grey.png';
        }
        return $adMainPhoto;
    }

    /**
     * Update refresh date for ad.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function searchAgentAlert($input, $output)
    {
        $id        = $input->getOption('id');
        $count     = $this->getAgentCount($id);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:search-agent-alert '.$commandOptions.' --verbose';

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
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAgentQueryBuilder($id = null)
    {
        $searchAgentRepository  = $this->em->getRepository('FaUserBundle:UserSearchAgent');
        $qb = $searchAgentRepository->createQueryBuilder(UserSearchAgentRepository::ALIAS);
        $qb->andWhere(UserSearchAgentRepository::ALIAS.'.is_email_alerts = :alert_status');
        if ($id) {
            $qb->andWhere(UserSearchAgentRepository::ALIAS.'.id = :id');
            $qb->setParameter('id', $id);
        }
        $qb->setParameter('alert_status', 1);
        return $qb;
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAgentCount($id = null)
    {
        $qb = $this->getAgentQueryBuilder($id);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        return $qb->getQuery()->getSingleScalarResult();
    }
}
