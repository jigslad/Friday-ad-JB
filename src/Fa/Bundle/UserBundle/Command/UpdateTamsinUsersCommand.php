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
class UpdateTamsinUsersCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:tamsin-users')
        ->setDescription("Update Migrated tamsin user's category and package")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:update:tamsin-users
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

        $reader = new \EasyCSV\Reader(__DIR__."/tamsin_users.csv");
        $reader->setDelimiter(',');
        $batchSize = 1000;
        $row = 0;
        $ad_id = array();
        $category = array();
        $row = $reader->getRow();

        if ($offset > 0)
            $reader->advanceTo($offset-1);
        else
            $reader->advanceTo(1);

        while (($row = $reader->getRow()) && $reader->getLineNumber() != $offset + $batchSize) {

            if (isset($row['EMAIL']) && $row['EMAIL']) {
                $email = $row['EMAIL'];
                $emailConstraint = new EmailConstraint();
                $emailConstraint->message = 'In valid email';
                $errors = $this->getContainer()->get('validator')->validateValue($email, $emailConstraint);

                if (count($errors) > 0) {
                    continue;
                } else {
                    $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $row['EMAIL']));

                    if (!$user || $user->getUpdateType() == 'tamsin_user') {
                        $flag = true;

                        if ($row['ADVERTISER_TYPE'] == 'T') {
                            $category = $this->getCategoryId($row['CATEGORY']);

                            if ($category) {
                                if (!$user) {
                                    $newUser = new User();

                                } else {
                                    $newUser = $user;
                                    $flag = false;
                                    $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_BUSINESS_SELLER));
                                    $newUser->removeRole($sellerRole);
                                    $this->em->persist($newUser);
                                }

                                $newUser->setUsername($row['EMAIL']);
                                $newUser->setEmail($row['EMAIL']);
                                $userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
                                $newUser->setStatus($userActiveStatus);
                                $password = substr(md5(rand(1, 1000)), 0, 8);
                                $encoder = new Pbkdf2PasswordEncoder();
                                $password = $encoder->encodePassword($password, '');
                                $newUser->setBusinessName($row['NAME']);

                                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_BUSINESS_SELLER));
                                $newUser->addRole($sellerRole);
                                $newUser->setRole($sellerRole);
                                $newUser->setPassword($password);
                                $newUser->setContactThroughEmail(1);
                                $newUser->setContactThroughPhone(0);
                                $newUser->setGuid(CommonManager::generateGuid($row['EMAIL']));
                                $newUser->setUpdateType('tamsin_user');

                                $newUser->setBusinessCategoryId($category);

                                $this->em->persist($newUser);
                                $this->em->flush();


                                $user_site = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $newUser));

                                if (!$user_site) {
                                    $user_site = new UserSite();
                                    $user_site->setUser($newUser);
                                }

                                $user_site->setStatus(1);
                                $this->em->persist($user_site);
                                $this->em->flush();

                                if ($user_site->getSlug() == '') {
                                    $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($newUser->getId(), $this->getContainer(), false);
                                }

                                $package = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($newUser);

                                if (!$package) {
                                    $this->em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($newUser, null, $this->getContainer(), false);
                                }

                                if ($newUser)
                                    $this->em->detach($sellerRole);

                                if ($user)
                                    $this->em->detach($user);

                                if ($newUser)
                                    $this->em->detach($newUser);
                                $this->em->clear();

                                if ($flag) {
                                    echo 'Tamsin trade user added '.$newUser->getId().' = '.$row['EMAIL']."\n";
                                } else {
                                    echo 'Tamsin trade user updated '.$newUser->getId().' = '.$row['EMAIL']."\n";
                                }
                            }
                        } else {
                            if (!$user) {
                                $newUser = new User();

                            } else {
                                $newUser = $user;
                                $flag = false;
                                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_SELLER));
                                $newUser->removeRole($sellerRole);
                                $this->em->persist($newUser);
                            }

                            $newUser->setUsername($row['EMAIL']);
                            $newUser->setEmail($row['EMAIL']);
                            $userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
                            $newUser->setStatus($userActiveStatus);
                            $password = substr(md5(rand(1, 1000)), 0, 8);
                            $encoder = new Pbkdf2PasswordEncoder();
                            $password = $encoder->encodePassword($password, '');

                            if (isset($row['FIRST_NAME']) && $row['FIRST_NAME'] != '' && isset($row['LAST_NAME']) && $row['LAST_NAME'] != '') {
                                $newUser->setFirstName($row['FIRST_NAME']);
                                $newUser->setLastName();
                            } else {
                                $newUser->setFirstName($row['NAME']);
                            }

                            $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_SELLER));
                            $newUser->addRole($sellerRole);
                            $newUser->setRole($sellerRole);
                            $newUser->setPassword($password);
                            $newUser->setUpdateType('tamsin_user');
                            $newUser->setContactThroughEmail(1);
                            $newUser->setContactThroughPhone(0);
                            $newUser->setGuid(CommonManager::generateGuid($row['EMAIL']));


                            $this->em->persist($newUser);
                            $this->em->flush();
                            if ($newUser)
                                $this->em->detach($sellerRole);

                            if ($user)
                            $this->em->detach($user);

                            if ($newUser)
                            $this->em->detach($newUser);
                            $this->em->clear();

                            if ($flag) {
                                echo 'Tamsin private user added '.$newUser->getId().' = '.$row['EMAIL']."\n";
                            } else {
                                echo 'Tamsin private user updated '.$newUser->getId().' = '.$row['EMAIL']."\n";
                            }
                        }
                    }
                    else {
                        //echo ' Already user ->'.$email."\n";
                        $this->em->detach($user);
                        $this->em->flush();
                        $this->em->clear();
                    }
                }
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    public function getCategoryId($id)
    {
        $category = array();

        $category['1'] = 2;
        $category['2'] = 2;
        $category['4'] = 2;
        $category['5'] = 2;
        $category['6'] = 500;
        $category['7'] = 2;
        $category['8'] = 585;
        $category['9'] = 678;
        $category['10'] = 585;
        $category['11'] = 585;
        $category['12'] = 585;
        $category['13'] = 725;
        $category['14'] = 725;
        $category['15'] = 725;
        $category['16'] = 444;
        $category['17'] = 444;
        $category['18'] = 444;
        $category['19'] = 2;
        $category['20'] = 2;
        $category['21'] = 2;
        $category['23'] = 2;
        $category['25'] = 2;
        $category['46'] = 783;
        $category['52'] = 444;
        $category['54'] = 3411;
        $category['55'] = 444;
        $category['95'] = 2;
        $category['99'] = 585;
        $category['100'] = 444;
        $category['101'] = 444;
        $category['105'] = 2;
        $category['120'] = 2;

        if (isset($category[$id])) {
            return $category[$id];
        }
    }


    /**
     * Update dimension.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function insertTasminUser($input, $output)
    {
        $reader = new \EasyCSV\Reader(__DIR__."/tamsin_users.csv");
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:tamsin-users '.$commandOptions.' ';
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
