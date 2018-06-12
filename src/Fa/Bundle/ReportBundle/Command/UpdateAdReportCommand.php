<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Command;

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

/**
 * This command is used to update ad report statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdReportCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * History entity manager
     *
     * @var object
     */
    private $historyEntityManager;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * History db name
     *
     * @var object
     */
    private $historyDbName;

    /**
     * Print package ids
     *
     * @var array
     */
    private $printPackageArray;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-report')
        ->setDescription("Update ad report statistics.")
        ->addArgument('action', InputArgument::OPTIONAL, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update ad report statistics.

Command:
 - php app/console fa:update:ad-report all
 - php app/console fa:update:ad-report beforeoneday
 - php app/console fa:update:ad-report --date="2015-04-28"
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
        // set entity manager.
        $this->entityManager        = $this->getContainer()->get('doctrine')->getManager();
        $this->historyEntityManager = $this->getContainer()->get('doctrine')->getManager('history');
        $this->historyDbName        = $this->getContainer()->getParameter('database_name_history');
        $this->mainDbName           = $this->getContainer()->getParameter('database_name');
        $this->printPackageArray    = $this->entityManager->getRepository('FaPromotionBundle:Package')->getPrintPackagesArray();

        //get arguments passed in command
        $offset = $input->getOption('offset');
        $date   = $input->getOption('date');
        $action = $input->getArgument('action');

        if ($action == 'beforeoneday' || $action == 'all' || $date) {
            if (!isset($offset)) {
                $start_time = time();
                $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
                if ($action == 'all') {
                    $adReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdReportDaily')->getTableName();
                    $adPrintInsertDateReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdPrintInsertDateReportDaily')->getTableName();
                    $output->writeln('Truncating ad_report_daily records...', true);
                    $this->executeRawQuery('TRUNCATE TABLE '.$this->historyDbName.'.'.$adReportDailyTableName.';', $this->historyEntityManager);
                    $output->writeln('Truncating ad_print_insert_date_report_daily records...', true);
                    $this->executeRawQuery('TRUNCATE TABLE '.$this->historyDbName.'.'.$adPrintInsertDateReportDailyTableName.';', $this->historyEntityManager);
                }
            }

            // insert ads statistics.
            if (isset($offset)) {
                $this->updateAdStatisticsWithOffset($input, $output);
            } else {
                $this->updateAdStatistics($input, $output);
            }

            if (!isset($offset)) {
                $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
                $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
            }
        } elseif (!$action && !$date) {
            $output->writeln('Please enter either action argument or date option.', true);
        }
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
     * Update user total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdStatistics($input, $output)
    {
        $action = $input->getArgument('action');
        $date   = $input->getOption('date');
        $count  = $this->getAdCount($action, $date);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-report '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdStatisticsWithOffset($input, $output)
    {
        $action                   = $input->getArgument('action');
        $date                     = $input->getOption('date');
        $adReportDailyTableName   = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdReportDaily')->getTableName();
        $adPrintInsertDateReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdPrintInsertDateReportDaily')->getTableName();
        $offset                   = $input->getOption('offset');

        $ads       = $this->getAdResult($action, $date, $offset, $this->limit);
        $paymentAdIdArray = array();
        $adIdArray     = array();
        $adUserIdArray = array();
        $adminUserIdArray = array();
        list($startDate, $endDate) = $this->getDateInTimeStamp($date);
        foreach ($ads as $ad) {
            $adIdArray[]     = $ad['id'];
            $adUserIdArray[$ad['id']] = $ad['user_id'];
            $adminUserIdArray[] = $ad['admin_user_id'];
            if (($ad['created_at'] && $ad['created_at'] >= $startDate && $ad['created_at'] <= $endDate) || ($ad['published_at'] && $ad['published_at'] >= $startDate && $ad['published_at'] <= $endDate)) {
                $paymentAdIdArray[] = $ad['id'];
            }
            $paymentAdIdArray = array_unique($paymentAdIdArray);
            $adminUserIdArray = array_unique($adminUserIdArray);
        }
        if (count($adIdArray)) {
            $this->historyEntityManager->getRepository('FaReportBundle:AdReportDaily')->removeAdReportDailyAdsByIds($adIdArray, $date);
            $this->historyEntityManager->getRepository('FaReportBundle:AdPrintInsertDateReportDaily')->removeAdPrintInsertDateReportDailyAdsByIds($adIdArray, $date);
            $output->writeln('Inserting records in ad_report_daily....', true);
            $shopUserIds        = array_unique($adUserIdArray);
            $adLocations        = $this->entityManager->getRepository('FaAdBundle:AdLocation')->findByAdId($adIdArray, true, true);
            $adImages           = $this->entityManager->getRepository('FaAdBundle:AdImage')->getAdImageCountArrayByAdId($adIdArray);
            $adUserPackages     = $this->entityManager->getRepository('FaAdBundle:AdUserPackage')->getAdPackageArrayByAdIdForAdReportDaily($adIdArray, 'ad');
            $adPrints           = $this->entityManager->getRepository('FaAdBundle:AdPrint')->getAdPrintDetailByAdIdForAdReport($adIdArray, $startDate, $endDate, false, false);
            $adUserShopPackages = $this->entityManager->getRepository('FaUserBundle:UserPackage')->getShopPackageDetailByUserIdForAdReport($shopUserIds);
            $adPayments         = $this->entityManager->getRepository('FaPaymentBundle:PaymentTransaction')->getPaymentTransactionForReportByAdIds($paymentAdIdArray, $startDate, $endDate);
            $adminUserDetails   = $this->entityManager->getRepository('FaUserBundle:User')->getUserDataArrayByUserId($adminUserIdArray);
            $userPhoneDetails   = $this->entityManager->getRepository('FaUserBundle:User')->getUserPhoneDetail(array_unique($adUserIdArray));
            $ipAddresses        = $this->entityManager->getRepository('FaAdBundle:AdIpAddress')->getIpAddressesByAdIds($adIdArray);

            $insertSql = 'INSERT INTO '.$this->historyDbName.'.'.$adReportDailyTableName.
            '(`ad_id`, `user_id`, `ad_created_at`, `print_insert_date`, `published_at`, `is_edit`, `is_renewed`, `is_expired`, `expires_at`, `expired_at`, `status_id`, `category_id`, `postcode`, `town_id`, `county_id`, `print_edition_ids`, `source`, `source_latest`, `role_id`, `no_of_photos`, `total_revenue_gross`, `print_revenue_gross`, `online_revenue_gross`, `total_revenue_net`, `print_revenue_net`, `online_revenue_net`, `package_id`, `package_name`, `package_sr_no`, `duration_print`, `duration_online`, `shop_package_id`, `shop_package_name`, `shop_package_revenue`, `created_at`, `renewed_at`, `edited_at`, `admin_user_email`, `payment_method`, `ad_price`, `skip_payment_reason`, `is_discount_code_used`, `phones`, `is_credit_used`, `credit_value`, `ti_ad_id`, `ip_addresses`) VALUES';

            foreach ($ads as $ad) {
                $isNew     = 0;
                $isEdit    = 0;
                $isRenewed = 0;
                $isExpired = 0;
                $isDiscountCodeUsed = 0;
                $isCreditUsed = 0;
                $creditUsedValue = array();
                $paymentMethod = null;
                $skipPaymentReason = null;
                $source    = $ad['source'];
                $sourceLatest = ($ad['source_latest'] != '' ? $ad['source_latest'] : $source);
                $durationOnline = 0;
                $postCode  = (isset($adLocations[$ad['id']]) ? $adLocations[$ad['id']]['postcode'] : null);
                $countyId  = (isset($adLocations[$ad['id']]) ? $adLocations[$ad['id']]['domicile_id'] : null);
                $townId    = (isset($adLocations[$ad['id']]) ? $adLocations[$ad['id']]['town_id'] : null);
                $adImageCnt = (isset($adImages[$ad['id']]) ? $adImages[$ad['id']] : 0);
                $adUserPackage = (isset($adUserPackages[$ad['id']]) ? $adUserPackages[$ad['id']] : null);
                $adUserPhoneDetails = $this->getAdUserPhoneDetails($userPhoneDetails, $ad);
                $userRole  = $this->entityManager->getRepository('FaUserBundle:User')->getUserRole($ad['user_id'], $this->getContainer());
                $userRoleId = null;
                $packageId = ($adUserPackage ? $adUserPackage['package_id'] : 0);
                $packageSrNo = ($adUserPackage ? $adUserPackage['package_sr_no'] : null);
                if (!$packageSrNo) {
                    $packageSrNo = 8;
                }

                $packageName = ($adUserPackage ? $packageSrNo.' ('.($adUserPackage['package_text'] ? $adUserPackage['package_text'] : 'Other Package').')' : null);
                $shopPackageId = (isset($adUserShopPackages[$adUserIdArray[$ad['id']]]) ? $adUserShopPackages[$adUserIdArray[$ad['id']]]['package_id'] : 0);
                $shopPackageName = (isset($adUserShopPackages[$adUserIdArray[$ad['id']]]) ? $adUserShopPackages[$adUserIdArray[$ad['id']]]['package_sr_no'].' ('.$adUserShopPackages[$adUserIdArray[$ad['id']]]['package_text'].')' : null);
                $adPayment = (isset($adPayments[$ad['id']]) ? $adPayments[$ad['id']] : null);
                $adminUserEmail = (isset($adminUserDetails[$ad['admin_user_id']]) ? $adminUserDetails[$ad['admin_user_id']]['email'] : null);

                $printInsertDate = null;
                $printEditionIds = array();
                $durationPrint   = null;

                if (isset($adPrints[$ad['id']]) && count($adPrints[$ad['id']])) {
                    foreach ($adPrints[$ad['id']] as $printEditionId => $adPrint) {
                        if (!$printInsertDate || ($adPrint['insert_date'] > $printInsertDate)) {
                            $printInsertDate   = $adPrint['insert_date'];
                        }
                        $printEditionIds[] = $printEditionId;
                        $durationPrint     = $adPrint['duration'];
                    }
                    $printEditionIds = array_unique($printEditionIds);
                    asort($printEditionIds);
                }

                if ($ad['status_id'] == EntityRepository::AD_STATUS_LIVE_ID) {
                    $durationOnline = floor(ConfigRepository::DEFAULT_EXPIRATION_DAYS / 7);
                }

                //revenue
                $totalRevenueGross  = 0;
                $printRevenueGross  = 0;
                $onlineRevenueGross = 0;
                $totalRevenueNet    = 0;
                $printRevenueNet    = 0;
                $onlineRevenueNet   = 0;
                $shopPackageRevenue = 0;

                if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER) {
                    $userRoleId = RoleRepository::ROLE_BUSINESS_SELLER_ID;
                } elseif ($userRole == RoleRepository::ROLE_SELLER) {
                    $userRoleId = RoleRepository::ROLE_SELLER_ID;
                }

                if (($ad['created_at'] && $ad['created_at'] >= $startDate && $ad['created_at'] <= $endDate) || ($ad['published_at'] && $ad['published_at'] >= $startDate && $ad['published_at'] <= $endDate)) {
                    $isNew = 1;
                }
                if ($ad['edited_at'] && $ad['edited_at'] >= $startDate && $ad['edited_at'] <= $endDate) {
                    $isEdit = 1;
                }
                if ($ad['renewed_at'] && $ad['renewed_at'] >= $startDate && $ad['renewed_at'] <= $endDate) {
                    $isRenewed = 1;
                }
                if ($ad['expires_at'] && $ad['expires_at'] >= $startDate && $ad['expires_at'] <= $endDate) {
                    $isExpired = 1;
                }

                if ($adUserPackage && $adPayment['created_at'] && $adPayment['created_at'] >= $startDate && $adPayment['created_at'] <= $endDate && ($isNew || $isRenewed || $action == 'all')) {
                    $paymentMethod     = $adPayment['payment_method'];
                    if ($adPayment['skip_payment_reason']) {
                        $skipPaymentReason = 'Skip Payment ['.$adPayment['skip_payment_reason'].']';
                    } else {
                        $paymentValue = array();
                        try {
                            $paymentValue = unserialize($adPayment['payment_value']);
                        } catch (\Exception $e) {
                        }

                        if ($adPayment['payment_discount_amount'] > 0 && isset($paymentValue['discount_values']) && count($paymentValue['discount_values']) && isset($paymentValue['discount_values']['code'])) {
                            $isDiscountCodeUsed = 1;
                            $discountAmount = $adPayment['payment_discount_amount'];
                            $adUserPackage['price'] = ($adUserPackage['price'] - $discountAmount);
                            if ($adUserPackage['price'] < 0) {
                                $adUserPackage['price'] = 0;
                            }
                        }

                        $paymentTransactionDetailValue = array();

                        try {
                            $paymentTransactionDetailValue = unserialize($adPayment['payment_trans_detail_value']);
                        } catch (\Exception $e) {
                        }

                        if (isset($paymentTransactionDetailValue['user_credit_id']) && isset($paymentTransactionDetailValue['user_credit'])) {
                            $isCreditUsed = 1;
                            $creditUsedValue = array(
                                'user_credit_id' => $paymentTransactionDetailValue['user_credit_id'],
                                'user_credit' => $paymentTransactionDetailValue['user_credit']
                            );
                            $adUserPackage['price'] = 0;
                        }

                        $totalRevenueGross = $adUserPackage['price'];
                        $totalRevenueNet   = CommonManager::getNetAmountFromGrossAmount($totalRevenueGross, $this->getContainer());

                        //if (in_array($packageSrNo, array(6, 7))) {
                        if (in_array($packageId, $this->printPackageArray)) {
                            $onlineRevenueGross = (($adUserPackage['price'] * 45) / 100);
                            $onlineRevenueNet   = CommonManager::getNetAmountFromGrossAmount($onlineRevenueGross, $this->getContainer());
                            $printRevenueGross  = (($adUserPackage['price'] * 55) / 100);
                            $printRevenueNet    = CommonManager::getNetAmountFromGrossAmount($printRevenueGross, $this->getContainer());
                        } else {
                            $onlineRevenueGross = $adUserPackage['price'];
                            $onlineRevenueNet   = CommonManager::getNetAmountFromGrossAmount($onlineRevenueGross, $this->getContainer());
                        }
                    }
                }

                $ipAddressesStr = '';
                if (array_key_exists($ad['id'], $ipAddresses)) {
                    $ipAddressesStr = $ipAddresses[$ad['id']];
                }

                $insertSql .= '("'.$ad['id'].'", "'.$ad['user_id'].'", "'.$ad['created_at'].'", "'.$printInsertDate.'", "'.$ad['published_at'].'", "'.$isEdit.'", "'.$isRenewed.'", "'.$isExpired.'", "'.($ad['expires_at'] && !$isExpired ? $ad['expires_at'] : null ).'", "'.($ad['expires_at'] && $isExpired ? $ad['expires_at'] : null ).'", "'.$ad['status_id'].'", "'.$ad['category_id'].'", "'.$postCode.'", "'.$townId.'", "'.$countyId.'", "'.implode(',', $printEditionIds).'", "'.$source.'", "'.$sourceLatest.'", "'.$userRoleId.'", "'.$adImageCnt.'", "'.$totalRevenueGross.'", "'.$printRevenueGross.'", "'.$onlineRevenueGross.'", "'.$totalRevenueNet.'", "'.$printRevenueNet.'", "'.$onlineRevenueNet.'", "'.$packageId.'", "'.$packageName.'", "'.$packageSrNo.'", "'.$durationPrint.'", "'.$durationOnline.'", "'.$shopPackageId.'", "'.$shopPackageName.'", "'.$shopPackageRevenue.'", '.($action == 'all' ? time() : ($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60))).', "'.$ad['renewed_at'].'", "'.$ad['edited_at'].'", "'.$adminUserEmail.'", "'.$paymentMethod.'", "'.$ad['ad_price'].'", "'.$skipPaymentReason.'", "'.$isDiscountCodeUsed.'", "'.$adUserPhoneDetails.'", "'.$isCreditUsed.'", "'.mysql_escape_string(serialize($creditUsedValue)).'", "'.$ad['ti_ad_id'].'", "'.$ipAddressesStr.'"), ';
            }

            $insertSql = trim($insertSql, ', ');
            $this->executeRawQuery($insertSql, $this->historyEntityManager);

            $this->entityManager->getRepository('FaAdBundle:AdIpAddress')->deleteRecordsByAdIds($adIdArray);

            //insert into ad_print_insert_date_report_daily
            $printDateInsertFlag = false;
            $adReportIds  = $this->historyEntityManager->getRepository('FaReportBundle:AdReportDaily')->getAdReportIdsByAdIds($adIdArray);
            $adPrintDates = $this->entityManager->getRepository('FaAdBundle:AdPrint')->getAdPrintDetailByAdIdForAdReport($adIdArray, $startDate, $endDate, true);

            $insertSql = 'INSERT INTO '.$this->historyDbName.'.'.$adPrintInsertDateReportDailyTableName.
            '(`ad_id`, `ad_report_daily_id`, `print_insert_date`, `print_edition_id`, `created_at`) VALUES';
            foreach ($adIdArray as $adId) {
                $printDatesArray = (isset($adPrintDates[$adId]) ? $adPrintDates[$adId] : array());
                $printDatesArray = CommonManager::arrayUnique($printDatesArray);
                foreach ($printDatesArray as $printDates) {
                    $printDateInsertFlag = true;
                    $insertSql .= '("'.$adId.'", "'.$adReportIds[$adId].'", "'.$printDates['insert_date'].'", "'.$printDates['print_edition_id'].'", '.($action == 'all' ? time() : ($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60))).'), ';
                }
            }

            if ($printDateInsertFlag) {
                $output->writeln('Inserting records in ad_print_insert_date_report_daily....', true);
                $insertSql = trim($insertSql, ', ');
                $this->executeRawQuery($insertSql, $this->historyEntityManager);
            }
        }
    }

    /**
     * Get query builder for ads.
     *
     * @param string $action       Action name.
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getAdCount($action, $date, $searchParams = array())
    {
        $adRepository  = $this->entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder()
            ->select('COUNT('.AdRepository::ALIAS.'.id)');

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.published_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.edited_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.expires_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.renewed_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        } elseif ($action == 'all') {
            $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_LIVE_ID);
        }
        $query->distinct(AdRepository::ALIAS.'.id');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get user ad count results.
     *
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdResult($action, $date, $offset, $limit, $searchParam = array())
    {
        $adRepository  = $this->entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder()
        ->addSelect(AdRepository::ALIAS.'.id', AdRepository::ALIAS.'.created_at', AdRepository::ALIAS.'.published_at', AdRepository::ALIAS.'.edited_at', AdRepository::ALIAS.'.renewed_at', AdRepository::ALIAS.'.expires_at', AdRepository::ALIAS.'.expires_at', 'IDENTITY('.AdRepository::ALIAS.'.status) as status_id', 'IDENTITY('.AdRepository::ALIAS.'.category) as category_id', 'IDENTITY('.AdRepository::ALIAS.'.user) as user_id', AdRepository::ALIAS.'.source', AdRepository::ALIAS.'.source_latest', AdRepository::ALIAS.'.admin_user_id', AdRepository::ALIAS.'.price as ad_price', AdRepository::ALIAS.'.phone as ad_phone', AdRepository::ALIAS.'.business_phone', AdRepository::ALIAS.'.ti_ad_id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.published_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.edited_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.expires_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdRepository::ALIAS.'.renewed_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        } elseif ($action == 'all') {
            $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_LIVE_ID);
        }

        $query->distinct(AdRepository::ALIAS.'.id');

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get date in time stamp
     *
     * @param string $date Date.
     *
     * @return array
     */
    private function getDateInTimeStamp($date)
    {
        if ($date) {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime($date)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime($date)));
        } else {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60)));
        }

        return array($startDate, $endDate);
    }

    /*
     * Get ad user phone details
     *
     * @param array $userPhoneDetails User phone details.
     * @param array $ad               Ad detail.
     *
     * @return array
     */
    private function getAdUserPhoneDetails($userPhoneDetails, $ad)
    {
        $phoneString = '';
        if (isset($userPhoneDetails[$ad['user_id']]['phone']) && $userPhoneDetails[$ad['user_id']]['phone']) {
            $phoneString .= 'Account phone: '.$userPhoneDetails[$ad['user_id']]['phone'].', ';
        }

        if (isset($userPhoneDetails[$ad['user_id']]['phone1']) && $userPhoneDetails[$ad['user_id']]['phone1']) {
            $phoneString .= 'Business phone 1: '.$userPhoneDetails[$ad['user_id']]['phone1'].', ';
        }

        if (isset($userPhoneDetails[$ad['user_id']]['phone2']) && $userPhoneDetails[$ad['user_id']]['phone2']) {
            $phoneString .= 'Business phone 2: '.$userPhoneDetails[$ad['user_id']]['phone2'].', ';
        }

        if (isset($ad['business_phone']) && $ad['business_phone']) {
            $phoneString .= 'Ad specific phone number: '.$ad['business_phone'].', ';
        }

        if (isset($ad['phone']) && $ad['phone']) {
            $phoneString .= 'Ad specific phone number: '.$ad['phone'].', ';
        }

        return trim($phoneString, ', ');
    }
}
