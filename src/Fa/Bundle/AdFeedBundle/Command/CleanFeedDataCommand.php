<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Doctrine\DBAL\Logging\EchoSQLLogger;

/**
 * This command is used to clean feed data
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CleanFeedDataCommand extends ContainerAwareCommand
{
    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;


    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:clean-feed-data')
        ->setDescription('Clean feed data')
        ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action', null)
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to remove unwanted feed data.

Command:
 - php app/console fa:clean-feed-data --action="remove-empty-folders"
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
        $managerRegistry = $this->getContainer()->get('doctrine');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        $feedAdDataPath = $this->getContainer()->getParameter('fa.feed.data.dir');
        $feedAdImagePath = $feedAdDataPath.'/images/';
        $adFeedRepository  = $this->em->getRepository('FaAdFeedBundle:AdFeed');

        $action = $input->getOption('action');
        $path = $input->getOption('path');

        if ($action == 'remove-empty-folders') {
            $feedDataFolders = glob($feedAdImagePath.'*');
            if (count($feedDataFolders)) {
                foreach ($feedDataFolders as $feedDataFolder) {
                    CommonManager::removeEmptySubFolders($feedDataFolder, $output);
                }
            }
        } elseif ($action == 'remove-extra-images') {
            if (!$path) {
                $feedDataFolders = glob($feedAdImagePath.'*');
                if (count($feedDataFolders)) {
                    foreach ($feedDataFolders as $feedDataFolder) {
                        $feedDataSubFolders = glob($feedDataFolder.'/*');
                        foreach ($feedDataSubFolders as $feedDataSubFolder) {
                            $this->removeExtraAdImagesCommand($input, $output, $feedDataSubFolder);
                        }
                    }
                }
            } elseif ($path) {
                $feedAdImages = glob($path.'/*.jpg');
                foreach ($feedAdImages as $feedAdImage) {
                    $explodeRes = explode('_', basename($feedAdImage));
                    if ($explodeRes[0]) {
                        $adFeedObj = $adFeedRepository->findOneBy(array('unique_id' => $explodeRes[0]));
                        if (!$adFeedObj) {
                            if (is_file($feedAdImage)) {
                                if (unlink($feedAdImage)) {
                                    $output->writeln('Image deleted for : '.$feedAdImage, true);
                                } else {
                                    $output->writeln('Problem in deleting image : '.$feedAdImage, true);
                                }
                            }else {
                                $output->writeln('Image not found for : '.$feedAdImage, true);
                            }
                        } else {
                            $filesize = filesize($feedAdImage);
                            $filesize = $filesize /(1024*1024);
                            $output->writeln('Image size : '.$filesize, true);
                        }
                    }
                }
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Remove extra feed ad images.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function removeExtraAdImagesCommand($input, $output, $path)
    {
        $commandOptions = null;
        foreach ($input->getOptions() as $option => $value) {
            if ($value) {
                $commandOptions .= ' --'.$option.'='.$value;
            }
        }

        if ($path) {
            $commandOptions .= ' --path='.$path;
        }
        $memoryLimit = '';
        if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
            $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
        }
        $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:clean-feed-data '.' '.$commandOptions;
        $output->writeln($command, true);
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            $output->writeln('Error occurred during subtask', true);
        }
    }
}
