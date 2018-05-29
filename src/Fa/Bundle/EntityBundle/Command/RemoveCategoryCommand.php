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
class RemoveCategoryCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove-categories')
        ->setDescription("Remove specified categories")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Actions:
- Remove specified categories

Command:
 - php app/console fa:remove-categories

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
        $qb     = $this->getCategoryQueryBuilder();
        $qb->andWhere(CategoryRepository::ALIAS.'.id IN (:ids)');
        $qb->setParameter('ids', array(476, 696, 697, 715, 716, 843, 844, 885, 886, 973, 974, 1008, 1009, 1187, 1188, 1308, 1309, 1313, 1314, 1318, 1319, 1442, 1443, 1464, 1465, 1546, 1547, 1647, 1648, 1667, 1668, 1731, 1732, 1733, 1734, 1836, 1837, 1838, 1839, 1840, 1841, 1842, 1843, 1844, 1845, 1846, 1847, 1848, 1849, 1850, 1851, 1935, 1936, 1937, 1938, 1940, 1941, 1942, 1943, 2029, 2030, 2082, 2083, 2136, 2244, 2245, 2288, 2289, 2363, 2364, 2365, 2387, 2388, 2416, 2417, 2444, 2445, 2486, 2487, 2488, 2489, 2490, 2491, 2542, 2543, 2592, 2593, 2594, 2595, 2617, 2618, 2642, 2643, 2802, 2803, 2980, 2981, 2982, 3004, 3005, 3068, 3069, 3072, 3073, 3070, 3071, 3074, 3075, 3161, 3162, 3146, 3147, 3148, 3149, 3154, 3155, 3217, 3218, 3388, 3389, 927, 1557, 2931, 2572, 2666, 2677, 2641, 2916, 3995, 3784, 3996));
        $categories = $qb->getQuery()->getResult();
        foreach ($categories as $category) {
            echo "removed id".$category->getId()."\n";
            $this->em->remove($category);
            $this->em->flush();
        }
    }

    /**
     * Get query builder for category.
     *
     * @return Doctrine_Query object
     */
    protected function getCategoryQueryBuilder()
    {
        return $this->em->getRepository('FaEntityBundle:Category')->getBaseQueryBuilder();
    }
}
