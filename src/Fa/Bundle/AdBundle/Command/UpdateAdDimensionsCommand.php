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
use Fa\Bundle\AdBundle\lib\Migration\Pets;
use Fa\Bundle\AdBundle\lib\Migration\Horses;
use Fa\Bundle\AdBundle\lib\Migration\Property;
use Fa\Bundle\AdBundle\lib\Migration\Boats;
use Fa\Bundle\AdBundle\lib\Migration\CV;
use Fa\Bundle\AdBundle\lib\Migration\Jobs;
use Fa\Bundle\AdBundle\lib\Migration\Car;
use Fa\Bundle\AdBundle\lib\Migration\Caravan;
use Fa\Bundle\AdBundle\lib\Migration\Motorbikes;
use Fa\Bundle\AdBundle\lib\Migration\Fa\Bundle\AdBundle\lib\Migration;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateAdDimensionsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-dimension')
        ->setDescription("Update ad dimensions.")
        ->addArgument('action', InputArgument::REQUIRED, 'add or update or delete')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('category', null, InputOption::VALUE_OPTIONAL, 'Category name', null)
        ->addOption('update_type', null, InputOption::VALUE_OPTIONAL, 'Update type', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'add or update for last few days only', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:update:ad-dimension --category="Pets" add
   php app/console fa:update:ad-dimension --category="Horses" add
   php app/console fa:update:ad-dimension --category="Property" add
   php app/console fa:update:ad-dimension --category="Jobs" add
   php app/console fa:update:ad-dimension --category="Boats" add
   php app/console fa:update:ad-dimension --category="Cars" add
   php app/console fa:update:ad-dimension --category="Commercial Vehicles" add
   php app/console fa:update:ad-dimension --category="Motorhomes and Caravans" add
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
        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $ids      = $input->getOption('id');
        $offset   = $input->getOption('offset');
        $category = $input->getOption('category');
        $lastDays = $input->getOption('last_days');
        $update_type = $input->getOption('update_type');

        $categoryIds = array();
        $searchParam = array();

        if ($category) {
            $categoryObj = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => $category));
            if ($categoryObj) {
                $children = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getNodesHierarchyQuery($categoryObj)->getArrayResult();

                $categoryIds[] = $categoryObj->getId();
                foreach ($children as $child) {
                    $categoryIds[] = $child['id'];
                }
            }
        }

        if ($action == 'add') {
            if ($action == 'add') {
                if (count($categoryIds > 0)) {
                    $searchParam['category'] = array('id' => $categoryIds);
                }
                if ($lastDays) {
                    $searchParam['ad']['created_at_from_to'] =  strtotime('-'.$lastDays.' day').'|';
                }
            } else {
                //$searchParam['entity_ad_status'] = array('id' => $statusId);
                if (count($categoryIds > 0)) {
                    $searchParam['category'] = array('id' => $categoryIds);
                }
                if ($lastDays) {
                    $searchParam['ad']['updated_at_from_to'] =  strtotime('-'.$lastDays.' day').'|';
                }
            }


            if ($update_type != '') {
                $searchParam['update_type'] = $update_type;
            }

            if (isset($offset)) {
                $this->updateDimensionWithOffset($searchParam, $input, $output);
            } else {
                $this->updateDimension($searchParam, $input, $output);
            }
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimensionWithOffset($searchParam, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();
        $em  = $this->getContainer()->get('doctrine')->getManager();

        foreach ($ads as $ad) {
            if ($input->getOption('category') == "Pets") {
                $pets = new Pets($ad['old_meta_xml'], $ad['id'], $em);
                $pets->update();
            }

            if ($input->getOption('category') == "Horses") {
                $horses = new Horses($ad['old_meta_xml'], $ad['id'], $em);
                $horses->update();
            }

            if ($input->getOption('category') == "Property") {
                $property = new Property($ad['old_meta_xml'], $ad['id'], $em, $this->getContainer());
                $property->update();
            }

            if ($input->getOption('category') == 'Jobs') {
                $jobs = new Jobs($ad['old_meta_xml'], $ad['id'], $em);
                $jobs->update();
            }

            if ($input->getOption('category') == "Boats") {
                $boats = new Boats($ad['old_meta_xml'], $ad['id'], $em);
                $boats->update();
            }

            if ($input->getOption('category') == "Motorbikes") {
                $motorbikes = new Motorbikes($ad['old_meta_xml'], $ad['id'], $em);
                $motorbikes->update();
            }

            if ($input->getOption('category') == "Cars") {
                $cars = new Car($ad['old_meta_xml'], $ad['id'], $em);
                $cars->update();
            }

            if ($input->getOption('category') == "Commercial Vehicles") {
                $cv = new CV($ad['old_meta_xml'], $ad['id'], $em);
                $cv->update();
            }

            if ($input->getOption('category') == "Motorhomes" || $input->getOption('category') == "Caravans" || $input->getOption('category') == "Static Caravans") {
                $cv = new Caravan($ad['old_meta_xml'], $ad['id'], $em, $input->getOption('category'));
                $cv->update();

            }
        }

        $em->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update dimension.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimension($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 1000;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:ad-dimension -v '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getAdQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->createQueryBuilder(AdRepository::ALIAS);
        $qb->select(AdRepository::ALIAS.'.old_meta_xml, '.AdRepository::ALIAS.'.id');
        $qb->andWhere(AdRepository::ALIAS.'.old_meta_xml IS NOT NULL');
        $qb->andWhere(AdRepository::ALIAS.'.is_blocked_ad = 0');
        $qb->andWhere(AdRepository::ALIAS.'.category IN (:category_id)');
        $qb->setParameter('category_id', $searchParam['category']['id']);
        $qb->andWhere(AdRepository::ALIAS.'.update_type != :update_type_not');
        $qb->setParameter('update_type_not', 'non-paa');

        if (isset($searchParam['update_type']) && $searchParam['update_type']) {
            $qb->andWhere(AdRepository::ALIAS.'.update_type = :update_type');
            $qb->setParameter('update_type', $searchParam['update_type']);
        }

        return $qb;
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount($searchParam)
    {
        $qb = $this->getAdQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
