<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
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
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used Send email at Monday 9am to anyone with a live advert which has not already been booked into a print product and is within the print location group.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CheckEmailProblemCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:check-email-problem')
        ->setDescription("Send email at Monday 9am to anyone with a live advert which has not already been booked into a print product and is within the print location group.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Send email at Monday 9am to anyone with a live advert which has not already been booked into a print product and is within the print location group.

Command:
 - php app/console fa:check-email-problem
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
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $ads = array('14509742' => 'samiram@aspl.in',
                    '14509735' =>  'janak@aspl.in',
                    '14509726' => 'sagar@aspl.in',
                    '14509765' => 'jarno@fiare.fi',
                    '14509649' => 'amitl@aspl.in',
                    '14509656' => 'samiram@aspl.in',
                    '14509758' => 'mohitc@aspl.in',
                    '14509651' => 'sagar@aspl.in'
            );

        $users = array('samiram@aspl.in' => 'Samir',
            'janak@aspl.in' => 'Janak',
            'sagar@aspl.in' => 'Sagar',
            'jarno@fiare.fi' => 'Jarno',
            'amitl@aspl.in' => 'Amit',
            'samiram@aspl.in' => 'Samir',
            'mohitc@aspl.in' => 'Mohit',
            'sagar@aspl.in' => 'Sagar'
        );

        /*$ads = array('14509742' => 'samiram@aum203.aum.com',
            '14509735' =>  'janak@aum203.aum.com',
            '14509726' => 'sagar@aum203.aum.com',
            '14509765' => 'jarno@fiare.fi',
            '14509649' => 'amitl@aum203.aum.com',
            '14509656' => 'samiram@aum203.aum.com',
            '14509758' => 'mohitc@aum203.aum.com',
            '14509651' => 'sagar@aum203.aum.com'
        );

        $users = array('samiram@aum203.aum.com' => 'Samir',
                    'janak@aum203.aum.com' => 'Janak',
                    'sagar@aum203.aum.com' => 'Sagar',
                    'jarno@fiare.fi' => 'Jarno',
                    'amitl@aum203.aum.com' => 'Amit',
                    'samiram@aum203.aum.com' => 'Samir',
                    'mohitc@aum203.aum.com' => 'Mohit',
                    'sagar@aum203.aum.com' => 'Sagar'
        );*/

        $output->writeln('Total ads : '.count($ads), true);

        foreach ($ads as $adId => $email) {
            $container   = $this->getContainer();
            $entityCache = $container->get('fa.entity.cache.manager');
            $ad          = $entityManager->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
            $adMainPhoto = $container->getParameter('fa.url.scheme').":".$container->getParameter('fa.static.url').'/fafrontend/images/no-image-grey.png';

            $parameters  = array(
                'user_first_name'     => $users[$email],
                'user_last_name'      => 'User',
                'text_ad_title'       => $ad->getTitle(),
                'text_ad_category'    => $entityCache->getEntityNameById('FaEntityBundle:Category', $ad->getCategory()->getId()),
                'text_ad_description' => $ad->getDescription(),
                'url_ad_main_photo'   => $adMainPhoto,
                'text_ad_views'       => 10,
                'text_ad_enquiries'   => 5,
                'url_ad_upsell'       => $container->get('router')->generate('ad_promote', array('adId' => $ad->getId(), 'type' => 'promote'), true),
                'url_ad_edit'         => $container->get('router')->generate('ad_edit', array('id' => $ad->getId()), true),
                'url_ad_mark_sold'    => $container->get('router')->generate('manage_my_ads_mark_as_sold', array('adId' => $ad->getId()), true),
                'url_ad_view'         => $container->get('router')->generate('ad_detail_page_by_id', array('id' => $ad->getId()), true)
            );

            $container->get('fa.mail.manager')->send($email, 'print_your_ad_upsell', $parameters, CommonManager::getCurrentCulture($container));

            $output->writeln('Email has been sent for ad id: '.$ad->getId(). ' to '. $email, true);
        }


        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
