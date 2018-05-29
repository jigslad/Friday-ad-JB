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
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to replace EG print edition by Uckfield print edition.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ReplaceEGByUckfieldPrintCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:replace:eg-by-uckfield-print')
        ->setDescription("Replace BH print edition tby crwaley print edition.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Replace EG print edition tby Uckfield print edition.

Command:
 - php app/console fa:replace:eg-by-uckfield-print
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

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->replaceEgByUckfieldWithOffset($input, $output);
        } else {
            $this->replaceEgByUckfield($input, $output);
        }
    }

    /**
     * Update eg by Uckfield print edition.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function replaceEgByUckfieldWithOffset($input, $output)
    {
        $qb          = $this->getAdPrintQueryBuilder();
        $step        = 100;
        $offset      = 0;//$input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $printAds           = $qb->getQuery()->getResult();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $deleteManager = $this->getContainer()->get('fa.deletemanager');
        foreach ($printAds as $printAd) {
            $uckfieldInsertObjs = $this->getAdPrintByEditionAndInsertDate($printAd->getAd()->getId(), $printAd->getInsertDate());
            if ($uckfieldInsertObjs) {
                foreach ($uckfieldInsertObjs as $uckfieldInsertObj) {
                    if ($uckfieldInsertObj) {
                        $output->writeln('Uckfield print insert exist so deleted EG entry: '.$printAd->getId(), true);
                        $deleteManager->delete($printAd);
                    }
                }
            } else {
                $printAd->setPrintEdition($entityManager->getReference('FaAdBundle:PrintEdition', 5));
                $entityManager->persist($printAd);
                $entityManager->flush($printAd);
                $output->writeln('EG entry replaced by Uckfield: '.$printAd->getId(), true);
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update eg by Uckfield print edition.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function replaceEgByUckfield($input, $output)
    {
        $count     = $this->getAdPrintCount();
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:replace:eg-by-uckfield-print '.$commandOptions;
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
    protected function getAdPrintQueryBuilder()
    {
        $startDate = CommonManager::getTimeStampFromStartDate('2016-04-30');
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adPrintRepository  = $entityManager->getRepository('FaAdBundle:AdPrint');

        $query = $adPrintRepository->getBaseQueryBuilder()
            ->andWhere(AdPrintRepository::ALIAS.'.insert_date >= '.$startDate)
            ->andWhere(AdPrintRepository::ALIAS.'.print_edition = 6');

        return $query;
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdPrintCount()
    {
        $qb = $this->getAdPrintQueryBuilder();
        $qb->select('COUNT('.AdPrintRepository::ALIAS.'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdPrintByEditionAndInsertDate($adId, $printInsertDate)
    {
        $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', $printInsertDate));
        $endDate = CommonManager::getTimeStampFromEndDate(date('Y-m-d', $printInsertDate));
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adPrintRepository  = $entityManager->getRepository('FaAdBundle:AdPrint');

        $query = $adPrintRepository->getBaseQueryBuilder()
        ->andWhere(AdPrintRepository::ALIAS.'.insert_date >= '.$startDate)
        ->andWhere(AdPrintRepository::ALIAS.'.insert_date <= '.$endDate)
        ->andWhere(AdPrintRepository::ALIAS.'.print_edition = 5')
        ->andWhere(AdPrintRepository::ALIAS.'.ad = '.$adId);

        return $query->getQuery()->getResult();
    }
}
