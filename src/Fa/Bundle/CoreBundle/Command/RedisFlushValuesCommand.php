<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to remove redis cache
 *
 * php app/console fa:redis:flushall
 *
 * @author Janak Jadeja<janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class RedisFlushValuesCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('fa:redis:flushall')
              ->addOption('pattern', null, InputOption::VALUE_OPTIONAL, 'key pattern', '*')
            ->setDescription('Flushes the redis data using the redis flushall command')
            ->setHelp(
                <<<EOF
Actions:
- Use to remove redis cache

Command:
 - php app/console fa:redis:flushall
 - php app/console fa:redis:flushall --pattern="category*"
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
        $this->input = $input;
        $this->output = $output;
        $this->executeRedisCommand();
    }
    /**
     * {@inheritDoc}
     */
    protected function executeRedisCommand()
    {
        if ($this->proceedingAllowed()) {
            $this->flushAll();
        } else {
            $this->output->writeln('<error>Flushing cancelled</error>');
        }
    }


    /**
     * Checks if either the no-interaction option was chosen or asks the user to proceed
     *
     * @return boolean true if either no-interaction was chosen or the user wants to proceed
     */
    protected function proceedingAllowed()
    {
        if ($this->input->getOption('no-interaction')) {
            return true;
        }

        return $this->getHelper('dialog')->askConfirmation($this->output, '<question>Are you sure you wish to flush the whole database? (y/n)</question>', false);
    }

    /**
     * Flushing all redis databases
     */
    private function flushAll()
    {
        CommonManager::removeCachePattern($this->getContainer(), $this->input->getOption('pattern'));
        $this->output->writeln('<info>All redis databases flushed</info>');
    }
}
