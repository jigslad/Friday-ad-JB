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
use Fa\Bundle\DotMailerBundle\Repository\DotmailerResponseRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;

/**
 * This command is used to update dotmailer data in bulk.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerBulkDeleteCommand extends ContainerAwareCommand
{
    private $response;

    private $httpcode;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:bulk-delete')
        ->setDescription("Bulk delete.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at 6am

Actions:
- Remove old data from dotmailer.

Command:
 - php app/console fa:dotmailer:bulk-delete
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

        $searchParam['dotmailer'] = array('dotmailer_newsletter_unsubscribe' => 1);

        if (isset($offset)) {
            $this->dotmailerBulkDeleteWithOffset($searchParam, $input, $output);
        } else {
            $this->dotmailerBulkDelete($searchParam, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerBulkDeleteWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getDotmailerQueryBuilder($searchParam);
        $step          = 1000;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $dotmailers = $qb->getQuery()->getResult();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        if (count($dotmailers) > 0) {
            foreach ($dotmailers as $dotmailer) {
                $email = $dotmailer->getEmail();

                try {
                    $getConact = $this->getContainer()->get('fa.dotmailer.getcontactbyemail.resource');
                    $getConact->setDataToSubmit(array(0 => $email));
                    $response = $getConact->getContact();
                    if ($response) {
                        $response = json_decode($response);
                        if ($response->id) {
                            $deleteConact = $this->getContainer()->get('fa.dotmailer.deletecontact.resource');
                            $deleteConact->setDataToSubmit(array(0 => $response->id));
                            $deleted = $deleteConact->delete();
                            if (!$deleted) {
                                CommonManager::sendErrorMail($container, 'Info: Delete contact from dotmailer => '.$email, 'Dotmailer', 'Dotmailer');
                            } else {
                                $output->writeln('Contact deleted successfully for email: '.$email, true);
                            }
                        }
                    } else {
                        $output->writeln('Contact not found in dotmailer for email: '.$email, true);
                    }
                } catch (\Exception $e) {
                    CommonManager::sendErrorMail($container, 'Error: Delete contact from dotmailer => '.$email, $e->getMessage(), $e->getTraceAsString());
                }
            }

            sleep(2);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerBulkDelete($searchParam, $input, $output)
    {
        $count     = $this->getDotmailerCount($searchParam);
        $step      = 1000;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:dotmailer:bulk-delete '.$commandOptions;
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
    protected function getDotmailerQueryBuilder($searchParam)
    {
        $entityManager       = $this->getContainer()->get('doctrine')->getManager();
        $dotMailerRepository = $entityManager->getRepository('FaDotMailerBundle:Dotmailer');

        $data                  = array();
        $data['query_filters'] = $searchParam;

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($dotMailerRepository, $data);

        $qb = $searchManager->getQueryBuilder();

        return $qb;
    }

    /**
     * Get query builder for dotmailer.
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
