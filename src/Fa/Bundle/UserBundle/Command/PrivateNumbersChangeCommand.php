<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;


/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PrivateNumbersChangeCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:change:private-numbers')
        ->setDescription("Change Private Numbers")
        ->addArgument('csv_file', InputArgument::REQUIRED, 'CSV File')
        ->addOption('email_identifier', InputOption::REQUIRED, 'Email Identifier')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('csv_file', null, InputOption::VALUE_OPTIONAL, 'Csv File', null)
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Change Private Numbers 

Command:
 - php bin/console fa:change:private-numbers
 - php bin/console fa:change:private-numbers FAD.csv --email_identifier=private_numbers_change_phase_two
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
        
        //get arguments passed in command
        $csvFile = '';
        $csvFile = $input->getArgument('csv_file');
        
        $email_identifier = '';
        if ($input->hasOption("email_identifier") && $input->getOption("email_identifier")) {
            $email_identifier = $input->getOption("email_identifier");
        }
        
        $returnVar = 0;
        
        $date = date('d-m-Y');
        
        echo "RUN TIME DATE IS: >>>>>>>>>> ".$date."\N";
        
        if ($csvFile) {
            $reader = new \EasyCSV\Reader(__DIR__."/".$csvFile);
            $reader->setDelimiter(';');
            $batchSize = 100;
            $row = 0;
            $parameters = array(); 
            
            while ($row = $reader->getRow()) {                
                if (isset($row['email']) && $row['email']) {
                    $this->getContainer()->get('fa.mail.manager')->send($row['email'], $email_identifier , array(), CommonManager::getCurrentCulture($this->getContainer()), null, array(), array(), array(), null,null, 1,0);
                    echo 'Sent Mail to '.$row['email'].'<br>';
                }
            }
            
            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
           
    }
}

