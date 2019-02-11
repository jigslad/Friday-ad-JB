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
 * This command is used to generate cache for categories used in auto suggest.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class GenerateCategoryCacheAutoSuggestCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:category-cache-for-autosuggest')
        ->setDescription("Generate cache for category used in category auto-suggest")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Generate cache for autosuggest category

Command:
 - php app/console fa:generate:category-cache-for-autosuggest

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
        $searchParam = array();

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->generateCategoryCacheWithOffset($input, $output);
        } else {
            $this->generateCategoryCache($input, $output);
        }
    }

    /**
     * Generate cache for category with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function generateCategoryCacheWithOffset($input, $output)
    {
        $qb     = $this->getCategoryQueryBuilder();
        $step   = 500;
        $offset = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $categories = $qb->getQuery()->getResult();

        $entityManager      = $this->getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository('FaEntityBundle:Category');
        foreach ($categories as $category) {
            $showTwoParentName    = false;
            $secondRootCategoryId = $categoryRepository->getSecondRootCategoryId($category->getId(), $this->getContainer());
            if ($secondRootCategoryId && in_array($secondRootCategoryId, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                $showTwoParentName = true;
            }

            $categoryRepository->getNestedChildrenArrayByParentId($category->getId(), $this->getContainer(), true, $showTwoParentName);
            echo '.';
        }

        $output->writeln('', true);
        $output->writeln('Cache generated for auto suggest', true);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Generate cache for category.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function generateCategoryCache($input, $output)
    {
        $count     = $this->getCategoryCount();
        $step      = 500;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:generate:category-cache-for-autosuggest '.$commandOptions;
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
     * Get query builder for category.
     *
     * @return Doctrine_Query object
     */
    protected function getCategoryQueryBuilder()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        return $entityManager->getRepository('FaEntityBundle:Category')->getBaseQueryBuilder();
    }

    /**
     * Get count for categories.
     *
     * @return Doctrine_Query object
     */
    protected function getCategoryCount()
    {
        $qb = $this->getCategoryQueryBuilder();
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
