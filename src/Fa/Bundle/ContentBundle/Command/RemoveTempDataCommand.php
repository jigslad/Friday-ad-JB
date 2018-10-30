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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * This command is used to generate active ad site map.
 *
 * php app/console fa:remove:temp:data
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class RemoveTempDataCommand extends ContainerAwareCommand
{

  /**
   * Task options
   *
   * @var array
   */
    protected $taskOptions = array();

    /**
     *  Task arguments
     *
     * @var array
     */
    protected $taskArguments = array();

    /**
     * configure task
     *
     * @see sfTask
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('fa:remove:temp:data')
            ->setDescription("Remove temp data.")
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
            ->setHelp(
                <<<EOF
Cron: To be setup to generate active ad sitemap.

Actions:
- Generate active ad  sitemap.

Command:
 - php app/console fa:remove:temp:data

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
        $start_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        $output->writeln('');
        $this->removeData($output);
        $output->writeln('');
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
    }

    /**
     * Remove temporary data created before days
     *
     * @return void
     */
    public function removeData($output)
    {
        // remove temp data created before 1 day
        $this->removeTempImageData($output);
    }

    /**
     * Remove temp images created before last 1 day
     *
     * @return void
     */
    public function removeTempImageData($output)
    {
        $twoDaysBeforeTimestamp = strtotime(date('Y-m-d') . ' -2 day');
        $dir                    = new \DirectoryIterator($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/tmp');
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $fileModifiedTimestamp = strtotime(date('Y-m-d', filemtime($fileinfo->getPathname())));
                if ($fileModifiedTimestamp < $twoDaysBeforeTimestamp) {
                    $output->writeln($fileinfo->getFilename().' was removed. ('.date('d-m-Y', filemtime($fileinfo->getPathname())).')');
                    unlink($fileinfo->getPathname());
                } else {
                    $output->writeln('');
                    $output->writeln($fileinfo->getFilename().' was NOT removed. ('.date('d-m-Y', filemtime($fileinfo->getPathname())).')');
                    $output->writeln('');
                }
            }
        }
    }
}
