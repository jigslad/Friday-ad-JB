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
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\AdBundle\Entity\AdMain;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class InsertNonPaaAdsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:insert:non-paa')
        ->setDescription("Insert non paa ads")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:insert:non-paa
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
        $offset   = $input->getOption('offset');

        if (isset($offset)) {
            $this->insertNonPaaOffset($input, $output);
        } else {
            $this->insertNonPaa($input, $output);
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function insertNonPaaOffset($input, $output)
    {
        $offset      = $input->getOption('offset');

        $reader = new \EasyCSV\Reader(__DIR__."/missing_ads.csv");
        $reader->setDelimiter(',');
        $batchSize = 1000;
        $row = 0;
        $ad_id = array();
        $category = array();
        $row = $reader->getRow();

        if ($offset > 0) {
            $reader->advanceTo($offset-1);
        } else {
            $reader->advanceTo(1);
        }

        while (($row = $reader->getRow()) && $reader->getLineNumber() != $offset + $batchSize) {
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->findOneBy(array('trans_id' => $row['AdRef'], 'update_type' => 'missing_non-paa'));

            if (!$adMain) {
                $adMain = new AdMain();
                $adMain->setTransId($row['AdRef']);
                $adMain->setUpdateType('missing_non-paa');
                $this->em->persist($adMain);
                $this->em->flush();
            }

            $ad = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('ad_ref' => $row['AdRef'], 'update_type' => 'missing_non-paa'));

            if (!$ad) {
                $ad = new Ad();
            }

            $is_trade = $row['PrivateOrTrade'] ==  'Trade' ? 1 :  0;
            $ad->setAdRef($row['AdRef']);
            $metadata = $this->em->getClassMetaData('Fa\Bundle\AdBundle\Entity\Ad');
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            $ad->setId($adMain->getId());
            $ad->setAdMain($adMain);
            $ad->setTitle($row['Title']);
            $ad->setDescription($row['AdSummary']);
            $ad->setIsTradeAd($is_trade);
            $ad->setUpdateType('missing_non-paa');
            $cat = $this->em->getRepository('FaEntityBundle:MappingCategory')->findOneBy(array('id' => $row['Classification']));
            $ad->setCategory($this->em->getReference('FaEntityBundle:Category', $cat->getNewId()));
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', 25));
            $ad->setUpdatedAt(strtotime($row['TimeModified']));
            $ad->setCreatedAt(strtotime($row['AdAgeDate']));
            $ad->setPublishedAt(strtotime($row['AdAgeDate']));
            $ad->setPhone($row['PhoneNo']);
            $this->em->persist($ad);
            $this->em->flush();
            echo 'Added '. $ad->getId();

            $locations = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($row['PostCode']);

            $adLocation = $this->em->getRepository('FaAdBundle:AdLocation')->findOneBy(array('ad' => $ad));

            if (!$adLocation) {
                $adLocation = new AdLocation();
                $adLocation->setAd($ad);
            }

            if (isset($locations['town_id']) && $locations['town_id']) {
                $adLocation->setLocationTown($this->em->getReference('FaEntityBundle:Location', $locations['town_id']));
            }

            if (isset($locations['locality_id']) && $locations['locality_id']) {
                $adLocation->setLocality($this->em->getReference('FaEntityBundle:Locality', $locations['locality_id']));
            }

            if (isset($locations['county_id']) && $locations['county_id']) {
                $adLocation->setLocationDomicile($this->em->getReference('FaEntityBundle:Location', $locations['county_id']));
            }
            $adLocation->setLocationCountry($this->em->getReference('FaEntityBundle:Location', 2));

            if (isset($locations['postcode']) && $locations['postcode']) {
                $adLocation->setPostcode($locations['postcode']);
            }

            if (isset($locations['latitude']) && $locations['latitude']) {
                $adLocation->setLatitude($locations['latitude']);
            }
            if (isset($locations['longitude']) && $locations['longitude']) {
                $adLocation->setLongitude($locations['longitude']);
            }
            $this->em->persist($adLocation);
            $this->em->flush();

            $printEditions = $this->em->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionCodeArray();

            if (isset($row['PubInserts']) && $row['PubInserts'] != '') {
                $print_entries = explode(',', $row['PubInserts']);
                foreach ($print_entries as $print_entry) {
                    $print_entries = explode('|', $print_entry);

                    if (isset($print_entries[0]) && isset($print_entries[1]) && isset($printEditions[$print_entries[0]])) {
                        $printAd = $this->em->getRepository('FaAdBundle:AdPrint')->findOneBy(array('ad' => $ad));
                        $p = $this->em->getRepository('FaAdBundle:PrintEdition')->find($printEditions[$print_entries[0]]);
                        if (!$printAd) {
                            $adPrint = new AdPrint();
                            $adPrint->setAd($ad);
                            $adPrint->setPrintEdition($p);
                            $adPrint->setDuration('1 weeks');
                            $adPrint->setSequence(1);
                            $adPrint->setIsPaid(1);
                            $adPrint->setPrintQueue(0);
                            $adPrint->setAdModerateStatus(2);
                            $adPrint->setInsertDate(strtotime($print_entries[1]));
                            $this->em->persist($adPrint);
                        }
                    }
                }
            }
            $this->em->flush();
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    /**
     * Update dimension.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function insertNonPaa($input, $output)
    {
        $reader = new \EasyCSV\Reader(__DIR__."/missing_ads.csv");
        $reader->setDelimiter(',');
        $count     = $reader->getLastLineNumber();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:insert:non-paa '.$commandOptions.' ';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
