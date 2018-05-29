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
 * This command is used to remove old jobs category mapping.
 *
 * php app/console fa:remove:old-job-categories
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveOldJobCategoriesCommand extends ContainerAwareCommand
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
        ->setName('fa:remove:old-job-categories')
        ->setDescription("Generate new job categories mapping")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to remove old job categories mapping.

Command:
 - php app/console fa:remove:old-job-categories
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

        $offset = $input->getOption('offset');

        $updateSql = 'UPDATE category set status = 5 where id IN (532, 533, 534);';
        $this->executeRawQuery($updateSql, $this->entityManager);

        if (isset($offset)) {
            $this->removeJobCategoriesEntityOffset($input, $output);
        } else {
            $this->removeJobCategoriesEntity($input, $output);
        }
    }

    /**
     * Update entity with offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function removeJobCategoriesEntityOffset($input, $output)
    {
        $step        = 1;
        $offset      = 0;//$input->getOption('offset');

        $deleteManager = $this->getContainer()->get('fa.deletemanager');
        $oldJobCategories = $this->getCategoryResult($offset, $step);

        foreach ($oldJobCategories as $oldJobCategory) {
            try {
                $deleteManager->delete($oldJobCategory);
            } catch (\Exception $e) {

            }
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update entity.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function removeJobCategoriesEntity($input, $output)
    {
        $count     = $this->getCategoryCount();
        $step      = 1;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total categories : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:remove:old-job-categories '.$commandOptions;
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
    protected function getCategoryQueryBuilder()
    {
        $categoryRepository  = $this->entityManager->getRepository('FaEntityBundle:Category');

        $data                  = array();
        $data['static_filters'] = CategoryRepository::ALIAS.'.status = 5';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($categoryRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCategoryResult($offset, $step)
    {
        $qb = $this->getCategoryQueryBuilder();

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        return $qb->getQuery()->getResult();
    }

    /**
     * Get query builder for category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCategoryCount()
    {
        $qb = $this->getCategoryQueryBuilder();

        $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        return $qb->getQuery()->getSingleScalarResult();
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
