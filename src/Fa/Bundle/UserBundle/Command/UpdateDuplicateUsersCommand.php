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
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\UserBundle\Entity\UserPackage;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\UserBundle\Encoder\Pbkdf2PasswordEncoder;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateDuplicateUsersCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:duplicate-users')
        ->setDescription("Update Migrated tamsin user's category and package")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:update:duplicate-users
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
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        $offset   = $input->getOption('offset');

        if (isset($offset)) {
            $this->insertTasminUserOffset($input, $output);
        } else {
            $this->insertTasminUser($input, $output);
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function insertTasminUserOffset($input, $output)
    {
        $offset      = $input->getOption('offset');

        $reader = new \EasyCSV\Reader(__DIR__."/user_duplicate.csv");
        $reader->setDelimiter(',');
        $batchSize = 1000;
        $row = 0;
        $ad_id = array();
        $category = array();
        $row = $reader->getRow();

        if ($offset > 0) {
            $reader->advanceTo($offset-1);
        } else {
            $reader->advanceTo(1);
        }

        while (($row = $reader->getRow()) && $reader->getLineNumber() != $offset + $batchSize) {
            if (isset($row['EMAIL']) && $row['EMAIL']) {
                $email = $row['EMAIL'];
                //echo $email."\n;";

                $users = $this->getUsers($email);

                if ($users) {
                    $user_with_ad = array();
                    $user_ids = array();
                    foreach ($users as $user) {
                        if ($user->getTotalAd() > 0) {
                            $user_with_ad[] = $user->getId();
                        }

                        $user_ids[$user->getId()] = $user->getId();
                        //echo $user->getTotalAd().'#'.$user->getId().'#'.$user->getEmail()."\n";
                    }


                    if (count($user_with_ad) > 1) {
                        $users = $this->getUsersByLastPaa($email);
                        $user_ids = array();
                        foreach ($users as $user) {
                            $user_ids[$user->getId()] = $user->getId();
                        }
                        $keep_id = array_pop($user_ids);
                    } elseif (count($user_with_ad) == 1) {
                        $keep_id = $user_with_ad[0];
                        unset($user_ids[$keep_id]);
                    } else {
                        $keep_id = array_pop($user_ids);
                        $user_ids;
                    }

                    if ($keep_id) {
                        $keep_user = $this->em->getRepository('FaUserBundle:User')->find($keep_id);
                    }

                    if ($keep_user) {
                        echo 'Keeped user account'.$keep_user->getId().'##'.'###'.$keep_user->getEmail()."\n";
                    }

                    if ($keep_user) {
                        $this->em->detach($keep_user);
                    }

                    $j = 0;
                    $remove_user = null;
                    foreach ($user_ids as $user_id) {
                        $remove_user = $this->em->getRepository('FaUserBundle:User')->find($user_id);
                        $prefix = null;
                        if ($remove_user) {
                            $prefix = 'DUPLI#'.$j.
                            var_dump($prefix.$email);
                            $remove_user->setUserName($prefix.$email);
                            $remove_user->setEmail($prefix.$email);
                            $remove_user->setUpdateType('duplicate_removal');
                            $this->em->persist($remove_user);
                            echo 'duplicate user account'.$remove_user->getId().'##'.'###'.$remove_user->getEmail()."\n";
                            $j++;
                        }
                    }

                    $this->em->flush();


                    if ($remove_user) {
                        $this->em->detach($remove_user);
                    }

                    if ($user) {
                        $this->em->detach($user);
                    }

                    $this->em->clear();

                    /*
                    $emailConstraint = new EmailConstraint();
                    $emailConstraint->message = 'In valid email';
                    $errors = $this->getContainer()->get('validator')->validateValue($email, $emailConstraint);
                    */
                    //$users = $this->em->getRepository('FaUserBundle:User')->findBy(array('email' => $email));
                }
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Load user by username.
     *
     * @param $username
     */
    public function getUsersByLastPaa($email)
    {
        $q = $this->em->getRepository('FaUserBundle:User')->createQueryBuilder(UserRepository::ALIAS);
        $q->where('u.email = :email');
        $q->setParameter('email', $email);
        $q->addOrderBy(UserRepository::ALIAS.'.last_paa', 'ASC');
        $users = $q->getQuery()->getResult();
        return $users;
    }

    /**
     * Load user by username.
     *
     * @param $username
     */
    public function getUsers($email)
    {
        $q = $this->em->getRepository('FaUserBundle:User')->createQueryBuilder(UserRepository::ALIAS);
        $q->where('u.email = :email');
        $q->setParameter('email', $email);
        $q->addOrderBy(UserRepository::ALIAS.'.created_at', 'ASC');
        $users = $q->getQuery()->getResult();
        return $users;
    }


    /**
     * Update dimension.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function insertTasminUser($input, $output)
    {
        $reader = new \EasyCSV\Reader(__DIR__."/user_duplicate.csv");
        $reader->setDelimiter(',');
        $count     = $reader->getLastLineNumber();
        $step      = 1000;
        $stat_time = time();
        $returnVar = null;

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:duplicate-users '.$commandOptions.' ';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
