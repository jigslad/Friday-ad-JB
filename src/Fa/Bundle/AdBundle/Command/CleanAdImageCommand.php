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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to clean ad image data
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CleanAdImageCommand extends ContainerAwareCommand
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
        ->setName('fa:clean-ad-image-data')
        ->setDescription('Clean ad image data')
        ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action', null)
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to remove unwanted feed data.

Command:
 - php app/console fa:clean-ad-image-data --action="remove-empty-folders"
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

        $webPath = $this->getContainer()->get('kernel')->getRootDir().'/../web';
        $adImagePath = $webPath.'/uploads/image/';
        $adImageRepository  = $this->em->getRepository('FaAdBundle:AdImage');

        $action = $input->getOption('action');
        $path = $input->getOption('path');

        if ($action == 'remove-empty-folders') {
            $adImageFolders = glob($adImagePath.'*');
            if (count($adImageFolders)) {
                foreach ($adImageFolders as $adImageFolder) {
                    CommonManager::removeEmptySubFolders($adImageFolder, $output);
                }
            }
        } elseif ($action == 'remove-images') {
            if (!$path) {
                $adImageFolders = glob($adImagePath.'*');
                if (count($adImageFolders)) {
                    foreach ($adImageFolders as $adImageFolder) {
                        $this->removeAdImagesCommand($input, $output, $adImageFolder);
                    }
                }
            } elseif ($path) {
                $adImages = glob($path.'/*.jpg');
                foreach ($adImages as $adImage) {
                    $explodeRes = explode('_', basename($adImage));
                    if ($explodeRes[0]) {
                        $explodeRes[1] = str_replace('.jpg', '', $explodeRes[1]);
                        $adImageObj = $adImageRepository->findOneBy(array('ad' => $explodeRes[0], 'hash' => $explodeRes[1], 'aws' => 1, 'local' => 0));
                        if (!$adImageObj) {
                            $adImageObj = $adImageRepository->findOneBy(array('ad' => $explodeRes[0], 'hash' => $explodeRes[1]));
                            if (!$adImageObj) {
                                if (is_file($adImage)) {
                                    if (unlink($adImage)) {
                                        $output->writeln('Image deleted for : '.$adImage, true);
                                    } else {
                                        $output->writeln('Problem in deleting image : '.$adImage, true);
                                    }
                                } else {
                                    $output->writeln('Image not found for : '.$adImage, true);
                                }
                            }
                        } else {
                            if (is_file($adImage)) {
                                if (unlink($adImage)) {
                                    $output->writeln('Image deleted for : '.$adImage, true);
                                } else {
                                    $output->writeln('Problem in deleting image : '.$adImage, true);
                                }
                            } else {
                                $output->writeln('Image not found for : '.$adImage, true);
                            }
                        }
                    }
                }
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Remove ad images.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function removeAdImagesCommand($input, $output, $path)
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
        $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:clean-ad-image-data '.' '.$commandOptions;
        $output->writeln($command, true);
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            $output->writeln('Error occurred during subtask', true);
        }
    }
}
