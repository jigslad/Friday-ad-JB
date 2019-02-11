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

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateBusinessUsersCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:business-users')
        ->setDescription("Update Migrated business user's category and package")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:update:business-users
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
        
        /*
        if (file_exists(__DIR__."/user_update.sql")) {
        	unlink(__DIR__."/user_update.sql");
        	echo 'removed'.__DIR__."/user_update.sql"."\n";
        }*/

        //get options passed in command
        $offset   = $input->getOption('offset');

        $searchParam = array();

        if (isset($offset)) {
            $this->updateDimensionWithOffset($searchParam, $input, $output);
        } else {
            $this->updateDimension($searchParam, $input, $output);
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimensionWithOffset($searchParam, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getUserQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $users = $qb->getQuery()->getResult();
        $em  = $this->getContainer()->get('doctrine')->getManager();
        $handle = fopen(__DIR__."/user_update.sql", "a+");
        $user_update = 'SET foreign_key_checks = 0; SET AUTOCOMMIT = 0;'."\n";
        foreach ($users as $user) {
            $cat = $this->getLastetAdMainCategoryByUser($user->getId());
            $user_site = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user));

            if (!$user_site) {
                $user_site = new UserSite();
                $user_site->setUser($user);
            }

            if (!$cat) {
                echo $user->getId().$user->getRole()."\n";
            } else {
                if ($cat['0']['id']) {
                    if ($cat['0']['id'] == CategoryRepository::SERVICES_ID || $cat['0']['id'] == CategoryRepository::ADULT_ID) {
                        $user_update .= 'UPDATE user SET business_category_id = "'.$cat['0']['id'].'" WHERE id='.$user->getId().';'."\n";
                        $user_update .= 'UPDATE user_site SET profile_exposure_category_id = "'.$cat['1'].'" WHERE user_id='.$user->getId().';'."\n";
                    } else {
                        $user_update .= 'UPDATE user SET business_category_id = "'.$cat['0']['id'].'" WHERE id='.$user->getId().';'."\n";
                    }
                }
            }
            
            $this->em->persist($user_site);
            $userPackage =  $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackageDetail($user->getId());
            if (!$userPackage) {
                $package     = $this->em->getRepository('FaPromotionBundle:Package')->getFreeShopPackageByCategory($cat['0']['id']);
                if ($package) {
                    echo "Assign package to".$user->getId()."\n";
                    $this->assignPackageToUser($user, $package, 'migrated_package');
                }
            } else {
                echo "Already package is assigend to".$user->getId()."\n";
            }
        }

        $user_update .= 'SET foreign_key_checks = 1; SET AUTOCOMMIT = 1;'."\n";
        fwrite($handle, $user_update);
        fclose($handle);
        
        $this->em->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    private function assignPackageToUser($user, $package, $remark)
    {
        $userPackage = new UserPackage();
        $userPackage->setUser($user);
        $userPackage->setPackage($package);
        $userPackage->setStatus('A');
        $userPackage->setExpiresAt(null);
        $userPackage->setRemark($remark);
        $this->em->persist($userPackage);
    }

    /**
     * Update dimension.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimension($searchParam, $input, $output)
    {
        $count     = $this->getUserCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:business-users '.$commandOptions.' ';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getUserQueryBuilder($searchParam)
    {
        $userRepository  = $this->em->getRepository('FaUserBundle:User');
        $qb = $userRepository->createQueryBuilder(UserRepository::ALIAS);
        $qb->andWhere(UserRepository::ALIAS.'.role IN (:role)');
        $qb->andWhere(UserRepository::ALIAS.'.update_type != :update_type OR '.UserRepository::ALIAS.'.update_type IS NULL');
        $qb->setParameter('role', array(RoleRepository::ROLE_BUSINESS_SELLER_ID, RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID));
        $qb->setParameter('update_type', 'business-ad');
        return $qb;
    }

    public function getLastetAdMainCategoryByUser($userId)
    {
        $category = array();

        $adRepository = $this->em->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->createQueryBuilder(AdRepository::ALIAS);
        $qb->andWhere(AdRepository::ALIAS.'.user = :user_id');
        $qb->setParameter('user_id', $userId);
        $qb->addOrderBy(AdRepository::ALIAS.'.published_at', 'DESC');
        $qb->setMaxResults(1);
        $ad = $qb->getQuery()->getResult();
        if ($ad) {
            if ($ad[0]->getCategory()) {
                $category[0] = $this->getFirstLevelParent($ad[0]->getCategory()->getId());
                $category[1] = $ad[0]->getCategory()->getId();

                return $category;
            }
        }
    }

    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getFirstLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->getContainer());
        return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById(key($cat), $this->getContainer());
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getUserCount($searchParam)
    {
        $qb = $this->getUserQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
