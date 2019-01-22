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
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to renew subscription packages through recurring payment
 *
 * php app/console fa:auto-renew-subscription
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version v1.0
 */
class AutoRenewSubscriptionCommand extends ContainerAwareCommand
{
    
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 200;
    
    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;
    
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:auto-renew-subscription')
        ->setDescription('Auto Renew subscription')
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'user id', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null);
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

        $offset   = $input->getOption('offset');
        
        if (isset($offset)) {
            $this->autoRenewSubscriptionWithOffset($input, $output);
        } else {
            $this->autoRenewSubscription($input, $output);
        }
    }

    /**
     * Auto Renew Subscription for user with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function autoRenewSubscriptionWithOffset($searchParam, $input, $output)
    {
        $offset  = $input->getOption('offset');
        $userId  = $input->getOption('user_id');
        $userPackages = $this->getUserActiveAdResult($userId, $offset, $this->limit);

        if (!empty($userPackages)) {
            foreach ($userPackages as $userPackage) {
                $user = $userPackage->getUser();
                $userStatus = $this->em->getRepository('FaUserBundle:User')->getUserStatus($user->getId(), $this->getContainer());
                if ($userStatus === EntityRepository::USER_STATUS_ACTIVE_ID) {
                    $package = $userPackage->getPackage();                   
                    $this->em->getRepository('FaUserBundle:UserPackage')->assignPackageToUser($user, $package, 'auto-renew-package-backend', null, 1, $this->getContainer());
                    
                }
            }
        } 
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * Auto Renew Subscription for user.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function autoRenewSubscription($input, $output)
    {
        $user_id   = $input->getOption('user_id');
        $count     = $this->getUserSubscriptionsCount($user_id);
        $step      = 200;
        $start_time = time();
        $returnVar = null;

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        $output->writeln('total users : '.$count, true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:auto-renew-subscription '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
    }

    /**
     * Get query builder for user subscriptions.
     *
     * @param integer $userId User id.
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return object.
     */
    protected function getUserSubscriptionsResult($user_id, $offset, $limit)
    {
        $q = $this->em->getRepository('FaUserBundle:UserPackage')->createQueryBuilder(UserPackageRepository::ALIAS);
        $q->andWhere(UserPackageRepository::ALIAS.'.status = :status');
        $q->setParameter('status', 'A');
        $q->andWhere(UserPackageRepository::ALIAS.'.is_auto_renew = :is_auto_renew');
        $q->setParameter('is_auto_renew', '1');
        
        $q->andWhere(UserPackageRepository::ALIAS.'.created_at > :created_at_from and '.UserPackageRepository::ALIAS.'.created_at < :created_at_to');
        $q->setParameter('created_at_from', strtotime(date('d/m/Y 00:00:00')));
        $q->setParameter('created_at_to', strtotime(date('d/m/Y 11:59:59')));
        
        $q->addOrderBy(UserPackageRepository::ALIAS.'.id');
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);
        
        if ($user_id != '') {
            $q->andWhere(UserPackageRepository::ALIAS.'.user = :user');
            $q->setParameter('user', $user_id);
        }
        
        return $q->getQuery()->getResult();
    }  
    
    
    /**
     * Get query builder for user subscriptions count.
     *
     * @param integer $userId User id.
     *
     * @return integer.
     */
    protected function getUserSubscriptionsCount($user_id)
    {
        $q = $this->em->getRepository('FaUserBundle:UserPackage')->getBaseQueryBuilder();
        $q->select('COUNT('.UserPackageRepository::ALIAS.'.id) as user_count');
        $q->andWhere(UserPackageRepository::ALIAS.'.status = :status');
        $q->setParameter('status', 'A');
        $q->andWhere(UserPackageRepository::ALIAS.'.is_auto_renew = :is_auto_renew');
        $q->setParameter('is_auto_renew', '1');
             
        $q->andWhere((UserPackageRepository::ALIAS.'.updated_at > '.UserPackageRepository::ALIAS.'.created_at and '.UserPackageRepository::ALIAS.'.updated_at > :created_at_from and '.UserPackageRepository::ALIAS.'.updated_at < :created_at_to) or ('.UserPackageRepository::ALIAS.'.created_at > :created_at_from and '.UserPackageRepository::ALIAS.'.created_at < :created_at_to'));
        $q->setParameter('created_at_from', strtotime(date('d/m/Y 00:00:00')));
        $q->setParameter('created_at_to', strtotime(date('d/m/Y 11:59:59')));
        
        if ($user_id != '') {
            $q->andWhere(UserPackageRepository::ALIAS.'.user = :user');
            $q->setParameter('user', $user_id);
        }
        
        
        return $q->getQuery()->getSingleScalarResult();
    } 
   
}

