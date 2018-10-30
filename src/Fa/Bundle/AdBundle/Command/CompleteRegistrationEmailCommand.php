<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\PaaLiteEmailNotificationRepository;

/**
 * This command is used to send 7 days after an ad first expires if the user has not reposted the ad and it is still inactive.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CompleteRegistrationEmailCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:complete-registration-mail')
        ->setDescription("This email will sent to people who silently registered with their email address, 10 minutes after an ad is placed via a PAA Lite form")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run continously.

Actions:
- Update mail sent status.

Command:
 - php app/console fa:send:complete-registration-mail"
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
        $this->em = $this->getContainer()->get('doctrine')->getManager();
       
        //get options passed in command
        $offset   = $input->getOption('offset');

        if (isset($offset)) {
            $this->completeRegistrationEmailsWithOffset($input, $output);
        } else {
            $this->completeRegistrationEmails($input, $output);

            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function completeRegistrationEmailsWithOffset($input, $output)
    {
        $records          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = 0;
        $container = $this->getContainer();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityCache =   $container->get('fa.entity.cache.manager');

        foreach ($records as $record) {
            $user = $this->em->getRepository('FaUserBundle:User')->find($record['user_id']);
            $paaLiteEmailNotification = $this->em->getRepository('FaAdBundle:PaaLiteEmailNotification')->find($record['id']);
            $text_package_name = '';
            $text_lowest_category_package_price = '';
            $url_ad_upsell = '';
            //send email only if ad has user and status is active and not feed ad.
            if ($user && CommonManager::checkSendEmailToUser($user, $this->getContainer())) {
                $encryption_key = $container->getParameter('reset_password_encryption_key');
                $resetPasswordLink = $container->get('router')->generate('reset_password', array('id' => CommonManager::encryptDecrypt($encryption_key, $user->getId()), 'key' => $user->getEncryptedKey(), 'mail_time' => CommonManager::encryptDecrypt($encryption_key, time())), true);

                $parameters = array(
                    'user_first_name' => $user->getFirstName()?$user->getFirstName():$user->getUserName(),
                    'url_password_reset' => $resetPasswordLink,
                    'url_account_dashboard' => $container->get('router')->generate('dashboard_home', array(), true),
                );

                $container->get('fa.mail.manager')->send($user->getEmail(), 'complete_registration', $parameters, CommonManager::getCurrentCulture($container));
                $paaLiteEmailNotification->setIsRegisteredMailSent(1);
                $this->em->persist($paaLiteEmailNotification);
                $this->em->flush($paaLiteEmailNotification);
                $output->writeln('Complete Registration mail sent to User Id:'.($user ? $user->getId() : null), true);
            }
        }
    }
    
    /**
     * Get main image thumb url from ad.
     *
     * @param object $ad
     * @param object $container
     *
     * @return string
     */
    public function getMainImageThumbUrlFromAd($ad, $container)
    {
        //image url
        $adMainPhoto = null;
        if ($url = $this->em->getRepository('FaAdBundle:AdImage')->getImageUrl($ad, '300X225', 1, $container)) {
            $adMainPhoto = $container->getParameter('fa.url.scheme').":".$url;
        } else {
            $adMainPhoto = $container->getParameter('fa.url.scheme').":".$container->getParameter('fa.static.url').'/fafrontend/images/no-image-grey.png';
        }
        return $adMainPhoto;
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function completeRegistrationEmails($input, $output)
    {
        $resultArr     = $this->getAdQueryBuilder(true, $input);
        $count  = $resultArr[0]['cnt'];

        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total users : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:complete-registration-mail '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get query builder for ads.
     * @param boolean $onlyCount count only.
     * @param array $input input parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        if ($onlyCount) {
            $sql = 'SELECT count(id) as cnt ';
        } else {
            $sql = 'SELECT * ';
        }

        $sql .= ' FROM paa_lite_email_notification as '.PaaLiteEmailNotificationRepository::ALIAS.' WHERE UNIX_TIMESTAMP(date_add(FROM_UNIXTIME('.PaaLiteEmailNotificationRepository::ALIAS.'.created_at), interval +10 minute)) <= UNIX_TIMESTAMP(NOW()) AND '.PaaLiteEmailNotificationRepository::ALIAS.'.is_paa_lite_registered_user = 1 AND '.PaaLiteEmailNotificationRepository::ALIAS.'.is_registered_mail_sent = 0 ORDER BY '.PaaLiteEmailNotificationRepository::ALIAS.'.id ASC';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $arrResult = $stmt->fetchAll();
        return $arrResult;
    }
}
