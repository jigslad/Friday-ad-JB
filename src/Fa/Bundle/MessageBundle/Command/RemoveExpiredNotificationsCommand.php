<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\MessageBundle\Repository\NotificationMessageEventRepository;

/**
 * This command is used to delete expired notifications
 *
 * php app/console fa:recurring-subscription
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveExpiredNotificationsCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove-expired-notification')
        ->setDescription('Remove expired notification from database');
    }


    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        $QUERY_BATCH_SIZE = 1000;
        $done             = false;
        $last_id          = 0;

        while (!$done) {
            $notifications = $this->getExpiredNotifications($last_id, $QUERY_BATCH_SIZE);
            if ($notifications) {
                foreach ($notifications as $notification) {
                    $this->em->remove($notification);
                    echo 'Removed notification id -> '.$notification->getId()."\n";
                }
                $this->em->flush();

                $last_id = $notification->getId();

            } else {
                $done = true;
            }

            $this->em->flush();
            $this->em->clear();
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get expired notifications.
     *
     * @param integer $last_id           Last id.
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getExpiredNotifications($last_id, $QUERY_BATCH_SIZE)
    {
        $q = $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->createQueryBuilder(NotificationMessageEventRepository::ALIAS);
        $q->andWhere(NotificationMessageEventRepository::ALIAS.'.expires_at < :expires_at');
        $q->setParameter('expires_at', time());
        $q->addOrderBy(NotificationMessageEventRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);
        return $q->getQuery()->getResult();
    }
}
