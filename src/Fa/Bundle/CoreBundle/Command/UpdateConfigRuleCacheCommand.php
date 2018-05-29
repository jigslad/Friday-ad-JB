<?php

/**
 * This file is part of the core bundle.
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
use Fa\Bundle\CoreBundle\Repository\ConfigRuleRepository;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This command is used to generate seo rule cache.
 *
 * php app/console fa:update:config:rule:cache generate
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateConfigRuleCacheCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:config:rule:cache')
        ->setDescription("Update or Remove seo rule cache")
        ->addArgument('action', InputArgument::REQUIRED, 'generate or remove')
        ->addArgument('config_id', InputArgument::REQUIRED, 'config id')
        ->setHelp(
            <<<EOF
Cron: To be setup to generate and remove entity cache.

Actions:
- Generate cache.

Command:
 - php app/console fa:update:config:rule:cache generate 10
 - php app/console fa:update:config:rule:cache generate 9
 - php app/console fa:update:config:rule:cache generate 4
 - php app/console fa:update:config:rule:cache generate 8
 - php app/console fa:update:config:rule:cache remove 10
 - php app/console fa:update:config:rule:cache remove 9
 - php app/console fa:update:config:rule:cache remove 4
 - php app/console fa:update:config:rule:cache remove 8

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
        //get arguments passed in command
        $action               = $input->getArgument('action');
        $configId             = $input->getArgument('config_id');
        $entityManager        = $this->getContainer()->get('doctrine')->getManager();
        $categoryRepository   = $entityManager->getRepository('FaEntityBundle:Category');
        $configRuleRepository = $entityManager->getRepository('FaCoreBundle:ConfigRule');

        if ($action == 'generate') {
            if ($configId == ConfigRepository::TOP_BUSINESSPAGE) {
                $nestedChilrenIds = $categoryRepository-> getNestedChildrenIdsByCategoryId(CategoryRepository::SERVICES_ID, $this->getContainer());
                $nestedChilrenIds = array_merge($nestedChilrenIds, $categoryRepository-> getNestedChildrenIdsByCategoryId(CategoryRepository::ADULT_ID, $this->getContainer()));
                foreach ($nestedChilrenIds as $nestedChilrenId) {
                    $configRuleRepository->getTopBusiness($nestedChilrenId, $this->getContainer());
                }
            } elseif ($configId == ConfigRepository::NUMBER_OF_BUSINESSPAGE_SLOTS) {
                $nestedChilrenIds = $categoryRepository-> getNestedChildrenIdsByCategoryId(CategoryRepository::SERVICES_ID, $this->getContainer());
                $nestedChilrenIds = array_merge($nestedChilrenIds, $categoryRepository-> getNestedChildrenIdsByCategoryId(CategoryRepository::ADULT_ID, $this->getContainer()));
                foreach ($nestedChilrenIds as $nestedChilrenId) {
                    $configRuleRepository->getBusinessPageSlots($nestedChilrenId, $this->getContainer());
                }
            } elseif ($configId == ConfigRepository::LISTING_TOPAD_SLOTS) {
                $configRuleRepository->getListingTopAdSlots($this->getContainer());
            } elseif ($configId == ConfigRepository::NUMBER_OF_ORGANIC_RESULTS) {
                $configRuleRepository->getNumberOfOrganicResult($this->getContainer());
            }
            $output->writeln('Cache generated for: '.$configId);
        } elseif ($action == 'remove') {
            //remove cache
            if ($configId == ConfigRepository::TOP_BUSINESSPAGE) {
                CommonManager::removeCachePattern($this->getContainer(), $this->getConfigRuleTableName().'|getTopBusiness|*');
            } elseif ($configId == ConfigRepository::NUMBER_OF_BUSINESSPAGE_SLOTS) {
                CommonManager::removeCachePattern($this->getContainer(), $this->getConfigRuleTableName().'|getBusinessPageSlots|*');
            } elseif ($configId == ConfigRepository::LISTING_TOPAD_SLOTS) {
                CommonManager::removeCachePattern($this->getContainer(), $this->getConfigRuleTableName().'|getListingTopAdSlots|*');
            } elseif ($configId == ConfigRepository::NUMBER_OF_ORGANIC_RESULTS) {
                CommonManager::removeCachePattern($this->getContainer(), $this->getConfigRuleTableName().'|getNumberOfOrganicResult|*');
            }
            $output->writeln('Cache removed for: '.$configId);
        }
    }

    /**
     * Get table name.
     */
    private function getConfigRuleTableName()
    {
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();

        return $entityManager->getClassMetadata('FaCoreBundle:ConfigRule')->getTableName();
    }
}
