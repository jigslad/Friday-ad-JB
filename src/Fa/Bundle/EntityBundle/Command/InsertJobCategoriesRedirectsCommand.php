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
 * This command is used to add new redirects for job categories.
 *
 * php app/console fa:insert:job-categories-redirects
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class InsertJobCategoriesRedirectsCommand extends ContainerAwareCommand
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
        ->setName('fa:insert:job-categories-redirects')
        ->setDescription("Generate new job categories mapping")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to new redirects for job categories.

Command:
 - php app/console fa:insert:job-categories-redirects
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

        $reader = new \EasyCSV\Reader(__DIR__."/job_redirects.csv");
        $reader->setDelimiter(';');

        while ($row = $reader->getRow()) {
            if (isset($row['old_level']) && isset($row['old']) && isset($row['new_level']) && isset($row['new'])) {
                $oldSql = 'SELECT full_slug
                    FROM category_old
                    Where lvl = '.$row['old_level'].' and name = "'.$row['old'].'";';
                $oldRes = $this->executeRawQuery($oldSql, $this->entityManager);
                $oldObj = $oldRes->fetch();
                if (!$oldObj) {
                    echo "OldObj not found for cat:".$row['old']."\n";
                } else {
                    $newSql = 'SELECT full_slug
                        FROM category
                        Where status = 1 and lvl = '.$row['new_level'].' and name = "'.$row['new'].'";';
                    $newRes = $this->executeRawQuery($newSql, $this->entityManager);
                    $newObj = $newRes->fetch();
                    if (!$newObj) {
                        echo "newObj not found for cat:".$row['new']."\n";
                    } elseif ($oldObj && $newObj) {
                        $insertSql = 'INSERT INTO redirects (old, new, is_location, created_at) VALUE ("'.$oldObj['full_slug'].'/", "'.$newObj['full_slug'].'", "0", "'.time().'");';
                        $this->executeRawQuery($insertSql, $this->entityManager);
                    }
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
