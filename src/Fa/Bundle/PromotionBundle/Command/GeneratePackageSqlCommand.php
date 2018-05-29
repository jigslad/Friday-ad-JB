<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:generate:package-sql
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class GeneratePackageSqlCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:package-sql')
        ->setDescription("Generate package sql from csv");
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootPath = $this->getContainer()->get('kernel')->getRootDir().'/../';
        $file_handle = fopen(__DIR__."/packages.csv", "r");

        if (!$file_handle) {
            $output->writeln('Csv file dose not exist.');
        } else {
            $line = 1;
            $sql = "SET FOREIGN_KEY_CHECKS = 0;\nTRUNCATE TABLE package;\nTRUNCATE TABLE package_rule;\nTRUNCATE TABLE package_upsell;\nTRUNCATE TABLE package_print;\n\n";
            while (!feof($file_handle)) {
                $line_of_text = fgetcsv($file_handle, 1024);
                $packageId    = $line_of_text[0];
                if ($line > 1) {
                    $value = array();
                    if (isset($line_of_text[10])) {
                        foreach (explode(',', $line_of_text[10]) as $valueRes) {
                            $explodeRes = explode(':', $valueRes);
                            $value[trim($explodeRes[0])] = trim($explodeRes[1]);
                        }
                    }
                    if ($line_of_text[12]) {
                        $sql .= "INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('".$packageId."', '".addslashes($line_of_text[1])."', '".addslashes($line_of_text[2])."', '".addslashes($line_of_text[3])."', '".addslashes($line_of_text[4])."', '".(!$line_of_text[11] ? $line_of_text[7] : null)."', '".(!$line_of_text[11] ? $line_of_text[16] : null)."', '".time()."', 1, '".serialize($value)."', 'ad', '".$line_of_text[9]."', '".$line_of_text[12]."', '".$line_of_text[14]."', '".$line_of_text[15]."', '".$line_of_text[8]."');\n";
                    } else {
                        $sql .= "INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('".$packageId."', '".addslashes($line_of_text[1])."', '".addslashes($line_of_text[2])."', '".addslashes($line_of_text[3])."', '".addslashes($line_of_text[4])."', '".(!$line_of_text[11] ? $line_of_text[7] : null)."', '".(!$line_of_text[11] ? $line_of_text[16] : null)."', '".time()."', 1, '".serialize($value)."', 'ad', '".$line_of_text[9]."', null, '".$line_of_text[14]."', '".$line_of_text[15]."', '".$line_of_text[8]."');\n";
                    }
                    //insert in package rule
                    if ($line_of_text[13]) {
                        $locationGroups = explode('|', $line_of_text[13]);
                        foreach ($locationGroups as $locationGroup) {
                            $sql .= "INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('".$packageId."', '".$line_of_text[5]."', '".$locationGroup."');\n";
                        }
                    } else {
                        $sql .= "INSERT INTO package_rule (`package_id`, `category_id`) VALUES ('".$packageId."', '".$line_of_text[5]."');\n";
                    }
                    //insert upsells
                    if ($line_of_text[6]) {
                        $upsellIds = explode('|', $line_of_text[6]);
                        foreach ($upsellIds as $upsellId) {
                            $sql .= "INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('".$packageId."', '".$upsellId."');\n";
                        }
                    }
                    //insert print durations
                    if ($line_of_text[11]) {
                        $printDurations = explode(',', $line_of_text[11]);
                        $adminPrintDurations = explode(',', $line_of_text[17]);
                        foreach ($printDurations as $printDurationIndex => $printDuration) {
                            $printExplodeRes = explode('|', $printDuration);
                            $adminPrintExplodeRes = explode('|', $adminPrintDurations[$printDurationIndex]);
                            $sql .= "INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('".$packageId."', '".$printExplodeRes[0]."', '".$printExplodeRes[1]."', '".$adminPrintExplodeRes[1]."', '".time()."');\n";
                        }
                    }
                    $sql .= "\n\n";
                }
                $line++;
            }

            fclose($file_handle);

            //write in file
            $packageSqlPath = $rootPath."sql_temp/package_04112014.sql";
            $packageFile = fopen($packageSqlPath, "w+");
            if (!$packageFile) {
                $output->writeln('Can not write file.');
            } else {
                fwrite($packageFile, $sql);
                fclose($packageFile);
                $output->writeln('Sql file generated: '.$packageSqlPath);
            }
        }
    }
}
