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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to update nimber size.
 *
 * php app/console fa:update:category:nimber-size
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateCategoryNimberSizeCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:category:nimber-size')
        ->setDescription("Update category nimber size");
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityCacheManager = $this->getContainer()->get('fa.entity.cache.manager');
        $entityManager      = $this->getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository('FaEntityBundle:Category');
        $file_handle = fopen(__DIR__."/nimber_category_size.csv", "r");

        if (!$file_handle) {
            $output->writeln('Csv file dose not exist.');
        } else {
            while (!feof($file_handle)) {
                $line_of_text = fgetcsv($file_handle, 1024);
                $totalCells = count($line_of_text);
                $nimberSize = trim($line_of_text[$totalCells - 1]);
                $nimberCategoryText = trim($line_of_text[$totalCells - 2]);
                $nimberParentCategoryText = trim($line_of_text[$totalCells - 3]);
                $parentCategories = $categoryRepository->findBy(array('name' => $nimberParentCategoryText, 'lvl' => ($totalCells - 2)));
                if (count($parentCategories) > 1) {
                    foreach ($parentCategories as $parentCategory) {
                        if (isset($line_of_text[$totalCells - 4]) && $parentCategory->getParent() && $parentCategory->getParent()->getName() == trim($line_of_text[$totalCells - 4])) {
                            $parentCategoryObj = $parentCategory;
                            break;
                        }
                    }
                } elseif (count($parentCategories) == 1) {
                    $parentCategoryObj = $parentCategories[0];
                }
                if ($nimberSize && $nimberCategoryText && $parentCategoryObj) {
                    $categoryObj = $categoryRepository->findOneBy(array('name' => $nimberCategoryText, 'parent' => $parentCategoryObj->getId()));
                    if ($categoryObj) {
                        CommonManager::removeCache($this->getContainer(), 'category|getNimberDetailForCategoryId|'.$categoryObj->getId());
                        //$output->writeln($nimberCategoryText.'<=>'.$categoryObj->getId(), true);
                        $updateSql = 'update category set is_nimber_enabled = 1, nimber_size = "'.$nimberSize.'" where id = '.$categoryObj->getId();
                        $this->executeRawQuery($updateSql, $entityManager);
                        $output->writeln('Nimber size updated for category:'.$nimberCategoryText.'<=>'.$categoryObj->getId(), true);
                    } else {
                        $output->writeln('Can not find category using parent :'.$nimberCategoryText, true);
                    }
                } else {
                    $output->writeln('Can not find category using prent name & level :'.$nimberCategoryText, true);
                }
            }

            fclose($file_handle);
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
