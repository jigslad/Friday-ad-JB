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
        ->addOption('renew_date', null, InputOption::VALUE_OPTIONAL, 'renew date', null)
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
        $this->mainDbName = $this->getContainer()->getParameter('database_name');
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
    protected function autoRenewSubscriptionWithOffset($input, $output)
    {
        $offset  = $input->getOption('offset');
        $userId  = $input->getOption('user_id');
        $userPackages = $this->getUserSubscriptionsResult($input, $offset, $this->limit);
        if (!empty($userPackages)) {            
            foreach ($userPackages as $userPackage) {                
                $userStatus = $this->em->getRepository('FaUserBundle:User')->getUserStatus($userPackage['user_id'], $this->getContainer());
                if ($userStatus === EntityRepository::USER_STATUS_ACTIVE_ID) {
                    $user = $this->em->getRepository('FaUserBundle:User')->find($userPackage['user_id']);
                    $package = $this->em->getRepository('FaPromotionBundle:Package')->find($userPackage['package_id']);
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
        $count     = $this->getUserSubscriptionsCount($input);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:auto-renew-subscription '.$commandOptions;
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
    protected function getUserSubscriptionsResult($input, $offset, $limit)
    {
        $user_id  = $input->getOption('user_id');
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $expiryDefaultDaysBeforeDate = strtotime('-28 day', strtotime(date('Y-m-d H:i:s')));
        $currentTime = $input->getOption('renew_date')?$input->getOption('renew_date'):date('Y-m-d');
                
        $querySql  = "SELECT * FROM ".$this->mainDbName.".user_package up WHERE up.status = 'A' AND up.is_auto_renew = 1";
        $querySql  .= " AND (date_format(up.expires_at,'%Y-%m-%d') = '".$currentTime."' or (CASE WHEN (up.updated_at > up.created_at) THEN ";
        $querySql  .= "(date_format(date_add(FROM_UNIXTIME(up.updated_at),INTERVAL 28 DAY),'%Y-%m-%d')  = '".$currentTime."') ELSE (date_format(date_add(FROM_UNIXTIME(up.created_at),INTERVAL 28 DAY),'%Y-%m-%d') = '".$currentTime."'";
        $querySql  .= ") END))";
        
        if ($user_id != '') {
            $querySql  .= " AND up.user_id=".$user_id;
        }
        
        $stmt = $entityManager->getConnection()->prepare($querySql);
        $stmt->execute();
        $user_packages = $stmt->fetchAll();
        return $user_packages;
    }
    
    /**
     * Get query builder for user subscriptions count.
     *
     * @param integer $userId User id.
     *
     * @return integer.
     */
    protected function getUserSubscriptionsCount($input)
    {   
        $user_id  = $input->getOption('user_id');
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $expiryDefaultDaysBeforeDate = strtotime('-28 day', strtotime(date('Y-m-d H:i:s')));
        $currentTime = $input->getOption('renew_date')?$input->getOption('renew_date'):date('Y-m-d');
                
        $querySql  = "SELECT count(up.user_id) as user_count FROM ".$this->mainDbName.".user_package up WHERE up.status = 'A' AND up.is_auto_renew = 1";
        // $querySql  .= " AND (up.expires_at >= ".$currentTime." or (CASE WHEN (up.updated_at > up.created_at) THEN ";
        // $querySql  .= "(up.updated_at  < ".$expiryDefaultDaysBeforeDate.") ELSE (up.created_at < ".$expiryDefaultDaysBeforeDate;
        $querySql  .= " AND (date_format(up.expires_at,'%Y-%m-%d') = '".$currentTime."' or (CASE WHEN (up.updated_at > up.created_at) THEN ";
        $querySql  .= "(date_format(date_add(FROM_UNIXTIME(up.updated_at),INTERVAL 28 DAY),'%Y-%m-%d')  = '".$currentTime."') ELSE (date_format(date_add(FROM_UNIXTIME(up.created_at),INTERVAL 28 DAY),'%Y-%m-%d') = '".$currentTime."'";
        $querySql  .= ") END))";
        
        if ($user_id != '') {
            $querySql  .= " AND up.user_id=".$user_id;
        }
        
        $stmt = $entityManager->getConnection()->prepare($querySql);
        $stmt->execute();
        $user_packages = $stmt->fetchAll();
        return $user_packages[0]['user_count'];
    }
}
