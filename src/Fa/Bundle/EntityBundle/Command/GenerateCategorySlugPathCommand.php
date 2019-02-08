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
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateCategorySlugPathCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:category-slug-path')
        ->setDescription("Generate cache for category used in category auto-suggest")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('category_id', null, InputOption::VALUE_OPTIONAL, 'Category id', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Generate cache for autosuggest category

Command:
 - php app/console fa:generate:category-slug-path
 - php app/console fa:generate:category-slug-path --category_id=XXX

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
        $offset     = $input->getOption('offset');
        $categoryId = $input->getOption('category_id', null);

        if (isset($offset)) {
            $this->generateCategoryCacheWithOffset($input, $output, $categoryId);
        } else {
            $this->generateCategoryCache($input, $output, $categoryId);
        }
    }

    /**
     * Generate cache for category with given offset.
     *
     * @param object  $input      Input object.
     * @param string  $output     Output object.
     * @param integer $categoryId Category id.
     *
     */
    protected function generateCategoryCacheWithOffset($input, $output, $categoryId = null)
    {
        $qb     = $this->getCategoryQueryBuilder($categoryId);
        $step   = 1000;
        $offset = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $categories = $qb->getQuery()->getResult();

        foreach ($categories as $category) {
            $flag = true;
            $parentSlug = array();

            //echo $category->getSlug();

            if ($category->getLvl() >= 1) {
                $cat = $category;

                while ($flag) {
                    $parentSlug[] = $cat->getSlug();

                    if (is_object($cat) && $cat->getLvl() == 1) {
                        $flag = false;
                    } else {
                        $cat = $cat->getParent();
                    }
                }
            }

            if (count($parentSlug) == 5) {
                if ($parentSlug[3] == 'fashion' && $parentSlug[2] == 'shoes') {
                    $parentSlug[0] = $parentSlug[1].'-'.$parentSlug[0];
                    unset($parentSlug[1]);
                } else {
                    unset($parentSlug[1]);
                }
            }

            if (count($parentSlug) == 6) {
                unset($parentSlug[1]);
                unset($parentSlug[2]);
            }


            $fullSlug =  implode("/", array_reverse($parentSlug));
            $category->setFullSlug($fullSlug);

            $this->getContainer()->get('doctrine')->getManager()->persist($category);
        }

        $this->getContainer()->get('doctrine')->getManager()->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Generate cache for category.
     *
     * @param object  $input      Input object.
     * @param object  $output     Output object.
     * @param integer $categoryId Category id.
     *
     */
    protected function generateCategoryCache($input, $output, $categoryId = null)
    {
        $count     = $this->getCategoryCount($categoryId);
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total categories: '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:generate:category-slug-path '.$commandOptions;
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
     * @param integer $categoryId Category id.
     *
     * @return Doctrine_Query object
     */
    protected function getCategoryQueryBuilder($categoryId = null)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $qb = $entityManager->getRepository('FaEntityBundle:Category')->getBaseQueryBuilder();

        if ($categoryId) {
            $qb->andWhere(CategoryRepository::ALIAS.'.id = :category_id')
               ->setParameter('category_id', $categoryId);
        }

        return $qb;
    }

    /**
     * Get count for categories.
     *
     * @param integer $categoryId Category id.
     *
     * @return Doctrine_Query object
     */
    protected function getCategoryCount($categoryId = null)
    {
        $qb = $this->getCategoryQueryBuilder($categoryId);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
