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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;

/**
 * This command is used to update guid of user.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateDotmailerGuidCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:dotmailer-user-guid')
        ->setDescription("Update dotmailer guid")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update dotmailer guid.

Command:
 - php app/console fa:update:dotmailer-user-guid
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

        $searchParam = array();

        if (isset($offset)) {
            $this->updateDotmailerGuidWithOffset($searchParam, $input, $output);
        } else {
            $this->updateDotmailerGuid($searchParam, $input, $output);
        }
    }

    /**
     * Update dotmailer guid with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDotmailerGuidWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getDotmailerQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $dotmailerUsers         = $qb->getQuery()->getResult();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        foreach ($dotmailerUsers as $dotmailerUser) {
            $dotmailerUser->setGuid(CommonManager::generateGuid($dotmailerUser->getEmail()));
            $entityManager->persist($dotmailerUser);
            echo '.';
        }
        $entityManager->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update guid for dotmailer.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDotmailerGuid($searchParam, $input, $output)
    {
        $count     = $this->getDotmailerCount($searchParam);
        $step      = 1000;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:dotmailer-user-guid '.$commandOptions;
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
     * Get query builder for user.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $dotmailerRepository  = $entityManager->getRepository('FaDotMailerBundle:Dotmailer');

        $data                   = array();
        $data['query_filters']  = $searchParam;
        $data['query_sorter']   = array('dotmailer' => array('id' => 'asc'));
        $data['static_filters'] = DotmailerRepository::ALIAS.'.guid IS NOT NULL';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($dotmailerRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for users.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerCount($searchParam)
    {
        $qb = $this->getDotmailerQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
