<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2019, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;
use App\Parser\AdFeed\AdParser;

/**
 * This command is used to Update feed status.
 * While updating feed advert status if there is any error or change in format of feed advert, at this time we are not updating advert status
 * and we are not updateing expire date of feed advert because of this issue expire adverts are still in solr.
 *
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2020 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateFeedAdIdCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
            ->setName('fa:feed:update-feed-ad-id')
            ->setDescription("Update feed ads id and add to solr")
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            ->addOption('trans_id', null, InputOption::VALUE_OPTIONAL, 'feed advert unique Id', null)
            ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'user Id', null)
            ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'startDate in unix timestamp', 0)
            ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'endDate in unix timestamp', 0)
            ->setHelp(
                <<<EOF
Actions:
Command:
   php bin/console fa:feed:update-feed-ad-id
   php bin/console fa:feed:update-feed-ad-id --trans_id=12345
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateAdIdWithOffset($input, $output);
        } else {
            $this->updateAdId($input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdIdWithOffset($input, $output)
    {
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $feedAds = $qb->getQuery()->getArrayResult();

        $feedReader  = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');

        //$adparserClass = 'Fa\Bundle\AdFeedBundle\Parser\AdParser';
        //$parser = new $adparserClass($this->container);

        if (!empty($feedAds)) {
            foreach ($feedAds as $feedAd) {
                $this->advert   = array();
                $this->advert  = unserialize($feedAd->getAdText());

                $uniqueId = $feedAd['unique_id'];
                $origFeed = $this->feedAdvertRequest($uniqueId);
                $parser = $feedReader->createParser($origFeed['AdvertType']);

                if (isset($this->advert['full_data'])) {
                    $originalJson  = unserialize($this->advert['full_data']);
                    $parser->mapAdData($originalJson, $this->advert['ref_site_id']);

                    if (isset($this->advert['status']) && $this->advert['status'] == 'R') {
                        $feedAd->setStatus('R');
                        if (implode(',', $this->advert['rejected_reason']) != '') {
                            $feedAd->setRemark(implode(',', $this->advert['rejected_reason']));
                        }
                    } elseif (isset($this->advert['status']) && $this->advert['status'] == 'E') {
                        $feedAd->setStatus('E');
                    } else {
                        $feedAd->setStatus('A');
                        $feedAd->setRemark(null);
                    }

                    $feedAd->setAdText(serialize($this->advert));
                    $this->em->persist($feedAd);
                    $this->em->flush();
                }

                $ad_feed_site   = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $this->advert['ref_site_id']));

                if ($this->advert['set_user'] === true) {
                    $user = $parser->getUser($this->advert['user']['email']);
                } else {
                    $user = null;
                }

                $ad = $parser->getAdByRef($this->advert['unique_id']);

                if ($ad || $feedAd->getStatus() != 'R') {
                    $adMain         = $parser->getAdMainByRef($this->advert['unique_id']);

                    if (isset($this->advert['set_user']) && $this->advert['set_user'] === true) {
                        if (!$user && $this->advert['user']['email'] != '') {
                            $user = $parser->setUser($user);
                        }
                    }

                    $parser->setAdFeedSiteUser($ad_feed_site, $user);

                    $adMain = $parser->setAdMain($adMain);

                    if (!$ad) {
                        $ad = $parser->setAd($user, $adMain, $ad);
                        echo "{ new }";
                    } else {
                        echo "{ updated }";
                        $ad = $parser->setAd($user, $adMain, $ad);
                    }

                    $this->advert['image_hash'] = isset($this->advert['image_hash']) ? $this->advert['image_hash'] : null;

                    $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('id' => $feedAd->getRefSiteId()));
                    $target_dir = $ad_feed_site->getType().'_'.$ad_feed_site->getRefSiteId();
                    $parser->parseAdForImage($originalJson, $target_dir);
                    $parser->updateImages($ad);


                    $parser->assignOrRemoveAdPackage($ad, $user);
                    $parser->setAdLocation($ad);
                    $parser->addChildData($ad);

                    $feedAd->setAd($ad);
                    $feedAd->setUser($user);
                    $feedAd->setImageHash($this->advert['image_hash']);
                    if ($this->advert['set_user'] === true) {
                        $feedAd->setUserHash($this->advert['user_hash']);
                    }
                    $feedAd->setHash(md5(serialize($this->advert)));
                    //$run_time = gmdate('Y-m-d\TH:i:s\Z',$this->advert['last_modified']);
                    $lastRunTime = new \DateTime($this->advert['last_modified']);
                    $feedAd->setLastModified($lastRunTime);
                    $this->em->persist($feedAd);
                    $this->em->flush();

                    $parser->sendFeedCallback($feedAd, $this->advert['last_modified']);
                    return $ad;
                } else {
                    echo "X".$feedAd->getTransId()."\n";
                    if (!$force) {
                        $this->sendFeedCallback($feedAd, $this->advert['last_modified']);
                    }
                }
            }

        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    public function getDataByCat($parser, $origFeed, $refSiteId) {
        switch ($origFeed) {
            case ($origFeed['AdvertType'] == 'BoatAdvert') :
                $this->mapBoatAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'BusinessAdvert') :
                $this->mapBusinessAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'Wightbay') :
                $this->mapWightbayData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'CaravanAdvert') :
                $this->mapCaravanAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'GeneralAdvert') :
                $this->mapGeneralAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'HorseAdvert') :
                $this->mapHorseAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'JobAdvert') :
                $this->mapJobAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'LivestockAdvert') :
                $this->mapLivestockAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'MerchandiseAdvert') :
                $this->mapMerchandiseAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'MotorhomeAdvert') :
                $this->mapMotorhomeAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'PetAdvert') :
                $this->mapPetAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'PropertyAdvert') :
                $this->mapPropertyAdvertData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'TradeIt') :
                $this->mapTradeItData($parser, $origFeed, $refSiteId);
                break;
            case ($origFeed['AdvertType'] == 'VehicleAdvert') :
                $this->mapVehicleAdvertData($parser, $origFeed, $refSiteId);
                break;
        }
        return $origFeed;
    }

    public function mapBoatAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['BoatType']);

        $description = array();

        foreach ($origFeed['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

        if (isset($origFeed['Price']) && $origFeed['Price'] != '') {
            $this->advert['property']['rent_per_id'] = 2560;
        }

        if (isset($origFeed['Manufacturer']) && $origFeed['Manufacturer'] != '') {
            $this->advert['motors']['manufacturer_id'] = $this->getEntityId($origFeed['Manufacturer'], 43);
            if (!$this->advert['motors']['manufacturer_id']) {
                $this->advert['motors']['meta_data']['manufacturer'] = $origFeed['Manufacturer'];
            }
        }

        if (isset($origFeed['Model']) && $origFeed['Model'] != '') {
            $this->advert['motors']['meta_data']['model'] = $origFeed['Model'];
        }

        if (isset($origFeed['Details']['EngineSpecs']) && $origFeed['Details']['EngineSpecs'] != '') {
            $specifications = $origFeed['Details']['EngineSpecs'];
            foreach ($specifications as $specification) {
                if (isset($specification['Equipment']) && $specification['Equipment'] == 'fuel') {
                    $this->advert['motors']['fuel_type_id'] = $this->getEntityId($specification['Value'], 44);
                } elseif (isset($specification['Equipment']) && $specification['Equipment'] == 'build year') {
                    $this->advert['motors']['meta_data']['year_built'] = $this->getEntityId($specification['Value']);
                }
            }
        }
        return $this->advert;
    }

    public function mapBusinessAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['BoatType'], $refSiteId);
        $description = array();

        foreach ($origFeed['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

        if (isset($origFeed['Details']['Tenure']) && $origFeed['Details']['Tenure'] != '') {
            $this->advert['for_sale']['business_type_id'] = $this->getEntityId($adArray['Details']['Tenure'], 15);
        }

        // for turn over
        if (isset($origFeed['Details']['MinTurnover']) && $origFeed['Details']['MinTurnover'] && isset($origFeed['Details']['MaxTurnover']) && $origFeed['Details']['MaxTurnover']) {
            if ($origFeed['Details']['MinTurnover'] == $origFeed['Details']['MaxTurnover']) {
                $this->advert['for_sale']['meta_data']['turnover_min'] = $origFeed['Details']['MinTurnover'];
            } else {
                $this->advert['for_sale']['meta_data']['turnover_min'] = $origFeed['Details']['MinTurnover'];
                $this->advert['for_sale']['meta_data']['turnover_max'] = $origFeed['Details']['MaxTurnover'];
            }
        }

        // for net profit
        if (isset($origFeed['Details']['MinProfit']) && $origFeed['Details']['MinProfit'] && isset($origFeed['Details']['MaxProfit']) && $origFeed['Details']['MaxProfit']) {
            if ($origFeed['Details']['MinProfit'] == $origFeed['Details']['MaxProfit']) {
                $this->advert['for_sale']['meta_data']['net_profit_min'] = $origFeed['Details']['MinProfit'];
            } else {
                $this->advert['for_sale']['meta_data']['net_profit_min'] = $origFeed['Details']['MinProfit'];
                $this->advert['for_sale']['meta_data']['net_profit_max'] = $origFeed['Details']['MaxProfit'];
            }
        }

        // for price
        if (isset($origFeed['Details']['MinPrice']) && $origFeed['Details']['MinPrice'] && isset($origFeed['Details']['MaxPrice']) && $origFeed['Details']['MaxPrice']) {
            $this->advert['price'] = $origFeed['Details']['MinPrice'];
        } else {
            $this->setRejectAd();
            $this->setRejectedReason('price is not specified');
        }
        return $this->advert;
    }

    public function mapWightbayData($parser, $origFeed, $refSiteId) {
        $category    = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($origFeed['category_slug']);
        $this->advert['category_id'] = $category['id'];
        $this->advert['parent_category'] = $origFeed['parent_category'];

        return $this->advert;
    }

    public function mapCaravanAdvertData($parser, $origFeed, $refSiteId) {
        if (!$origFeed['Details']['IsStaticCaravan'] && strtolower($origFeed['Details']['SaleOrHire']) != 'hire') {
            $this->advert['category_id'] = 477;
        } elseif ($origFeed['Details']['IsStaticCaravan'] && strtolower($origFeed['Details']['SaleOrHire']) != 'hire' && strtolower($origFeed['Details']['Type']) != 'lodge') {
            $this->advert['category_id'] = 478;
        } elseif ($origFeed['Details']['IsStaticCaravan'] && strtolower($origFeed['Details']['SaleOrHire']) != 'hire' && strtolower($origFeed['Details']['Type']) == 'lodge') {
            $this->advert['category_id'] = 702;
        } elseif (!$origFeed['Details']['IsStaticCaravan'] && strtolower($origFeed['Details']['SaleOrHire']) == 'hire') {
            $this->advert['category_id'] = 702;
        } elseif ($origFeed['Details']['IsStaticCaravan'] && strtolower($origFeed['Details']['SaleOrHire']) == 'hire') {
            $this->advert['category_id'] = 702;
        }

        if (isset($origFeed['category_id']) && $origFeed['category_id'] == 702) {
            $this->advert['ad_type_id']  = 2520;
        }
        return $this->advert;
    }

    public function mapGeneralAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['Category']);
        return $this->advert;
    }

    public function mapHorseAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId();
        $this->advert['ad_type_id']  = 2763;
        return $this->advert;
    }

    public function mapJobAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['JobType']);
        $this->advert['ad_type_id']  = 2763;
        return $this->advert;
    }

    public function mapLivestockAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['AnimalType']);
        $this->advert['ad_type_id']  = 2891;
        return $this->advert;
    }

    public function mapMerchandiseAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['ClassificationCategory']);
        $this->advert['ad_type_id']  = 2763;
        return $this->advert;
    }

    public function mapMotorhomeAdvertData($parser, $origFeed, $refSiteId) {
        if (isset($origFeed['Details']['VehicleType']) && (strtolower($origFeed['Details']['VehicleType']) == 'motorhomes' || strtolower($origFeed['Details']['VehicleType']) == 'campervan' || strtolower($origFeed['Details']['VehicleType']) == 'campervans' || strtolower($origFeed['Details']['VehicleType']) == 'motorhome')) {
            $this->advert['category_id'] = 475;
        } elseif (isset($origFeed['Details']['VehicleType']) && (strtolower($origFeed['Details']['VehicleType']) == 'touring caravans' || strtolower($origFeed['Details']['VehicleType']) == 'caravan')) {
            $this->advert['category_id'] = 477;
        } elseif (isset($origFeed['Details']['VehicleType']) && (strtolower($origFeed['Details']['VehicleType']) == 'static Caravans' || strtolower($origFeed['Details']['VehicleType']) == 'static')) {
            $this->advert['category_id'] = 478; // static
        }
        $this->advert['ad_type_id']  = 2520;
        return $this->advert;
    }

    public function mapPetAdvertData($parser, $origFeed, $refSiteId) {
        $this->advert['category_id'] = $parser->getCategoryId($origFeed['Details']['AnimalType']);
        $this->advert['ad_type_id']  = 2620;
        return $this->advert;
    }

    public function mapPropertyAdvertData($parser, $origFeed, $refSiteId) {
        $category_text =  str_replace(' ', '_', strtolower(trim($adArray['Details']['PropertyType'].' '.$adArray['Details']['PropertyStatus'])));
        $this->advert['category_id'] = $parser->getCategoryId($category_text);
        $this->advert['ad_type_id']  = 2520;
        return $this->advert;
    }

    public function mapTradeItData($parser, $origFeed, $refSiteId) {
        $category                    = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($adArray['category_slug']);
        $this->advert['category_id'] = $category['id'];
        $this->advert['parent_category'] = $adArray['parent_category'];
        return $this->advert;
    }

    public function mapVehicleAdvertData($parser, $origFeed, $refSiteId) {
        if ($origFeed['Details']['VehicleType'] == 'Motorhome') {
            $this->advert = 'Motorhomes';
        }

        $matchedUrl  = Urlizer::urlize($matchedName);
        $cat= $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($matchedName, 'motors/motorhomes-caravans/', $this->container);
        $this->advert['category_id'] = $cat[0]['id'];
        $this->advert['ad_type_id']  = 2763;
        return $this->advert;
    }

    public function getCategoryParameter($origFeed)
    {
        $cdata = '';
        switch ($origFeed) {
            case ($origFeed['AdvertType'] == 'BoatAdvert') :
                $cdata = $origFeed['Details']['BoatType'];
                break;
            case ($origFeed['AdvertType'] == 'BusinessAdvert') :
                $cdata = $origFeed['Details']['BoatType'];
                break;
        }
        return $cdata;
    }

    public function getCategoryChild($type)
    {
        switch ($type) {
            case ($type == 'BoatAdvert' || $type == 'ClickEditVehicleAdvert' || $type == 'MotorhomeAdvert' || $type == 'CaravanAdvert' || $type == 'VehicleAdvert' || $type == 'Motors') :
                $cname = 'ad_motors';
                break;
            case ($type == 'HorseAdvert' || $type == 'PetAdvert' || $type == 'LivestockAdvert' || $type == 'Animals'):
                $cname = 'ad_animals';
                break;
            case ($type == 'PropertyAdvert' || $type == 'Property') :
                $cname = 'ad_property';
                break;
            case ($type == 'MerchandiseAdvert') :
                $cname = 'merchandise';
                break;
            case ($type == 'JobAdvert' || $type == 'Jobs') :
                $cname = 'ad_jobs';
                break;
            case ($type == 'TradeIt' || $type == 'BusinessAdvert' || $type == 'GeneralAdvert' || $type == 'For Sale') :
                $cname = 'ad_for_sale';
                break;
        }

        return $cname;
    }

    /**
     * Set ad table data.
     *
     * @param object $user
     * @param object $ad
     *
     * @return Ambigous <string, \Fa\Bundle\AdBundle\Entity\Ad>
     */
    protected function setAdMain($origFeed)
    {
        $adMain = new AdMain();

        $adMain->setTransId($origFeed['unique_id']);
        $this->em->persist($adMain);
        $this->em->flush($adMain);
        return $adMain;
    }

    /**
     * Update solr index.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdId($input, $output)
    {
        $qb        = $this->getAdQueryBuilder(true, $input);
        $count     = $qb->getQuery()->getSingleScalarResult();
        $step      = 1000;
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

            $memoryLimit = ' -d memory_limit=100M';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:feed:update-feed-ad-id '.$commandOptions;
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
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        $uniqueId = trim($input->getOption('trans_id'));
        $userId = trim($input->getOption('user_id'));
        $startDate = $input->getOption('start_date').' 00:00:01';
        $endDate = $input->getOption('end_date').' 23:59:59';

        $adFeedRepository  = $this->em->getRepository('FaAdFeedBundle:AdFeed');
        $qb = $adFeedRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdFeedRepository::ALIAS.'.id)');
        } else {
            $qb->select(AdFeedRepository::ALIAS);
        }

        $qb->where(AdFeedRepository::ALIAS.".status = 'A'");
        $qb->andWhere(AdFeedRepository::ALIAS.'.ad is NULL');

        if (!empty($uniqueId)) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.unique_id = :unique_id')->setParameter('unique_id', $uniqueId);
        }
        if($userId) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.user_id = :user_id')->setParameter('user_id', $userId);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.last_modified >= :startDate')
                ->andWhere(AdFeedRepository::ALIAS.'.last_modified <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        if (!$onlyCount) {
            $qb->orderBy(AdFeedRepository::ALIAS.'.id');
        }

        return $qb;
    }

    /**
     * Handle fmgfeedaggregation request.
     *
     * @param string $transId fmgfeedaggregation request.
     *
     * @return string
     */
    protected function feedAdvertRequest($advertId)
    {
        $mode = $this->getContainer()->getParameter('fa.feed.mode');
        $mainUrl = $this->getContainer()->getParameter('fa.feed.'.$mode.'.url');

        $url = $mainUrl.'/Adverts/GetAdvertById?appkey='.$this->getContainer()->getParameter('fa.feed.api.id').'&advertId='.$advertId;

        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = json_decode(curl_exec($ch), true);

        return $response;
    }

    /**
     * Map add data.
     *
     * @param array   $imageArray
     * @param boolean $single_image single image only
     */
    public function mapAdImages($imageArray, $single_image = false)
    {
        $this->advert['images'] = array();
        $i = 1;
        foreach ($imageArray as $image) {
            if ($image['IsMainImage']) {
                $this->advert['images'][$i]['ord'] = 0;
            } else {
                $this->advert['images'][$i]['ord'] = $i;
            }

            $this->advert['images'][$i]['uri']           = $image['Uri'];
            $this->advert['images'][$i]['last_modified'] = $image['LastModified'];
            $this->advert['images'][$i]['main_image']    = $image['IsMainImage'];

            $group_dir = substr($origFeed['unique_id'], 0, 8);
            $idir      = $origFeed['feed_type'].'_'.$origFeed['ref_site_id'].'/'.$group_dir;
            $fileName  = $idir.'/'.$origFeed['unique_id'].'_'.basename($image['Uri']);
            $this->advert['images'][$i]['local_path'] = $fileName;

            if ($single_image) {
                break;
            }
            $i++;
        }

        $this->advert['image_hash'] = md5(serialize($origFeed['images']));
        $this->advert['image_count'] = count($imageArray);
    }

    /**
     * Get condition id.
     *
     * @param string $string
     *
     * @return number
     */
    public function getConditionId($string)
    {
        if ($string == 'new') {
            return 5;
        } elseif ($string == 'Excellent') {
            return 6;
        } elseif ($string == 'Good') {
            return 7;
        } elseif ($string == 'Average') {
            return 8;
        } elseif ($string == 'Poor') {
            return 9;
        } else {
            return 5;
        }
    }

    /**
     * Assign ad package
     *
     * @param object $ad
     * @param object $user
     */
    protected function assignOrRemoveAdPackage($ad, $user = null)
    {
        if (isset($origFeed['package_id']) && $origFeed['package_id'] && $ad->getStatus() && $ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID) {
            $this->handleAdPackage($ad, $user, 'assign-if-no-package');
        } else {
            //$this->handleAdPackage($ad, $user, 'remove');
        }
    }

    /**
     * Remove or assign package
     *
     * @param object $ad
     * @param object $user
     * @param string $type
     */
    protected function handleAdPackage($ad, $user = null, $type = 'update')
    {
        $deleteManager = $this->getContainer()->get('fa.deletemanager');
        $adId = $ad->getId();
        $adUserPackage = $this->em->getRepository('FaAdBundle:AdUserPackage')->findOneBy(array('ad_id' => $adId, 'status' => AdUserPackageRepository::STATUS_ACTIVE), array('id' => 'DESC'));

        if ($adUserPackage && $type == 'remove') {
            //remove ad use package upsell
            $objAdUserPackageUpsells = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
            if ($objAdUserPackageUpsells  && $type == 'remove') {
                foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                    $deleteManager->delete($objAdUserPackageUpsell);
                }
            }
            //remove ad user package
            $deleteManager->delete($adUserPackage);
        } elseif ($type == 'update' && (!$adUserPackage || ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $origFeed['package_id']))) {
            if ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $origFeed['package_id']) {
                //remove ad use package upsell
                $objAdUserPackageUpsells = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
                if ($objAdUserPackageUpsells) {
                    foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                        $deleteManager->delete($objAdUserPackageUpsell);
                    }
                }

                //remove ad user package
                $deleteManager->delete($adUserPackage);
            }

            $this->em->getRepository('FaAdBundle:AdUserPackage')->clear();
            $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->clear();
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($origFeed['package_id']);
            $adUserPackage->setPackage($package);

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackage->setAdMain($adMain);
            $adUserPackage->setAdId($adId);
            $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
            $adUserPackage->setStartedAt(time());
            if ($package->getDuration()) {
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            // set user
            if ($user) {
                $adUserPackage->setUser($user);
            }

            $adUserPackage->setPrice($package->getPrice());
            $adUserPackage->setDuration($package->getDuration());
            $this->em->persist($adUserPackage);
            $this->em->flush();
        } elseif ($type == 'assign-if-no-package' && !$adUserPackage && $origFeed['package_id']) {
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($origFeed['package_id']);
            $adUserPackage->setPackage($package);

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackage->setAdMain($adMain);
            $adUserPackage->setAdId($adId);
            $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
            $adUserPackage->setStartedAt(time());
            if ($package->getDuration()) {
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            // set user
            if ($user) {
                $adUserPackage->setUser($user);
            }

            $adUserPackage->setPrice($package->getPrice());
            $adUserPackage->setDuration($package->getDuration());
            $this->em->persist($adUserPackage);
            $this->em->flush();
        }

        if (isset($adUserPackage) && $adUserPackage && $adUserPackage->getId() && $type == 'update') {
            $packageUpsellIds = array();
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($origFeed['package_id']);
            foreach ($package->getUpsells() as $upsell) {
                $this->addAdUserPackageUpsell($ad, $adUserPackage, $upsell);
                $packageUpsellIds[] = $upsell->getId();
            }
            $objAdUserPackageUpsells = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
            foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                if (!in_array($objAdUserPackageUpsell->getUpsell()->getId(), $packageUpsellIds)) {
                    $deleteManager->delete($objAdUserPackageUpsell);
                }
            }
        }

        $isWeeklyRefresh = $this->em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId());

        // Check weekly refresh upsell purchased then set weekly_refresh_at field
        if ($isWeeklyRefresh && !$ad->getWeeklyRefreshAt()) {
            $ad->setWeeklyRefreshAt(time());
        } elseif ($isWeeklyRefresh && $ad->getWeeklyRefreshAt() && $ad->getPublishedAt() && $ad->getWeeklyRefreshAt() < $ad->getPublishedAt()) {
            $ad->setWeeklyRefreshAt($ad->getPublishedAt());
        } elseif ($isWeeklyRefresh === false) {
            $ad->setWeeklyRefreshAt(null);
        }

        $this->em->persist($ad);
        $this->em->flush($ad);
    }

    /**
     * Add ad user package upsell
     *
     * @param object $ad
     * @param object $adUserPackage
     * @param object $upsell
     */
    protected function addAdUserPackageUpsell($ad, $adUserPackage, $upsell)
    {
        $adId = $ad->getId();
        $adUserPackageUpsellObj = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findOneBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId(), 'status' => 1, 'upsell' => $upsell->getId()));
        if (!$adUserPackageUpsellObj) {
            $adUserPackageUpsell = new AdUserPackageUpsell();
            $adUserPackageUpsell->setUpsell($upsell);

            // set ad user package id.
            if ($adUserPackage) {
                $adUserPackageUpsell->setAdUserPackage($adUserPackage);
            }

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackageUpsell->setAdMain($adMain);
            $adUserPackageUpsell->setAdId($adId);

            $adUserPackageUpsell->setValue($upsell->getValue());
            $adUserPackageUpsell->setValue1($upsell->getValue1());
            $adUserPackageUpsell->setDuration($upsell->getDuration());
            $adUserPackageUpsell->setStatus(1);
            $adUserPackageUpsell->setStartedAt(time());
            if ($upsell->getDuration()) {
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            $this->em->persist($adUserPackageUpsell);
            $this->em->flush();
        }
    }

    /**
     * Setcommon data.
     *
     * @param array   $origFeed
     */
    protected function setCommonData($origFeed)
    {
        $this->advert['published_date'] = strtotime($origFeed['StartDate']);
        $this->advert['updated_date'] = strtotime($origFeed['LastModified']);

        if (($origFeed['EndDate'] == '0001-01-01T00:00:00Z' || strtotime($origFeed['EndDate']) >= time()) && $commonDataArr['status'] == 'A') {
            $this->advert['status'] = 'A';
        } elseif ($origFeed['EndDate'] != '0001-01-01T00:00:00Z' && strtotime($origFeed['EndDate']) < time() && $commonDataArr['status'] == 'A') {
            $this->advert['status'] = 'E';
            $this->advert['end_date'] = strtotime($origFeed['EndDate']);
        }

        $ec = $this->getContainer()->get('fa.entity.cache.manager');

        if (isset($origFeed['Details']['Condition'])) {
            $this->advert['condition'] = $this->getConditionId($origFeed['Details']['Condition']);
        }

        $this->advert['is_trade_ad'] = 1;
        if (isset($origFeed['IsPrivateSeller']) && $origFeed['IsPrivateSeller'] != null && $origFeed['IsPrivateSeller'] == true) {
            $this->advert['is_trade_ad'] = 0;
        }

        if (isset($origFeed['AdvertSource'])) {
            if (in_array(strtolower($origFeed['AdvertSource']), array('kapow scrape', 'not specified')) && $commonDataArr['affiliate'] == 1) {
                if (isset($origFeed['SiteVisibility']) && is_array($origFeed['SiteVisibility'])) {
                    foreach ($origFeed['SiteVisibility'] as $site) {
                        if (($site['IsMainSite'] === 'true') || ($site['IsMainSite'] === true)) {
                            $this->advert['advert_source'] = CommonManager::addHttpToUrl($site['Site']);
                        }
                    }
                }
            }

            if (!isset($this->advert['advert_source']) || $this->advert['advert_source'] == '') {
                $this->advert['advert_source'] = CommonManager::addHttpToUrl($origFeed['AdvertSource']);
            }
        }

        $this->advert['title'] = $origFeed['Title'];
        $this->advert['price'] = $origFeed['Price'];
        $this->advert['currency'] = $origFeed['Currency'];
        $this->advert['description'] = isset($origFeed['Descriptions'][0]['Text']) ? $origFeed['Descriptions'][0]['Text'] : null;
        $this->advert['trans_id'] = $origFeed['OriginatorsReference'];
        $this->advert['unique_id'] = isset($origFeed['Id']) ? $origFeed['Id'] : null;
        $this->advert['last_modified'] = $origFeed['LastModified'];
        $location_not_found = false;

        if ($origFeed['AdvertType'] == 'MotorhomeAdvert' || $origFeed['AdvertType'] == 'VehicleAdvert' || $origFeed['AdvertType'] == 'ClickEditVehicleAdvert' || $origFeed['AdvertType'] == 'JobAdvert') {
            $locationArray = array();
            if ($origFeed['Advertiser']['Postcode']) {
                $locationArray = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($origFeed['Advertiser']['Postcode'], $this->getContainer(), true);
            }
            if (!empty($locationArray)) {
                $location_not_found = true;
            } else {
                $townstring = explode(',', $origFeed['Advertiser']['TownCity']);
                if (isset($townstring[0]) && $townstring[0]) {
                    if ($ec->getEntityIdByName('FaEntityBundle:Location', $townstring[0])) {
                        $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townstring[0], $this->getContainer(), 'name');
                        $location_not_found = true;
                    } else {
                        $locationArray = $this->em->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($townstring[0], $this->getContainer(), 'name');
                        $location_not_found = true;
                    }
                }
            }
        } else {
            if ($origFeed['Town'] != '') {
                $this->advert['location']['locality'] = $origFeed['Locality'];
                $townstring = explode(',', $origFeed['Town']);

                if ($townstring[0] && $ec->getEntityIdByName('FaEntityBundle:Location', $townstring[0])) {
                    $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townstring[0], $this->getContainer(), 'name');
                    $location_not_found = true;
                } elseif ($townstring[0]) {
                    $locationArray = $this->em->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($townstring[0], $this->getContainer(), 'name');
                    $location_not_found = true;
                }
            } else {
                $locationArray = array();
                if ($origFeed['Advertiser']['Postcode']) {
                    $locationArray = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($origFeed['Advertiser']['Postcode'], $this->getContainer(), true);
                }

                if (!empty($locationArray)) {
                    $location_not_found = true;
                } else {
                    $townstring = explode(',', $origFeed['Advertiser']['TownCity']);
                    if (isset($townstring[0]) && $townstring[0]) {
                        if ($ec->getEntityIdByName('FaEntityBundle:Location', $townstring[0])) {
                            $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townstring[0], $this->getContainer(), 'name');
                            $location_not_found = true;
                        } else {
                            $locationArray = $this->em->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($townstring[0], $this->getContainer(), 'name');
                            $location_not_found = true;
                        }
                    }
                }
            }
        }

        if ($location_not_found == true) {
            if (!empty($locationArray) && $origFeed['Advertiser']['Postcode']) {
                // Fall back to advertiser
                $locationArray = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($origFeed['Advertiser']['Postcode'], $this->getContainer(), true);
            }

            if (!empty($locationArray)) {
                $this->advert['location']['town_id'] = isset($locationArray['town_id']) && $locationArray['town_id'] ? $locationArray['town_id'] : null;
                $this->advert['location']['latitude'] = isset($locationArray['latitude']) && $locationArray['latitude'] ? $locationArray['latitude'] : null;
                $this->advert['location']['longitude'] = isset($locationArray['longitude']) && $locationArray['longitude'] ? $locationArray['longitude'] : null;
                $this->advert['location']['locality_id'] = isset($locationArray['locality_id']) && $locationArray['locality_id'] ? $locationArray['locality_id'] : null;
                $this->advert['location']['county_id'] = isset($locationArray['county_id']) && $locationArray['county_id'] ? $locationArray['county_id'] : null;

                if (isset($locationArray['town_id']) && ($locationArray['town_id'] == LocationRepository::LONDON_TOWN_ID || (isset($locationArray['lvl']) && $locationArray['lvl'] == 4))) {
                    $this->advert['location']['postcode'] = isset($origFeed['Advertiser']['Postcode']) && $origFeed['Advertiser']['Postcode'] ? $origFeed['Advertiser']['Postcode'] : null;
                } else {
                    $this->advert['location']['postcode'] = isset($locationArray['postcode']) && $locationArray['postcode'] ? $locationArray['postcode'] : null;
                }
                $this->advert['location']['countrycode'] = 'GB';
            }
        }
        return $this->advert;
    }

    protected function getCategoryId($origFeed) {
        $feedReader  = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');
        $categoryId = '';
        switch($origFeed['AdvertType']) {
            case ($origFeed['AdvertType']=='BoatAdvert') :
                $categoryId = $this->getBoatCategoryId($origFeed['Details']['BoatType']);
                break;
        }
        return $categoryId;
    }


    /**
     * Set ad table data.
     *
     * @param object $user
     * @param object $ad
     *
     * @return Ambigous <string, \Fa\Bundle\AdBundle\Entity\Ad>
     */
    protected function setAd($origFeed, $adMain, $user)
    {
        $newAd = false;

        $ad = new Ad();
        $newAd = true;

        $this->advert['description'] = isset($origFeed['Descriptions'][0]['Text']) ? $origFeed['Descriptions'][0]['Text'] : null;

        $this->advert['affiliate'] = 0;
        if (($origFeed['SiteVisibility']['IsMainSite'] === 'false') || ($origFeed['SiteVisibility']['IsMainSite'] === false)) {
            $this->advert['affiliate'] = 1;
        }

        if (isset($origFeed['title'])) {
            $ad->setTitle($origFeed['title']);
        }

        if (isset($origFeed['affiliate']) && $origFeed['affiliate'] == 1) {
            $ad->setAffiliate(1);

            if (isset($origFeed['image_count'])) {
                $ad->setImageCount($origFeed['image_count']);
            }

            if (isset($origFeed['track_back_url'])) {
                $ad->setTrackBackUrl($origFeed['track_back_url']);
            }
        }

        if (isset($origFeed['advert_source'])) {
            $ad->setSource($origFeed['advert_source']);
        } else {
            $ad->setSource('Feed advert: source not provided');
        }

        if (isset($origFeed['description'])) {
            $ad->setDescription($origFeed['description']);
        } else {
            $ad->setDescription(null);
        }

        if (isset($origFeed['personalized_title'])) {
            $ad->setPersonalizedTitle($origFeed['personalized_title']);
        } else {
            $ad->setPersonalizedTitle(null);
        }

        if (isset($origFeed['unique_id'])) {
            $ad->setTransId($origFeed['unique_id']);
        }

        if (isset($origFeed['price'])) {
            $ad->setPrice($origFeed['price']);
        } else {
            $ad->setPrice(null);
        }

        $metadata = $this->em->getClassMetaData('Fa\Bundle\AdBundle\Entity\Ad');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $ad->setId($adMain->getId());
        $ad->setAdMain($adMain);

        $ad->setType($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_TYPE_FORSALE_ID));

        if (isset($origFeed['category_id']) && $origFeed['category_id'] > 0) {
            $ad->setCategory($this->em->getReference('FaEntityBundle:Category', $origFeed['category_id']));
        } else {
            $ad->setCategory(null);
        }

        if (isset($origFeed['ad_type_id']) && $origFeed['ad_type_id'] > 0) {
            $ad->setType($this->em->getReference('FaEntityBundle:Entity', $origFeed['ad_type_id']));
        } else {
            $ad->setType(null);
        }

        if (isset($origFeed['delivery_method_option_id']) && $origFeed['delivery_method_option_id'] > 0) {
            $ad->setDeliveryMethodOption($this->em->getReference('FaPaymentBundle:DeliveryMethodOption', $origFeed['delivery_method_option_id']));
        } else {
            $ad->setDeliveryMethodOption(null);
        }

        if (isset($origFeed['payment_method_id']) && $origFeed['payment_method_id'] > 0) {
            $ad->setPaymentMethodId($origFeed['payment_method_id']);
        } else {
            $ad->setPaymentMethodId(null);
        }

        if (isset($origFeed['is_new'])) {
            $ad->setIsNew($origFeed['is_new']);
        } else {
            $ad->setIsNew(1);
        }

        if (isset($origFeed['published_date'])) {
            $ad->setPublishedAt($origFeed['published_date']);
        }

        if (isset($origFeed['end_date'])) {
            $ad->setExpiresAt($origFeed['end_date']);
        }

        if (isset($origFeed['updated_date'])) {
            $ad->setUpdatedAt($origFeed['updated_date']);
        }

        if (isset($origFeed['status']) && $origFeed['status'] == 'A') {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_LIVE_ID));
        } elseif (isset($origFeed['status']) && $origFeed['status'] == 'R') {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_REJECTED_ID));
        } else {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_EXPIRED_ID));
            if (isset($origFeed['end_date'])) {
                $ad->setExpiresAt($origFeed['end_date']);
            } else {
                $ad->setExpiresAt(time());
            }
        }

        $ad->setIsFeedAd(1);
        $ad->setUser($user);
        $ad->setSkipSolr(1); // TO skip solr update

        $rejectedReason = count($origFeed['rejected_reason']) > 0 ? serialize($origFeed['rejected_reason']) : null;

        // Save ad is trade ad or not
        if ($user) {
            $userRoles = $this->em->getRepository('FaUserBundle:User')->getUserRolesArray($user);
            if (!empty($userRoles)) {
                if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $userRoles) || in_array(RoleRepository::ROLE_NETSUITE_SUBSCRIPTION, $userRoles)) {
                    $ad->setIsTradeAd(1);
                } elseif (in_array(RoleRepository::ROLE_SELLER, $userRoles)) {
                    $ad->setIsTradeAd(0);
                }
            }
        } else {
            if (isset($origFeed['is_trade_ad'])) {
                $ad->setIsTradeAd($origFeed['is_trade_ad']);
            } else {
                $ad->setIsTradeAd(null);
            }
        }

        if ($rejectedReason) {
            $ad->setRejectedReason($rejectedReason);
        } else {
            $ad->setRejectedReason(null);
        }

        $this->em->persist($ad);
        $this->em->flush();
        return $ad;
    }

    /**
     * Set ad location.
     *
     * @param object $ad
     */
    protected function setAdLocation($ad, $origFeed)
    {
        $ad_location = $this->em->getRepository('FaAdBundle:AdLocation')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_location) {
            $ad_location = new AdLocation();
        }

        $ad_location->setAd($ad);

        if (isset($origFeed['location']['postcode']) && $origFeed['location']['postcode']) {
            $ad_location->setPostcode($origFeed['location']['postcode']);
        } else {
            $ad_location->setPostcode(null);
        }

        if (isset($origFeed['location']['town_id']) && $origFeed['location']['town_id']) {
            $ad_location->setLocationTown($this->em->getReference('FaEntityBundle:Location', $origFeed['location']['town_id']));
            //check for location area
            if ($origFeed['location']['town_id'] == LocationRepository::LONDON_TOWN_ID) {
                //check post Code is exist
                if (isset($origFeed['location']['postcode']) && $origFeed['location']['postcode'] != '') {
                    $getPostalCode = explode(" ", $origFeed['location']['postcode']);
                    $getArea = $this->em->getRepository('FaEntityBundle:LocationPostal')->getAreasByPostCode($getPostalCode[0]);
                    if (!empty($getArea)) {
                        if (count($getArea) == '1') {
                            $ad_location->setLocationArea($this->em->getReference('FaEntityBundle:Location', $getArea[0]['id']));
                        } elseif (count($getArea) > '1') {
                            //get the nearest area for this location
                            $getNearestAreaObj = $this->em->getRepository('FaEntityBundle:Location')->getNearestAreaByPostLatLong($origFeed['location']['postcode'], $origFeed['location']['town_id']);
                            if (!empty($getNearestAreaObj) && $getNearestAreaObj->getLvl() == 4) {
                                $ad_location->setLocationArea($getNearestAreaObj);
                            }
                        }
                    }
                }
            }
        } else {
            $ad_location->setLocationTown(null);
        }

        if (isset($origFeed['location']['latitude']) && $origFeed['location']['latitude']) {
            $ad_location->setLatitude($origFeed['location']['latitude']);
        } else {
            $ad_location->setLatitude(null);
        }

        if (isset($origFeed['location']['longitude']) && $origFeed['location']['longitude']) {
            $ad_location->setLongitude($origFeed['location']['longitude']);
        } else {
            $ad_location->setLongitude(null);
        }

        if (isset($origFeed['location']['county_id']) && $origFeed['location']['county_id']) {
            $ad_location->setLocationDomicile($this->em->getReference('FaEntityBundle:Location', $origFeed['location']['county_id']));
        } else {
            $ad_location->setLocationDomicile(null);
        }

        if (isset($origFeed['location']['countrycode']) && $origFeed['location']['countrycode'] == 'GB') {
            $ad_location->setLocationCountry($this->em->getReference('FaEntityBundle:Location', 2));
        } else {
            $ad_location->setLocationCountry(null);
        }

        $this->em->persist($ad_location);
    }

    /**
     * Set for sale data.
     *
     * @param object $ad
     */
    protected function setForSaleData($ad)
    {
        $ad_forsale = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_forsale) {
            $ad_forsale = new AdForSale();
        }

        $ad_forsale->setAd($ad);

        if ($this->advert['condition']) {
            $ad_forsale->setConditionId($this->advert['condition']);
        }

        $this->em->persist($ad_forsale);
        $this->em->flush();
    }

    /**
     * Update image data.
     *
     * @param object $ad
     */
    protected function updateImages($ad, $origFeed)
    {
        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';
        $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($ad->getId());

        $i = 1;

        $currentImages = $this->em->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId());

        foreach ($currentImages as $image) {
            $adImageManager = new AdImageManager($this->getContainer(), $ad->getId(), $image->getHash(), $imagePath);
            $adImageManager->removeImage();
            $this->em->remove($image);
        }

        foreach ($origFeed['images'] as $img) {
            $filePath = $this->dir.'/images/'.$img['local_path'];
            $dimension = @getimagesize($filePath);

            if (file_exists($filePath) && $dimension) {
                $hash = CommonManager::generateHash();
                CommonManager::createGroupDirectory($adImageDir, $ad->getId());

                $image = new AdImage();
                $image->setHash($hash);
                $image->setPath('uploads/image/'.CommonManager::getGroupDirNameById($ad->getId()));
                $image->setOrd($i);
                $image->setAd($ad);
                $image->setStatus(1);
                $image->setImageName(Urlizer::urlize($ad->getTitle().'-'.$ad->getId().'-'.$i));
                $image->setAws(0);
                $this->em->persist($image);

                //$origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 75, 'ImageMagickManager');
                //$origImage->loadFile($filePath);
                //$origImage->save($imagePath.'/'.$ad->getId().'_'.$hash.'.jpg', 'image/jpeg');
                exec('convert -flatten '.escapeshellarg($filePath).' '.$imagePath.'/'.$ad->getId().'_'.$hash.'.jpg');

                $adImageManager = new AdImageManager($this->getContainer(), $ad->getId(), $hash, $imagePath);
                $adImageManager->createThumbnail();
                $adImageManager->createCropedThumbnail();

                $adImgPath = $imagePath.'/'.$ad->getId().'_'.$hash.'.jpg';
                if (file_exists($adImgPath)) {
                    $adImageManager->uploadImagesToS3($image);
                    unlink($filePath);
                }

                $i++;
            }
        }

        $this->em->flush();
    }
}