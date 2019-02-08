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

/**
 * This command is used to update jobs category.
 *
 * php app/console fa:update:job-categories-name
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateJobCategoriesNameCommand extends ContainerAwareCommand
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
        ->setName('fa:update:job-categories-name')
        ->setDescription("Update job categories")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update job categories.

Command:
 - php app/console fa:update:job-categories-name
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
        $categoryRepository = $this->entityManager->getRepository('FaEntityBundle:Category');

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateJobCategoriesOffset($input, $output);
        } else {
            $this->updateJobCategories($input, $output);

            //update duplicate category names for jobs
            $sql = 'SELECT id,name
                    FROM category
                    Where id in ('.implode(',', $categoryRepository->getNestedChildrenIdsByCategoryId(CategoryRepository::JOBS_ID, $this->getContainer())).')
                    GROUP BY name
                    HAVING COUNT(id) > 1';
            $res = $this->executeRawQuery($sql, $this->entityManager);
            foreach ($res->fetchAll() as $duplicateCategory) {
                $updateSql = 'UPDATE category set status = 5 where id <> '.$duplicateCategory['id'].' and name = "'.$duplicateCategory['name'].'";';
                $this->executeRawQuery($updateSql, $this->entityManager);
            }

            //update duplicate category references for jobs
            $sql = 'SELECT id, name
                    FROM category
                    Where status = 5';
            $res = $this->executeRawQuery($sql, $this->entityManager);
            foreach ($res->fetchAll() as $duplicateCategory) {
                $sql = 'SELECT id
                    FROM category
                    Where status = 1 and name = "'.$duplicateCategory['name'].'";';
                $activeCatRes = $this->executeRawQuery($sql, $this->entityManager);
                $activeCatResData = $activeCatRes->fetch();
                if (isset($activeCatResData['id'])) {
                    $parentCategories = $categoryRepository->findBy(array('parent' => $duplicateCategory['id']));
                    foreach ($parentCategories as $parentCategoryObj) {
                        $parentCategoryObj->setParent($this->entityManager->getReference('FaEntityBundle:Category', $activeCatResData['id']));
                        $this->entityManager->persist($parentCategoryObj);
                        $this->entityManager->flush();
                    }
                    /*$updateSql = 'UPDATE category set parent_id = '.$activeCatResData['id'].' where parent_id = '.$duplicateCategory['id'].';';
                    $this->executeRawQuery($updateSql, $this->entityManager);*/
                }
            }
        }
    }

    /**
     * Update job categories with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateJobCategoriesOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $categoryRepository = $this->entityManager->getRepository('FaEntityBundle:Category');

        $reader = new \EasyCSV\Reader(__DIR__."/job_new_categories.csv");
        $reader->setDelimiter(';');
        $reader->setHeaderLine(0);
        $row = $reader->getRow();
        $reader->advanceTo($offset);
        $row = $reader->getRow();
        $this->getContainer()->get('gedmo.listener.translatable')->setDefaultLocale('en_GB');
        $this->getContainer()->get('gedmo.listener.translatable')->setTranslatableLocale('en_GB');
        if (isset($row['old']) && isset($row['new'])) {
            $categoryRepository->clear();
            $catObj = $categoryRepository->findOneBy(array('name' => $row['old']));
            if ($catObj) {
                $catObj->setName($row['new']);
                $slug = str_ireplace(' and ', ' ', $row['new']);
                $catObj->setSlug($slug);
                $this->entityManager->persist($catObj);
                $this->entityManager->flush();
                $this->entityManager->clear();
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
    protected function updateJobCategories($input, $output)
    {
        $reader = new \EasyCSV\Reader(__DIR__."/job_new_categories.csv");
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:job-categories-name '.$commandOptions.' ';
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
