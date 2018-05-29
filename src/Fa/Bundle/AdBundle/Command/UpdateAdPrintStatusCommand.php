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
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;

/**
 * This command is used to update print ad status.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdPrintStatusCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-print-status')
        ->setDescription("Read moderation queue and send ad for moderation")
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Update ad print status

Command:
 - php app/console fa:update:ad-print-status

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
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityManager->getRepository('FaAdBundle:AdPrint')
            ->getBaseQueryBuilder()
            ->update()
            ->set(AdPrintRepository::ALIAS.'.print_queue', '1')
            ->andwhere(AdPrintRepository::ALIAS.'.tmp_print_queue = 1')
            ->getQuery()
            ->execute();

        $entityManager->getRepository('FaAdBundle:AdPrint')
            ->getBaseQueryBuilder()
            ->update()
            ->set(AdPrintRepository::ALIAS.'.tmp_print_queue', '0')
            ->andwhere(AdPrintRepository::ALIAS.'.tmp_print_queue = 1')
            ->getQuery()
            ->execute();

        $output->writeln('Print ad status updated.');
    }
}
