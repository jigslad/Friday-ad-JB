<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\lib\Migration\NonPaa;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Fa\Bundle\DotMailerBundle\Entity\DotmailerInfo;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateDotmailerDataCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:dotmailer-data')
        ->setDescription("Update Dot mailer data for migration")
        ->addArgument('action', InputArgument::REQUIRED, 'add')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'add or update for last few days only', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console update:dotmailer-data add
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
                $this->updateDimensionWithOffset($input, $output);
            } else {
                $this->updateDimension($input, $output);
            }
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimensionWithOffset($input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getDotmailerQueryBuilder();
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $newslatters = $qb->getQuery()->getResult();

        foreach ($newslatters as $newslatter) {
            $paa_entries = unserialize($newslatter->getPaaEntries());

            if ($paa_entries && count($paa_entries) > 0) {
                $this->insertPaaDotmailer($paa_entries, $newslatter);
            }

            $enq_entries = unserialize($newslatter->getEnqueriesEntries());


            if ($enq_entries && count($enq_entries) > 0) {
                $this->insertEnqDotmailer($enq_entries, $newslatter);
            }

            if (isset($enq_entries['AdPostCode']) && $enq_entries['AdPostCode'] != '') {
                $newslatter->setEnqPostcode($enq_entries['AdPostCode']);
            }

            $guid = CommonManager::generateGuid($newslatter->getEmail());
            $newslatter->setGuid($guid);


            $printEditionId = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($newslatter->getTownId(), $this->getContainer(), 'id');
            if ($printEditionId) {
                $newslatter->setPrintEditionId($printEditionId);
            }

            $user = $this->em->getRepository('FaUserBundle:User')->getUserByEmail($newslatter->getEmail());

            if ($user) {
                $newslatter->setFirstName($user->getFirstName());
                $newslatter->setLastName($user->getLastName());
                $newslatter->setBusinessName($user->getBusinessName());

                if ($user->getRole()) {
                    $newslatter->setRoleId($user->getRole()->getId());
                }
            }

            $this->em->persist($newslatter);
            echo ".";
        }

        $this->em->flush();
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * insert paa data
     *
     * @param array $paa_entries
     * @param object $newslatter
     *
     * @return void
     */
    private function insertPaaDotmailer($paa_entries, $newslatter)
    {
        foreach ($paa_entries as $entry) {
            if ($entry['ClassName'] != '') {
                $Mappedcategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->findOneBy(array('name' => $entry['ClassName']));

                if ($Mappedcategory) {
                    $dotmailer_info = new DotmailerInfo();
                    $dotmailer_info->setDotmailer($newslatter);
                    $dotmailer_info->setPaaCreatedAt(strtotime($entry['PublishDate']));
                    $dotmailer_info->setPaaCategoryId($Mappedcategory->getNewId());
                    $dotmailer_info->setSegment(DotmailerRepository::TOUCH_POINT_PAA);
                    $this->em->persist($dotmailer_info);
                } else {
                    echo 'Not matched category:'.$entry['ClassName']."\n";
                }
            }
        }
    }

    /**
     * insert data for enquery dotmailer
     *
     * @param array $enq_entries
     * @param object $newslatter
     *
     * @return void
     */
    private function insertEnqDotmailer($enq_entries, $newslatter)
    {
        foreach ($enq_entries as $entry) {
            if (isset($entry['ClassID']) && $entry['ClassID'] != '') {
                $newId = $this->getCatIdMapping($entry['ClassID']);

                if (!$newId) {
                    $Mappedcategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->findOneBy(array('id' => $entry['ClassID']));

                    if ($Mappedcategory) {
                        $newId = $Mappedcategory->getNewId();
                    }
                }

                if ($newId) {
                    $dotmailer_info = new DotmailerInfo();
                    $dotmailer_info->setDotmailer($newslatter);
                    $dotmailer_info->setEnquiryCreatedAt(strtotime($entry['SendingDateTime']));
                    $dotmailer_info->setEnquiryCategoryId($newId);
                    $dotmailer_info->setSegment(DotmailerRepository::TOUCH_POINT_ENQUIRY);
                    $this->em->persist($dotmailer_info);
                } else {
                    echo 'Not matched category:'.$entry['ClassID']."\n";
                }
            }
        }
    }

    //:Misc Businesses For Sale
    private function getCatIdMapping($classId)
    {
        $mapping = array();
        $mapping['5016'] = CategoryRepository::CARS_ID;
        $mapping['6506'] = 1571;
        $mapping['6521'] = 2248;
        $mapping['1180'] = 1609;
        $mapping['6513'] = 1045;
        $mapping['5055'] = CategoryRepository::COMMERCIALVEHICLES_ID;
        $mapping['6508'] = 1868;
        $mapping['6504'] = 1362;
        $mapping['6511'] = 2290;
        $mapping['11066'] = 1430;
        $mapping['1177'] = 1256;
        $mapping['6519'] = 1998;
        $mapping['248'] = 1120;
        $mapping['6507'] = 1609;
        $mapping['6505'] = 1415;
        $mapping['1190'] = 2772;
        $mapping['6510'] = 2117;
        $mapping['1188'] = 2619;
        $mapping['6515'] = 1256;
        $mapping['6514'] = 1219;
        $mapping['6501'] = 904;
        $mapping['6517'] = 1749;
        $mapping['6503'] = 1327;
        $mapping['6514'] = 1219;
        $mapping['1193'] = 2977;
        $mapping['1030'] = 1010;
        $mapping['6502'] = 1107;
        $mapping['6529'] = 1166;

        if (isset($mapping[$classId])) {
            return $mapping[$classId];
        }

        return null;
    }

    /**
     * Update dimension.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimension($input, $output)
    {
        echo $count     = $this->getDotmailerCount();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:dotmailer-data '.$commandOptions.' '.$input->getArgument('action');
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
     * get dotmailer query builder
     *
     * @return Query_Builder
     */
    protected function getDotmailerQueryBuilder()
    {
        $dotmailerRepository  = $this->em->getRepository('FaDotMailerBundle:Dotmailer');
        $qb = $dotmailerRepository->createQueryBuilder(DotmailerRepository::ALIAS);
        return $qb;
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerCount()
    {
        $qb = $this->getDotmailerQueryBuilder();
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
