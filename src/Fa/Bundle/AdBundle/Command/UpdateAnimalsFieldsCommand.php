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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;

/**
 * This command is used to clean animal dimensions.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAnimalsFieldsCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 20;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

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
        ->setName('fa:update-aninal-dimensions')
        ->setDescription("Insert paa field rule.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('category_id', null, InputOption::VALUE_REQUIRED, 'category id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to clean animal dimensions.

Command:
 - php app/console fa:update-aninal-dimensions --category_id=778
 - php app/console fa:update-aninal-dimensions --category_id=749
 - php app/console fa:update-aninal-dimensions --category_id=756
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
        // set entity manager.
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->mainDbName    = $this->getContainer()->getParameter('database_name');


        //get arguments passed in command
        $offset = $input->getOption('offset');
        $searchParams['category_id'] = $input->getOption('category_id');

        // insert ads statistics.
        if (isset($offset)) {
            $this->updateAnimaldimensionWithOffset($searchParams, $input, $output);
        } else {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);

            $this->updateAnimaldimension($searchParams, $input, $output);

            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
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

    /**
     * Insert missing paa field rule.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAnimaldimension($searchParams, $input, $output)
    {
        $count  = $this->getAnimalAdCount($searchParams);
        $output->writeln('Total records: '.$count, true);

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update-aninal-dimensions '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Insert missing paa field rule.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAnimaldimensionWithOffset($searchParams, $input, $output)
    {
        $offset = $input->getOption('offset');
        $animalAds = $this->getAnimalAdResult($searchParams, $offset, $this->limit);
        $adAnimalsRepository = $this->entityManager->getRepository('FaAdBundle:AdAnimals');

        foreach ($animalAds as $animalAd) {
            $adAnimalObj = $adAnimalsRepository->find($animalAd['animal_id']);
            $metaData = unserialize($adAnimalObj->getMetaData());
            $adAnimalObj->setColourId(null);
            $adAnimalObj->setGenderId(null);
            if (isset($metaData['age_id'])) {
                unset($metaData['age_id']);
            }
            if (isset($metaData['colour'])) {
                unset($metaData['colour']);
            }
            $adAnimalObj->setMetaData(serialize($metaData));
            $this->entityManager->persist($adAnimalObj);
            $this->entityManager->flush($adAnimalObj);
            $output->writeln('Updated ad animal: '.$adAnimalObj->getId(), true);
            // Update ad data to solr
            if ($adAnimalObj->getAd()) {
                $this->getContainer()->get('fa_ad.entity_listener.ad')->handleSolr($adAnimalObj->getAd());
                $output->writeln('Solr index has been updated for ad id: '.$adAnimalObj->getId(), true);
            }
        }
    }

    /**
     * Get query builder for paa field rule.
     *
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getAnimalAdCount($searchParams = array())
    {
        $sql = 'select count(aa.id) as total_ads from ad a
                inner join ad_animals aa on a.id = aa.ad_id where a.category_id in (SELECT node.id
                FROM category AS node,
                    category AS parent
                WHERE node.lft
                BETWEEN parent.lft
                AND parent.rgt
                AND parent.id = '.$searchParams['category_id'].')';

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        $res = $stmt->fetch();

        return $res['total_ads'];
    }

    /**
     * Get query builder for paa field rule results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAnimalAdResult($searchParams, $offset, $limit)
    {
        $sql = 'select a.id as ad_id, aa.id as animal_id from ad a
                inner join ad_animals aa on a.id = aa.ad_id where a.category_id in (SELECT node.id
                FROM category AS node,
                    category AS parent
                WHERE node.lft
                BETWEEN parent.lft
                AND parent.rgt
                AND parent.id = '.$searchParams['category_id'].') LIMIT '.$limit.' OFFSET '.$offset;

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        return $stmt->fetchAll();
    }
}
