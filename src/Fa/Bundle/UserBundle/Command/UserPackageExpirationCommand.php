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
use Fa\Bundle\UserBundle\Repository\UserPackageRepository;
use Fa\Bundle\PaymentBundle\Entity\Payment;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository;
use Fa\Bundle\PaymentBundle\Entity\PaymentCyberSource;
use Fa\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Fa\Bundle\PaymentBundle\Entity\PaymentTransactionDetail;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentTransactionDetailRepository;
use Fa\Bundle\PaymentBundle\Repository\TransactionDetailRepository;

/**
 * This command is used to renew subscription packages through recurring payment
 *
 * php app/console fa:recurring-subscription
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageExpirationCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:user-package-expiration')
        ->setDescription('Subscription package expiration')
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'user id', null);
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $QUERY_BATCH_SIZE = 1000;
        $done             = false;
        $last_id          = 0;
        $user_id          = $input->getOption('user_id');

        while (!$done) {
            $userPackages = $this->getUserSubscriptions($last_id, $QUERY_BATCH_SIZE, $user_id);
            if ($userPackages) {
                foreach ($userPackages as $userPackage) {
                    $user    = $userPackage->getUser();
                    $package = $userPackage->getPackage();
                    echo 'Subscription package expired for user -> '.$user->getId()."\n";
                    $this->em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, 'downgraded-to-free-package', $this->getContainer());
                    
                    //unboost ads in DB
                    $this->em->getRepository('FaAdBundle:Ad')->unboostAdByUserId($user->getId(), 0);
                    $this->em->getRepository('FaAdBundle:BoostedAd')->unboostAdByUserId($user->getId());

                    //unboost in solr
                    $memoryLimit = '';
                    if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                        $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
                    }
                    $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-solr-index update --status="A,S,E" --user_id="'.$user->getId().'" >/dev/null & ';
                    $output->writeln($command, true);
                    passthru($command, $returnVar);

                    if ($returnVar !== 0) {
                        $output->writeln('Error occurred during subtask', true);
                    }
                }

                $last_id = $userPackage->getId();
            } else {
                $done = true;
            }

            $this->em->flush();
            $this->em->clear();
        }
    }

    /**
     * Get images.
     *
     * @param integer $last_id           Last id.
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getUserSubscriptions($last_id, $QUERY_BATCH_SIZE, $user_id)
    {
        $q = $this->em->getRepository('FaUserBundle:UserPackage')->createQueryBuilder(UserPackageRepository::ALIAS);
        $q->andWhere(UserPackageRepository::ALIAS.'.status = :status');
        $q->setParameter('status', 'A');
        $q->andWhere(UserPackageRepository::ALIAS.'.id > :id');
        $q->andWhere(UserPackageRepository::ALIAS.'.expires_at < :expires_at');
        $q->setParameter('id', $last_id);
        $q->setParameter('expires_at', time());
        $q->addOrderBy(UserPackageRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);

        if ($user_id != '') {
            $q->andWhere(UserPackageRepository::ALIAS.'.user = :user');
            $q->setParameter('user', $user_id);
        }

        return $q->getQuery()->getResult();
    }
}
