<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Entity\Category;

/**
 * This command is used to add new jobs category.
 *
 * php app/console fa:add:new-job-categories-name
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AddNewJobCategoriesCommand extends ContainerAwareCommand
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
        ->setName('fa:add:new-job-categories-name')
        ->setDescription("Add new job categories")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to add new job categories.

Command:
 - php app/console fa:add:new-job-categories-name
EOF
        );
        ;
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
        $categoryRepository = $this->entityManager->getRepository('FaEntityBundle:Category');

        $reader = new \EasyCSV\Reader(__DIR__."/job_add_new_categories.csv");
        $reader->setDelimiter(';');
        $reader->setHeaderLine(0);
        $row = $reader->getRow();
        $reader->advanceTo($offset);
        $row = $reader->getRow();
        $this->getContainer()->get('gedmo.listener.translatable')->setDefaultLocale('en_GB');
        $this->getContainer()->get('gedmo.listener.translatable')->setTranslatableLocale('en_GB');
        if (isset($row['parent']) && isset($row['child']) && isset($row['parent_level']) && isset($row['child_level'])) {
            $parentCatObj = $categoryRepository->findOneBy(array('name' => $row['parent'], 'lvl' => $row['parent_level'], 'status' => 1));
            if ($parentCatObj) {
                $childCatObj = $categoryRepository->findOneBy(array('name' => $row['child'], 'parent' => $parentCatObj->getId(), 'lvl' => $row['child_level'], 'status' => 1));
                if ($parentCatObj && !$childCatObj) {
                    $catObj = new Category();
                    $catObj->setName($row['child']);
                    $slug = str_ireplace(' and ', ' ', $row['child']);
                    $catObj->setSlug($slug);
                    $catObj->setParent($parentCatObj);
                    $catObj->setStatus(1);
                    $this->entityManager->persist($catObj);
                    $this->entityManager->flush();
                } elseif ($childCatObj && $parentCatObj && $childCatObj->getParent()->getId() != $parentCatObj->getId()) {
                    $childCatObj->setParent($parentCatObj);
                    $this->entityManager->persist($childCatObj);
                    $this->entityManager->flush();
                } elseif (!$childCatObj) {
                    echo "Category not inserted:".$row['child']."\n";
                }
            }
        }

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
        $reader = new \EasyCSV\Reader(__DIR__."/job_add_new_categories.csv");
        $reader->setDelimiter(';');
        $count     = $reader->getLastLineNumber();
        $step      = 1;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:add:new-job-categories-name '.$commandOptions.' ';
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
}
