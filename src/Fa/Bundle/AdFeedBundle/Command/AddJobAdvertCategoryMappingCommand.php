<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedMapping;

/**
 * This command is used to add new jobs advert category mapping.
 *
 * php app/console fa:add:job-advert-category-mapping
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AddJobAdvertCategoryMappingCommand extends ContainerAwareCommand
{
    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:add:new-job-advert-category-mapping')
        ->setDescription("Add new job advert categories mapping")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('file_name', null, InputOption::VALUE_REQUIRED, 'File name', null)
        ->addOption('ref_site_id', null, InputOption::VALUE_REQUIRED, 'Reference site id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to add new job advert categories mapping.

Command:
 - php app/console fa:add:new-job-advert-category-mapping --ref_site_id="9" --file_name="simply_sales_jobs_category_mapping.csv"
EOF
        );;
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->addJobCategoriesOffset($input, $output);
        } else {
            $this->addJobCategories($input, $output);
        }
    }

    /**
     * Update job categories with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function addJobCategoriesOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $fileName = $input->getOption('file_name');
        $refSiteId = $input->getOption('ref_site_id');
        $categoryRepository = $this->entityManager->getRepository('FaEntityBundle:Category');
        $adFeedMappingRepository = $this->entityManager->getRepository('FaAdFeedBundle:AdFeedMapping');

        $step = 10;
        $reader = new \EasyCSV\Reader(__DIR__."/".$fileName);
        $reader->setDelimiter(',');
        $reader->setHeaderLine(0);
        $row = $reader->getRow();
        if ($offset > 0)
            $reader->advanceTo($offset-1);
        else
            $reader->advanceTo(1);
        while (($row = $reader->getRow()) && $reader->getLineNumber() != $offset + $step) {
            if (isset($row['feed_category']) && isset($row['fa_parent_category']) && isset($row['fa_leaf_category']) && isset($row['fa_leaf_category_lvl'])) {
                $adFeedMapping = $adFeedMappingRepository->findOneBy(array('text' => $row['feed_category'], 'ref_site_id' => $refSiteId));
                if (!$adFeedMapping) {
                    $parentCatObj = $categoryRepository->findOneBy(array('name' => $row['fa_parent_category'], 'lvl' => ($row['fa_leaf_category_lvl']-1)));
                    if ($parentCatObj) {
                        $childCatObj = $categoryRepository->findOneBy(array('name' => $row['fa_leaf_category'], 'parent' => $parentCatObj->getId(), 'lvl' => $row['fa_leaf_category_lvl']));
                        if ($parentCatObj && $childCatObj) {
                            $adFeedMappingObj = new AdFeedMapping();
                            $adFeedMappingObj->setText(trim($row['feed_category']));
                            $adFeedMappingObj->setTarget($childCatObj->getFullSlug());
                            $adFeedMappingObj->setRefSiteId($refSiteId);
                            $this->entityManager->persist($adFeedMappingObj);
                            echo "Mapping inserted :".$row['fa_leaf_category']."\n";
                        } elseif(!$childCatObj) {
                            echo "Mapping not inserted, Leaf Category not found:".$row['fa_leaf_category']."\n";
                        }
                    } elseif(!$parentCatObj) {
                        echo "Mapping not inserted, Parent Category not found:".$row['fa_parent_category']."\n";
                    }
                }
            }
        }
        $this->entityManager->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    /**
     * Update job categories.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function addJobCategories($input, $output)
    {
        $fileName = $input->getOption('file_name');
        $reader = new \EasyCSV\Reader(__DIR__."/".$fileName);
        $reader->setDelimiter(',');
        $count     = $reader->getLastLineNumber();
        $step      = 10;
        $stat_time = time();
        $returnVar = null;

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:add:new-job-advert-category-mapping '.$commandOptions.' ';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
