<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Date;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ReportBundle\Entity\AutomatedEmailReportDaily;

/**
 * This command is used for update sent email counter.
 * php app/console fa:update:automated-email-sent-counter
 * php app/console fa:update:automated-email-sent-counter --date=2015-01-01
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAutomatedEmailSentCounterCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:automated-email-sent-counter')
        ->setDescription("Update automated email sent counter from cache to database.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date in YYYY-MM-DD format', '')
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update automated email sent counter from cache to database.
- Update automated email view counter of previous day if date is not passed.

Command:
 - php app/console fa:update:automated-email-sent-counter
 - php app/console fa:update:automated-email-sent-counter --date="YYYY-mm-dd"
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

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateAutomatedEmailSentCounterWithOffset($input, $output);
        } else {
            $this->updateAutomatedEmailSentCounter($input, $output);
        }
    }

    /**
     * Update automated email view counter with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAutomatedEmailSentCounterWithOffset($input, $output)
    {
        $historyEntityManager = $this->getContainer()->get('doctrine')->getManager('history');
        $step          = 10;
        $offset        = 0;

        $date = $input->getOption('date');

        if ($date) {
            $date = strtotime($date);
        } else {
            // Previous day
            $date = strtotime(date('Y-m-d')) - 86400;
        }

        $automatedEmailCounterKeys = CommonManager::getCacheCounterUsingZIncr($this->getContainer(), 'automated_email_counter_'.$date, $offset, $step);

        if (count($automatedEmailCounterKeys)) {
            foreach ($automatedEmailCounterKeys as $emailIdentifier => $automatedEmailCounter) {
                $automatedEmailCounterObj = $historyEntityManager->getRepository('FaReportBundle:AutomatedEmailReportDaily')->findOneBy(array('identifier' => $emailIdentifier, 'created_at' => $date));
                if (!$automatedEmailCounterObj) {
                    $automatedEmailCounterObj = new AutomatedEmailReportDaily();
                    $automatedEmailCounterObj->setIdentifier($emailIdentifier);
                }
                $automatedEmailCounterObj->setEmailSentCounter($automatedEmailCounterObj->getEmailSentCounter() + $automatedEmailCounter);
                $automatedEmailCounterObj->setCreatedAt($date);
                $output->writeln('Counter updated for email identifier: '.$emailIdentifier);

                $historyEntityManager->persist($automatedEmailCounterObj);
            }
            $historyEntityManager->flush();
            // Remove from counter cache for this ad.
            CommonManager::removeCacheUsingZDeleteRangeByRank($this->getContainer(), 'automated_email_counter_'.$date, $offset, $step);
        } else {
            $output->writeln('No automated email sent counter found to update.');
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update automated email view counter.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAutomatedEmailSentCounter($input, $output)
    {
        $date = $input->getOption('date');

        if ($date) {
            $date = strtotime($date);
        } else {
            // Previous day
            $date = strtotime(date('Y-m-d')) - 86400;
        }

        $count     = $this->getAutomatedEmailSentCount($date);
        $step      = 10;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total email identifier : '.$count, true);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:automated-email-sent-counter '.$commandOptions;
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
     * Get counter for automated email sent.
     *
     * @param integer $date
     *
     * @return integer
     */
    protected function getAutomatedEmailSentCount($date)
    {
        $adCounter = CommonManager::getCacheZSize($this->getContainer(), 'automated_email_counter_'.$date);

        return $adCounter;
    }
}
