<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Date;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Entity\UserSiteViewCounter;

/**
 * This command is used for update counter.
 * php app/console fa:update:counter UserViewCounter
 * php app/console fa:update:counter UserViewCounter --date=2015-01-01
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserViewCounterCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:user-view-counter')
        ->setDescription("Update user view counter hourly from cache to database and remove cache")
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date in YYYY-MM-DD format', '')
        ->setHelp(
            <<<EOF
Cron: To be setup hourly.

Actions:
- Update user view counter from cache to database.
- Update user view counter every hour if date is not passed.
- Update user view counter for particular date, if date is given.

Command:
 - php app/console fa:update:user-view-counter
 - php app/console fa:update:user-view-counter --date="YYYY-mm-dd"
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
        $this->updateUserViewCounter($input, $output);
    }

    /**
     * Update user view counter from redis cache.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function updateUserViewCounter($input, $output)
    {
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        $em   = $this->getContainer()->get('doctrine')->getManager();
        $date = $input->getOption('date');

        if ($date) {
            $date = strtotime($date);
        } else {
            $date = strtotime(date('Y-m-d'));
        }

        $userCounterKeys = $this->getContainer()->get('fa.cache.manager')->keys('*user_view_counter_'.$date.'_*');

        if (count($userCounterKeys)) {
            foreach ($userCounterKeys as $userCounterKey) {
                $userCounterKey     = str_replace($this->getContainer()->getParameter('fa.cache.key'), '', $userCounterKey);
                $explodeRes         = explode('_', $userCounterKey);
                $userId             = end($explodeRes);
                $userViewCounter    = CommonManager::getCacheCounter($this->getContainer(), $userCounterKey);
                $userViewCounterObj = $em->getRepository('FaUserBundle:UserSiteViewCounter')->findOneBy(array('user' => $userId, 'created_at' => $date));
                $userSiteObj        = $em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));

                if (!$userViewCounterObj) {
                    $userViewCounterObj = new UserSiteViewCounter();
                    $userViewCounterObj->setUser($em->getReference('FaUserBundle:User', $userId));
                }

                if ($userSiteObj) {
                    $userViewCounterObj->setUserSite($userSiteObj);
                }

                $userViewCounterObj->setHits($userViewCounterObj->getHits() + $userViewCounter);
                $userViewCounterObj->setCreatedAt($date);
                $em->persist($userViewCounterObj);

                $output->writeln('Counter updated for user id: '.$userId);

                // Remove from counter cache for this user.
                CommonManager::removeCachePattern($this->getContainer(), $userCounterKey);
            }
            $em->flush();
        } else {
            $output->writeln('No user view counter found to update.');
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
