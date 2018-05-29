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
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ApplyOldAdFreePackageCommand extends ContainerAwareCommand
{
    const DATE = '2015-09-08';

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:apply-old-ad-free-packages')
        ->setDescription("Apply free print packages to old ads")
        ->addArgument('action', InputArgument::REQUIRED, 'add or update or delete')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'add or update for last few days only', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console update:non-paa-ads add
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
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $offset   = $input->getOption('offset');

        $searchParam = array();

        if ($action == 'add') {
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
        $step        = 1000;
        $offset      = $input->getOption('offset');
        $ads         = $this->getAdQueryBuilder($searchParam, $offset, $step);

        $em  = $this->getContainer()->get('doctrine')->getManager();

        $ids = array();

        foreach ($ads as $ad) {
            $ad = $this->em->getRepository('FaAdBundle:Ad')->find($ad['id']);
            if ($ad) {
                $printAd = $this->em->getRepository('FaAdBundle:AdPrint')->findOneBy(array('ad' => $ad));
                if (!$printAd) {
                    $this->em->getRepository('FaAdBundle:AdPrint')->addPrintAd(1, 1, $ad, false, false, true, strtotime(self::DATE));
                    echo 'Assign for print -> '.$ad->getId()."\n";
                } else {
                    echo 'Already assiend for print -> '.$ad->getId()."\n";
                }
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:apply-old-ad-free-packages '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getAdQueryBuilder($searchParam, $offset, $step)
    {
        $query = 'SELECT a.id FROM ad a left join ad_location al on a.id = al.ad_id where a.status_id = 25 AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13) ORDER BY a.id ASC LIMIT '.$offset.', '.$step;
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $ads = $stmt->fetchAll();
        return $ads;

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
        $query = 'SELECT count(a.id) as count FROM ad a left join ad_location al on a.id = al.ad_id where a.status_id = 25 AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)';
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchAll();
        return $count[0]['count'];
    }
}
