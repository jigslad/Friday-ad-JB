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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send email to user to edit his draft ad.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExpireAdUpsellCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:expire-ad-upsell')
        ->setDescription("Expire ad upsell")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('upsell_type_id', null, InputOption::VALUE_OPTIONAL, 'Upsell type ids', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Expire ad upsell

Command:
 - php app/console fa:update:expire-ad-upsell --upsell_type_id="xxx"
 - php app/console fa:update:expire-ad-upsell --upsell_type_id="xxx" --ad_id="yyy"

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
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //get options passed in command
        $offset         = $input->getOption('offset');
        $adIds          = $input->getOption('ad_id');
        $upsellTypeIds  = $input->getOption('upsell_type_id');

        if ($adIds) {
            $adIds = explode(',', $adIds);
            $adIds = array_map('trim', $adIds);
        } else {
            $adIds = array();
        }

        if ($upsellTypeIds) {
            $upsellTypeIds = explode(',', $upsellTypeIds);
            $upsellTypeIds = array_map('trim', $upsellTypeIds);
        } else {
            $output->writeln('Please enter upsell_type_id.', true);
            return false;
        }

        $searchParam = array(
                           'ad_id'      => $adIds,
                           'expires_at' => time(),
                           'type'       => $upsellTypeIds
                        );

        if (isset($offset)) {
            $this->expireAdUpsellWithOffset($searchParam, $input, $output);
        } else {
            $this->expireAdUpsell($searchParam, $input, $output);
        }
    }

    /**
     * Send email for draft ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function expireAdUpsellWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getAdUpsellQueryBuilder($searchParam);
        $step          = 100;
        $offset        = 0;//$input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $adUpsells = $qb->getQuery()->getResult();
        foreach ($adUpsells as $adUpsell) {
            $adUpsell->setStatus(2);
            $entityManager->persist($adUpsell);
            $entityManager->flush($adUpsell);

            $output->writeln('Upsell has been expired for ad id: '.$adUpsell->getAdId(), true);

            // Update ad data to solr
            $ad = $entityManager->getRepository('FaAdBundle:Ad')->find($adUpsell->getAdId());
            if ($ad) {
                $this->getContainer()->get('fa_ad.entity_listener.ad')->handleSolr($ad);
                $output->writeln('Solr index has been updated for ad id: '.$adUpsell->getAdId(), true);
                $output->writeln('', true);
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send email for draft ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function expireAdUpsell($searchParam, $input, $output)
    {
        $count     = $this->getAdUpsellCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ad upsells : '.$count, true);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:expire-ad-upsell '.$commandOptions;
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
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdUpsellQueryBuilder($searchParam)
    {
        $entityManager       = $this->getContainer()->get('doctrine')->getManager();
        $adUserPackageUpsell = $entityManager->getRepository('FaAdBundle:AdUserPackageUpsell');

        return $adUserPackageUpsell->getActiveAdPackageUpsellsForExpirationQueryBuilder($searchParam['type'], $searchParam['expires_at'], $searchParam['ad_id']);
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdUpsellCount($searchParam)
    {
        $qb = $this->getAdUpsellQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
