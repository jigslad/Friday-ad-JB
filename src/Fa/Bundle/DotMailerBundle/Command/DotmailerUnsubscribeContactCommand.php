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

/**
 * This command is used to unsubscribe one contact from dotmailer.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerUnsubscribeContactCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:unsubscribe-contact')
        ->setDescription("Daily bulk upload the data to master address book.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('master_id', null, InputOption::VALUE_OPTIONAL, 'Master address book id', null)
        ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Dotmailer id', null)
        ->setHelp(
            <<<EOF
Cron: To delete dotmailer contact


Command:
 - php app/console fa:dotmailer:unsubscribe-contact --id="xxxx"
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
        $id = $input->getOption('id');

        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $dotmailer = $em->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('id' => $id));

            if ($dotmailer) {
                $response = $em->getRepository('FaDotMailerBundle:Dotmailer')->sendUnsubscribeUserFromDotmailerRequest($dotmailer, $this->getContainer());
                if (isset($response['suppressedContact']) && isset($response['suppressedContact']['id']) && $response['suppressedContact']['id']) {
                    $output->writeln('Contact unsubscribed from dotmailer for id: '.$id, true);
                } else {
                    $output->writeln('Contact not unsubscribed from dotmailer for id: '.$id, true);
                }
            } else {
                $output->writeln('Contact not found or already unsubscribed in dotmailer for id: '.$id, true);
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->getContainer(), 'Error: Unsubscribed contact from dotmailer => '.$id, $e->getMessage(), $e->getTraceAsString());
            $output->writeln($e->getMessage(), true);
        }
    }
}
