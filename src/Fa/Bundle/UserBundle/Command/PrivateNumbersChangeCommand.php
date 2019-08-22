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
 - php bin/console fa:change:private-numbers --csv_file="FAD.csv"
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
        
        $date = date('d-m-Y');
        
        echo "RUN TIME DATE IS: >>>>>>>>>> ".$date."\N";
        
        if ($csvFile) {
            $reader = new \EasyCSV\Reader(__DIR__."/FAD.csv");
            $reader->setDelimiter(';');
            $batchSize = 100;
            $row = 0;
            $parameters = array();
            
            while ($row = $reader->getRow()) {
                if (isset($row['email']) && $row['email']) {
                    $this->getContainer->get('fa.mail.manager')->send($row['email'], 'private_numbers_change', array(), CommonManager::getCurrentCulture($this->getContainer));
                }
            }
            
            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
           
    }
}

