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
 * This command is used to update dotmailer data in bulk.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerDeleteContactCommand extends ContainerAwareCommand
{
    private $response;

    private $httpcode;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:dotmailer:delete-contact')
        ->setDescription("Daily bulk upload the data to master address book.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('master_id', null, InputOption::VALUE_OPTIONAL, 'Master address book id', null)
        ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email address', null)
        ->setHelp(
            <<<EOF
Cron: To delete dotmailer contact


Command:
 - php app/console fa:dotmailer:delete-contact --email="xxxx"
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
        $email = $input->getOption('email');

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
}
