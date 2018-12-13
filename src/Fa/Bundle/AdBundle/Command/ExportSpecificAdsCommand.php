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
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;
use Fa\Bundle\CoreBundle\Manager\EntityCacheManager;

/**
 * This command is used to export specific ads.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ExportSpecificAdsCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * Default history db name
     *
     * @var string
     */
    private $historyDbName;

    /**
     * Full file path
     *
     * @var string
     */
    private $fullFilePath;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:export-specific-ads')
        ->setDescription("Export specific ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run manually when needed.

Actions:
- Can be run to export specifc ads.

Command:
 - php app/console fa:export-specific-ads
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
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->mainDbName    = $this->getContainer()->getParameter('database_name');
        $this->historyDbName = $this->getContainer()->getParameter('database_name_history');
        $this->fullFilePath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/ad/";

        //get arguments passed in command
        $offset = $input->getOption('offset');

        // insert ads statistics.
        if (isset($offset)) {
            $this->exportSpecificAdsWithOffset($input, $output);
        } else {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);

            if (file_exists($this->fullFilePath.'specific_ads.csv')) {
                unlink($this->fullFilePath.'specific_ads.csv');
            }

            $file          = fopen($this->fullFilePath.'specific_ads.tmp', "a+");
            $reportColumns = array();

            $reportColumns = array("Ad ID", "Job title", "Status", "User type", "Category", "Package taken", "Revenue paid", "Number of email enquiries", "Number of phone clicks", "Contract type", "Salary amount", "Salary type", "Years of experience", "Education level", "Location", "Postcode");
            fputcsv($file, $reportColumns);
            fclose($file);

            $this->exportSpecificAds($input, $output);

            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);

            $oldFileName = $this->fullFilePath.'specific_ads.tmp';
            $newFileName = str_replace('.tmp', '.csv', $oldFileName);
            rename($oldFileName, $newFileName);
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
     * Import paa field rule.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function exportSpecificAds($input, $output)
    {
        $count = $this->getAdsCount();
        $output->writeln('Total Ads To Export: '.$count);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export-specific-ads '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Import paa field rule.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function exportSpecificAdsWithOffset($input, $output)
    {
        $offset          = $input->getOption('offset');
        $objAds          = $this->getAdsResult($offset, $this->limit);
        $objAdRepository = $this->entityManager->getRepository('FaAdBundle:Ad');
        $file            = fopen($this->fullFilePath.'specific_ads.tmp', "a+");
        $adEnquiryReportDailyRepository = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdEnquiryReportDaily');
        $adIdArray = array();

        foreach ($objAds as $adArray) {
            $adIdArray[] = $adArray['id'];
        }

        $enquiriesArray  = $adEnquiryReportDailyRepository->getAdEnquiryTotalsByAdId($adIdArray);

        foreach ($objAds as $adArray) {
            $adMetaDataArray = unserialize($adArray['meta_data']);
            $experienceId    = (($adMetaDataArray && isset($adMetaDataArray['years_experience_id'])) ? $adMetaDataArray['years_experience_id'] : null);
            $educationId     = (($adMetaDataArray && isset($adMetaDataArray['education_level_id'])) ? $adMetaDataArray['education_level_id'] : null);
            $locationText    = (isset($adArray['town_id']) ? $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $adArray['town_id']) : '');
            $userRoleText    = $this->entityManager->getRepository('FaUserBundle:User')->getUserRole($adArray['user_id'], $this->getContainer());

            if ($locationText != '') {
                $locationText = $locationText.', '.(isset($adArray['domicile_id']) ? $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $adArray['domicile_id']) : '');
            } else {
                $locationText = (isset($adArray['domicile_id']) ? $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $adArray['domicile_id']) : '');
            }

            $packageDetail = $this->entityManager->getRepository('FaAdBundle:AdUserPackage')->getAdPackagesAndPriceSum($adArray['id']);

            $recordValues   = array();
            $recordValues[] = $adArray['id'];
            $recordValues[] = $adArray['title'];
            $recordValues[] = ($this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $adArray['status_id']));
            $recordValues[] = ($userRoleText == 'ROLE_BUSINESS_SELLER' ? 'Business' :($userRoleText == 'ROLE_NETSUITE_SUBSCRIPTION' ? 'Netsuite Subscription Users':'Private'));
            $recordValues[] = join(' > ', $this->entityManager->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($adArray['category_id'], false, $this->getContainer()));
            $recordValues[] = $packageDetail['package_text'];
            $recordValues[] = $packageDetail['price_sum'];
            $recordValues[] = (($enquiriesArray && isset($enquiriesArray[$adArray['id']]['total_email_send_link'])) ? $enquiriesArray[$adArray['id']]['total_email_send_link'] : '');
            $recordValues[] = (($enquiriesArray && isset($enquiriesArray[$adArray['id']]['total_call_click'])) ? $enquiriesArray[$adArray['id']]['total_call_click'] : '');
            $recordValues[] = $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $adArray['contract_type_id']);
            $recordValues[] = (isset($adMetaDataArray['salary']) ? $adMetaDataArray['salary'] : '');
            $recordValues[] = $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $adArray['salary_band_id']);
            $recordValues[] = (isset($experienceId) ? $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $experienceId) : '');
            $recordValues[] = (isset($educationId) ? $this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $educationId) : '');
            $recordValues[] = $locationText;
            $recordValues[] = $adArray['postcode'];
            //print_r($recordValues); exit;
            fputcsv($file, $recordValues);
        }

        fclose($file);
    }

    /**
     * Get query builder for specific ads count.
     *
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getAdsCount($searchParams = array())
    {
        $sql = "SELECT COUNT(a.id) As total_ads
                FROM ".$this->mainDbName.".ad a
                INNER JOIN ad_jobs aj ON aj.ad_id = a.id
                WHERE a.created_at BETWEEN UNIX_TIMESTAMP('2015/11/01') AND UNIX_TIMESTAMP('2016/10/31');";

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        $res = $stmt->fetch();

        return $res['total_ads'];
    }

    /**
     * Get query builder for specifc ads results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdsResult($offset, $limit, $searchParam = array())
    {
        $sql = "SELECT a.id, a.title, a.status_id, a.user_id,  a.category_id, a.created_at, aj.contract_type_id, aj.salary_band_id, aj.meta_data,  al.postcode, al.country_id, al.domicile_id, al.town_id, al.postcode
                FROM ".$this->mainDbName.".ad a
                INNER JOIN ".$this->mainDbName.".ad_jobs aj ON aj.ad_id = a.id
                LEFT JOIN ".$this->mainDbName.".ad_location al ON al.ad_id = a.id
                WHERE a.original_created_at BETWEEN UNIX_TIMESTAMP('2015/11/01') AND UNIX_TIMESTAMP('2016/10/31')
                GROUP BY a.id
                ORDER BY a.created_at
                LIMIT ".$limit." OFFSET ".$offset;

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        return $stmt->fetchAll();
    }
}
