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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Mapping\Entity;

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RemoveSearchAgentEmailAdCommand extends ContainerAwareCommand
{
    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove:search-agent-email-ad')
        ->setDescription("Remove search agent email ad")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "256M")
        ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "35")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Remove old records from search agent email ad table.

Command:
 - sudo -u apache php app/console fa:remove:search-agent-email-ad
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
        $this->em            = $this->getContainer()->get('doctrine')->getManager();
        $this->mainDbName    = $this->getContainer()->getParameter('database_name');
        $daysBefore          = $input->getOption('days');
        $daysBeforeTimestamp = strtotime('-'.$daysBefore.' days', time());

        $deleteSql = 'DELETE FROM '.$this->mainDbName.'.user_search_agent_email_ad WHERE created_at < '.$daysBeforeTimestamp.';';
        $objResult = $this->executeRawQuery($deleteSql, $this->em);

        $output->writeln($daysBefore.' days old '.$objResult->rowCount().' records removed successfully.', true);
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }
}
