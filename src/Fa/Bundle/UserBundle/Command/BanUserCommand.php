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
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to ban user.
 *
 * php app/console fa:ban-user
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class BanUserCommand extends ContainerAwareCommand
{
    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:ban-user')
        ->setDescription("Ban user")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('file_name', null, InputOption::VALUE_REQUIRED, 'File name', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to add new job advert categories mapping.

Command:
 - php app/console fa:ban-user --file_name="Email_Ban_Rule_Values_241016_1.csv"
EOF
        );;
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set entity manager.
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->banUserOffset($input, $output);
        } else {
            $this->banUser($input, $output);
        }
    }

    /**
     * Ban user with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function banUserOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $fileName = $input->getOption('file_name');
        $userRepository = $this->entityManager->getRepository('FaUserBundle:User');

        $step = 20;
        $reader = new \EasyCSV\Reader(__DIR__."/".$fileName);
        $reader->setDelimiter(',');
        $reader->setHeaderLine(0);
        $row = $reader->getRow();
        if ($offset > 0)
            $reader->advanceTo($offset-1);
        else
            $reader->advanceTo(1);
        while (($row = $reader->getRow()) && $reader->getLineNumber() != $offset + $step) {
            if (isset($row['email'])) {
                if (preg_match("/@gmail\.co\.uk$/i", $row['email'], $matches) || preg_match("/@gmail\.com$/i", $row['email'], $matches)  || preg_match("/@googlemail\.com$/i", $row['email'], $matches) ) {
                    if (count($matches)) {
                        $email = str_replace($matches[0], '', $row['email']);
                        $email = str_replace('.', '', $email);
                        $users = $this->getGmailUsersWithEmail($email.$matches[0]);
                    }
                } else {
                    $users = $this->getUsersWithEmail($row['email']);
                }

                if (count($users)) {
                    foreach ($users as $user) {
                        $userObj = $userRepository->find($user['id']);
                        if ($userObj) {
                            $userObj->setStatus($this->entityManager->getReference('FaEntityBundle:Entity', EntityRepository::USER_STATUS_BLOCKED));
                            $this->entityManager->persist($userObj);
                            // inactive ad
                            $this->entityManager->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($user['id'], 1);
                            $this->entityManager->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($user['id'], $this->getContainer());
                            $output->writeln('User banned :'.$user['id'] , true);
                        }
                    }

                    $this->entityManager->flush();
                } else {
                    $output->writeln('User not banned :'.$row['email'] , true);
                }
            }
        }
        $this->entityManager->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    /**
     * Ban user.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function banUser($input, $output)
    {
        $fileName = $input->getOption('file_name');
        $reader = new \EasyCSV\Reader(__DIR__."/".$fileName);
        $reader->setDelimiter(',');
        $count     = $reader->getLastLineNumber();
        $step      = 20;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:ban-user '.$commandOptions.' ';
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
     * Get results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUsersWithEmail($email)
    {
        $sql = 'SELECT id FROM user WHERE email = :email and status_id = 52';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue("email", $email);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getGmailUsersWithEmail($email)
    {
        $sql = "SELECT id FROM user WHERE CONCAT( REPLACE( SUBSTRING( email, 1, LOCATE(  '@', email ) -1 ) ,  '.',  '' ) , SUBSTRING( email, LOCATE(  '@', email ) ) ) = :email and status_id = 52";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue("email", $email);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
