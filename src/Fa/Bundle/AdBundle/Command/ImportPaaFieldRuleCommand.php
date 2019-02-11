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

/**
 * This command is used to insert paa field rule.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ImportPaaFieldRuleCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 5;

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
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:import:paa-filed-rule')
        ->setDescription("Import paa field rule.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run manually when needed.

Actions:
- Can be run to import paa field rule.

Command:
 - php app/console fa:import:paa-filed-rule
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

        //get arguments passed in command
        $offset = $input->getOption('offset');

        // insert ads statistics.
        if (isset($offset)) {
            $this->importPaaFieldRuleWithOffset($input, $output);
        } else {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);

            if (file_exists($this->getContainer()->get('kernel')->getRootDir()."/../data/reports/paa_field_rule/paa_field_rules.csv")) {
                unlink($this->getContainer()->get('kernel')->getRootDir()."/../data/reports/paa_field_rule/paa_field_rules.csv");
            }

            $file          = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/reports/paa_field_rule/paa_field_rules.tmp", "a+");
            $reportColumns = array();

            $reportColumns = array("paa_form_id", "paa_form_category_path", "field_name", "field_label", "field_type", "is_required", "is_common_advert_field", "field_range", "field_min", "field_max", "other_validation_requirements");
            fputcsv($file, $reportColumns);
            fclose($file);

            $this->importPaaFieldRule($input, $output);

            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);

            $oldFileName = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/paa_field_rule/paa_field_rules.tmp";
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
    protected function importPaaFieldRule($input, $output)
    {
        $count  = $this->getPaaFieldRuleCount();

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:import:paa-filed-rule '.$commandOptions.' --verbose';
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
    protected function importPaaFieldRuleWithOffset($input, $output)
    {
        $offset                 = $input->getOption('offset');
        $paaFieldRules          = $this->getPaaFieldRuleResult($offset, $this->limit);
        $paaFieldRuleRepository = $this->entityManager->getRepository('FaAdBundle:PaaFieldRule');
        $file                   = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/reports/paa_field_rule/paa_field_rules.tmp", "a+");

        foreach ($paaFieldRules as $paaFieldRule) {
            $categoryPathArray  = $this->entityManager->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($paaFieldRule['category_id']);
            $categoryPath       = implode(" > ", $categoryPathArray);
            $paaFieldRuleDetail = $this->entityManager->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryId($paaFieldRule['category_id']);

            foreach ($paaFieldRuleDetail as $key => $objPFR) {
                $possibleValues      = '';
                $possibleValuesArray = array();
                if ($objPFR->getPaaField()->getCategoryDimensionId()) {
                    $objCategoryDimensions = $this->entityManager->getRepository('FaEntityBundle:Entity')->getEntitiesByCategoryDimensionId($objPFR->getPaaField()->getCategoryDimensionId());
                    if ($objCategoryDimensions) {
                        foreach ($objCategoryDimensions as $objCategoryDimension) {
                            $possibleValuesArray[] = $objCategoryDimension->getName();
                        }
                        $possibleValues = implode(', ', $possibleValuesArray);
                    }
                }

                $recordValues   = array();
                $recordValues[] = $paaFieldRule['category_id'];
                $recordValues[] = $categoryPath;
                $recordValues[] = $objPFR->getPaaField()->getLabel();
                $recordValues[] = $objPFR->getLabel();
                $recordValues[] = $objPFR->getPaaField()->getFieldType();
                $recordValues[] = ($objPFR->getIsRequired() ? 'Yes' : 'No');
                $recordValues[] = ($objPFR->getPaaField()->getCategory() ? 'No' : 'Yes');
                $recordValues[] = $possibleValues;
                $recordValues[] = $objPFR->getMinValue();
                $recordValues[] = $objPFR->getMaxValue();
                $recordValues[] = "";
                fputcsv($file, $recordValues);
            }
            $recordValues = array();
            fputcsv($file, $recordValues);
        }

        fclose($file);
    }

    /**
     * Get query builder for paa field rule.
     *
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getPaaFieldRuleCount($searchParams = array())
    {
        $sql = 'SELECT COUNT(id) as total_paa_field_rule FROM '.$this->mainDbName.'.paa_field_rule WHERE paa_field_id = 1';

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        $res = $stmt->fetch();

        return $res['total_paa_field_rule'];
    }

    /**
     * Get query builder for paa field rule results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getPaaFieldRuleResult($offset, $limit, $searchParam = array())
    {
        $sql = 'SELECT * FROM '.$this->mainDbName.'.paa_field_rule WHERE paa_field_id = 1 ORDER BY id DESC LIMIT '.$limit.' OFFSET '.$offset;

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        return $stmt->fetchAll();
    }
}
