<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\MessageBundle\Repository\MessageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to send contact request to moderation.
 *
 * @author GauravAggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version v1.0
 */
class CleanUnwantedHeaderImageCommand extends ContainerAwareCommand
{
    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:clean:unwanted-header-image')
        ->setDescription("Clean unwanted Header Images")
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Clean Unwanted Header Images

Command:
 - php app/console fa:clean:unwanted-header-image

EOF
        );
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {	
    	$webPath = $this->getContainer()->get('kernel')->getRootDir().'/../web';
    	$imagePath = $webPath.'/uploads/headerimage/';
    	$this->clearUnwantedBannerImages($imagePath, $output);
    }

    /**
     * Removing unwanted Header Images from folder.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function clearUnwantedBannerImages($imagePath = '', $output)
    {	
    	$stat_time = time();
    	$managerRegistry = $this->getContainer()->get('doctrine');
    	$this->em = $this->getContainer()->get('doctrine')->getManager();
    	$headerImageRepository  =  $this->em->getRepository('FaContentBundle:HeaderImage');
    	$adImageFolders = glob($imagePath.'*');
    	$cnt = 0;
    	if(!empty( $adImageFolders )) {
    		foreach ($adImageFolders as $image) {
    			$expFile = explode('/', $image);
    			$fileName = end($expFile);    			
    			$headerImageObj = $headerImageRepository->findByImageName($fileName);
    			//checking image exist in folder but not used in DB
    			if( $headerImageObj == '0') { 
    				if (is_file($image)) {
    					if (unlink($image)) {
    						$output->writeln('Image deleted for : '.$image, true);
    						$cnt++;
    					} else {
    						$output->writeln('Problem in deleting image : '.$image, true);
    					}
    				}
    			}
    		}
    	}
    	$output->writeln('Total '.$cnt.' Header Images deleted', true);
    	$output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
    	$output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    
}
