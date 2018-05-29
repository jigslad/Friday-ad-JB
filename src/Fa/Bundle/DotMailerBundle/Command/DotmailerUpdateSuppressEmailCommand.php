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
use Fa\Bundle\DotMailerBundle\Repository\DotmailerResponseRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to Suppress emails.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerUpdateSuppressEmailCommand extends ContainerAwareCommand
{
    private $limit = 1000;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:update-suppress-email')
        ->setDescription("Dotmailer Suppress emails.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('total_page', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', 5)
        ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'The date from which any unsubscribed contacts are returned', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at 6am

Actions:
- Dotmailer Suppress emails.

Command:
 - php app/console fa:dotmailer:update-suppress-email --start_date=2015-09-01
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
        $offset    = $input->getOption('offset');
        $startDate = $input->getOption('start_date');
        if (!$startDate) {
            $startDate = date('Y-m-d', strtotime('-3 days'));
        }

        $searchParam['start_date'] = $startDate;

        if (isset($offset)) {
            $this->dotmailerSuppressEmailsWithOffset($searchParam, $input, $output);
        } else {
            $this->dotmailerSuppressEmails($searchParam, $input, $output);
        }
    }

    /**
     * Suppress emails with given offset.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerSuppressEmailsWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $offset        = $input->getOption('offset');

        $suppressedEmails = array();
        $suppressedEmails = $this->sendRequest($searchParam, $this->limit, $offset);

        if (count($suppressedEmails)) {
            foreach ($suppressedEmails as $suppressedEmail) {
                try {
                    if (isset($suppressedEmail['suppressedContact']) && isset($suppressedEmail['suppressedContact']['email'])) {
                        $dotmailerObj = $entityManager->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $suppressedEmail['suppressedContact']['email']));
                        if ($dotmailerObj) {
                            $dotmailerObj->setIsSuppressed(1);
                            $dotmailerObj->setSuppressedReason($suppressedEmail['suppressedContact']['status']);
                            $dotmailerObj->setDotmailerNewsletterUnsubscribe(1);
                            $dotmailerObj->setOptIn(0);
                            $entityManager->persist($dotmailerObj);
                            $user = $entityManager->getRepository('FaUserBundle:User')->findOneBy(array('email' => $dotmailerObj->getEmail()));
                            if ($user) {
                                $user->setIsEmailAlertEnabled(0);
                                $entityManager->persist($user);
                            }
                            $output->writeln('Email suppressed:'.$suppressedEmail['suppressedContact']['email'], true);
                        } else {
                            $output->writeln('Email not found in dotmailer:'.$suppressedEmail['suppressedContact']['email'], true);
                        }
                    }
                } catch (\Exception $e) {
                    CommonManager::sendErrorMail($this->getContainer(), 'Error: Subscribed contact from dotmailer => '.$id, $e->getMessage(), $e->getTraceAsString());
                    $output->writeln($e->getMessage(), true);
                }
            }

            $entityManager->flush();
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Suppress emails.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function dotmailerSuppressEmails($searchParam, $input, $output)
    {
        $totalPage = $input->getOption('total_page');
        $count     = $totalPage * $this->limit;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:dotmailer:update-suppress-email '.$commandOptions;
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
     * Send request to dotmailer.
     *
     * @param array   $searchParam Search parameters.
     * @param integer $limit       Limit.
     * @param integer $offset      Offset.
     *
     * @return array
     */
    public function sendRequest($searchParam, $limit, $offset)
    {
        $url = $this->getContainer()->getParameter('fa.dotmailer.api.url').'/'.$this->getContainer()->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        //https://api.dotmailer.com/v2/contacts/suppressed-since/[Date]?select=[Select]&skip=[Skip]
        $url = $url.'contacts/suppressed-since/'.$searchParam['start_date'].'?select='.$limit.'&skip='.$offset;

        $username = $this->getContainer()->getParameter('fa.dotmailer.api.username');
        $password = $this->getContainer()->getParameter('fa.dotmailer.api.password');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json'
            )
        );
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD,$username . ':' . $password);

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $response;
    }
}
