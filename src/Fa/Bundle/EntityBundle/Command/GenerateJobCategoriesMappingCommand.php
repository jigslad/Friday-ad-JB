<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Entity\Category;

/**
 * This command is used to add new jobs category mapping.
 *
 * php app/console fa:generate:new-job-categories-mapping
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class GenerateJobCategoriesMappingCommand extends ContainerAwareCommand
{
    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:new-job-categories-mapping')
        ->setDescription("Generate new job categories mapping")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to generate new job categories mapping.

Command:
 - php app/console fa:generate:new-job-categories-mapping
EOF
        );;
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $reader = new \EasyCSV\Reader(__DIR__."/generate_job_old_new_categories_mapping.csv");
        $reader->setDelimiter(';');

        if (file_exists(__DIR__."/job_mapping.csv")) {
            unlink(__DIR__."/job_mapping.csv");
        }
        $writer = new \EasyCSV\Writer(__DIR__."/job_mapping.csv");
        $writer->setDelimiter(';');
        $writer->writeRow(array('old_id', 'new_id'));
        while ($row = $reader->getRow()) {
            if (isset($row['old_level']) && isset($row['old_parent']) && isset($row['old_child']) && isset($row['new_level']) && isset($row['new_parent']) && isset($row['new_child'])) {
                $oldParentSql = 'SELECT id
                    FROM category_old
                    Where lvl = '.($row['old_level']-1).' and name = "'.$row['old_parent'].'";';
                $oldParentRes = $this->executeRawQuery($oldParentSql, $this->entityManager);
                $oldParentObj = $oldParentRes->fetch();

                if (isset($oldParentObj['id'])) {
                    $oldChildSql = 'SELECT id
                        FROM category_old
                        Where lvl = '.($row['old_level']).' and name = "'.$row['old_child'].'" and parent_id = "'.$oldParentObj['id'].'";';
                    $oldchildRes = $this->executeRawQuery($oldChildSql, $this->entityManager);
                    $oldChildObj = $oldchildRes->fetch();
                } else {
                    echo "oldParentObj not found for cat:".$row['old_child'];
                }

                if (isset($row['leaf']) && trim($row['leaf'])) {
                    $newChildSql = 'SELECT id
                            FROM category
                            Where status = 1 and lvl = '.($row['new_level']).' and name = "'.$row['leaf'].'" ;';
                    $newchildRes = $this->executeRawQuery($newChildSql, $this->entityManager);
                    $newChildObj = $newchildRes->fetch();
                } else {
                    if (isset($oldChildObj['id'])) {
                        $newParentSql = 'SELECT id
                        FROM category
                        Where status = 1 and lvl = '.($row['new_level']-1).' and name = "'.$row['new_parent'].'";';
                        $newParentRes = $this->executeRawQuery($newParentSql, $this->entityManager);
                        $newParentObj = $newParentRes->fetch();
                    } else {
                        echo "oldChildObj not found for cat:".$row['old_child'];
                    }

                    if (isset($newParentObj['id'])) {
                        $newChildSql = 'SELECT id
                            FROM category
                            Where status = 1 and lvl = '.($row['new_level']).' and name = "'.$row['new_child'].'" and parent_id = "'.$newParentObj['id'].'";';
                        $newchildRes = $this->executeRawQuery($newChildSql, $this->entityManager);
                        $newChildObj = $newchildRes->fetch();
                    } else {
                        echo "newParentObj not found for cat:".$row['new_child'];
                    }
                }

                if (isset($oldChildObj['id']) && isset($newChildObj['id']) && $oldChildObj['id'] && $newChildObj['id'] && $oldChildObj['id']  != $newChildObj['id']) {
                    echo $oldChildObj['id']."=>".$newChildObj['id']."\n";
                    $writer->writeRow(array($oldChildObj['id'], $newChildObj['id']));
                }elseif (!isset($oldChildObj['id']) || !isset($newChildObj['id'])) {
                    echo "------".$row['new_child']."\n";
                    echo "old\n";
                    print_R($oldChildObj);
                    echo "new\n";
                    print_R($newChildObj);
                }
            }

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
}
