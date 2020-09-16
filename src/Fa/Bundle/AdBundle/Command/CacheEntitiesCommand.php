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

/**
 * This command is used to add/update/delete solr index for ads.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CacheEntitiesCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
            ->setName('fa:cache:entities')
            ->setDescription("Cache all entities.")
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'Entity', 'all')
            ->setHelp(
                <<<EOF
Cron: To be setup to run at mid-night.

Command:
 - php bin/console fa:cache:entities --entity=all
EOF
            );
    }

    /**
     * Execute.
     *
     * @param InputInterface $input InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //get arguments passed in command
        $entity = $input->getOption('entity');
        $container = $this->getContainer();

        switch ($entity) {
            case 'category' : $this->em->getRepository('FaEntityBundle:Category')->getAllCategories($container);
                $output->writeln('Successfully cached categories.', true);
                break;

            case 'location':
                $this->em->getRepository('FaEntityBundle:Region')->getAllRegions($container);
                $this->em->getRepository('FaEntityBundle:Location')->getAllLocations($container);
                $this->em->getRepository('FaEntityBundle:Locality')->getAllLocalities($container);
                /** Commenting out postcode as the DB is very heavy */
                /*$this->em->getRepository('FaEntityBundle:Postcode')->getAllPostcodes($container);*/

                $output->writeln('Successfully cached locations.', true);
                break;

            case 'entity' : $this->em->getRepository('FaEntityBundle:Entity')->getAllEntities($container);
                $output->writeln('Successfully cached entities.', true);
                break;

            default:
                $this->em->getRepository('FaEntityBundle:Category')->getAllCategories($container);

                $this->em->getRepository('FaEntityBundle:Region')->getAllRegions($container);
                $this->em->getRepository('FaEntityBundle:Location')->getAllLocations($container);
                $this->em->getRepository('FaEntityBundle:Locality')->getAllLocalities($container);
                /** Commenting out postcode as the DB is very heavy */
                /*$this->em->getRepository('FaEntityBundle:Postcode')->getAllPostcodes($container);*/

                $this->em->getRepository('FaEntityBundle:Entity')->getAllEntities($container);

                $output->writeln('Successfully cached all entities.', true);

        }

    }
}