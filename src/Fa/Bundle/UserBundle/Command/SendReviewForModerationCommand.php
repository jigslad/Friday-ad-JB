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
use Fa\Bundle\UserBundle\Repository\UserReviewRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to send ad to moderation.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SendReviewForModerationCommand extends ContainerAwareCommand
{
    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:review-for-moderation')
        ->setDescription("Read moderation queue and send review for moderation")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('reviewId', null, InputOption::VALUE_OPTIONAL, 'Review ids', null)
        ->addOption('isForManualModeration', null, InputOption::VALUE_OPTIONAL, 'Whether ad requires manual moderation or not', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Send review for moderation

Command:
 - php app/console fa:send:review-for-moderation
 - php app/console fa:send:review-for-moderation --reviewId="xxxx"
 - php app/console fa:send:review-for-moderation --reviewId="xxxx" --isForManualModeration='true'

EOF
        );
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searchParam = array();

        //get options passed in command
        $reviewIds              = $input->getOption('reviewId');
        $isForManualModeration  = $input->getOption('isForManualModeration');
        $offset                 = $input->getOption('offset');

        if (!$isForManualModeration) {
            $isForManualModeration = false;
        }

        if ($reviewIds) {
            $reviewIds = explode(',', $reviewIds);
            $reviewIds = array_map('trim', $reviewIds);
            $searchParam['user_review']['id'] = $reviewIds;
        } else {
            $reviewIds = null;
            $searchParam['user_review']['status'] = UserReviewRepository::MODERATION_QUEUE_STATUS_SEND;
        }

        if (isset($offset)) {
            $this->sendReviewForModerationWithOffset($searchParam, $input, $output, $isForManualModeration);
        } else {
            $this->sendReviewForModeration($searchParam, $input, $output, $isForManualModeration);
        }
    }

    /**
     * Send ad for moderation with given offset.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     */
    protected function sendReviewForModerationWithOffset($searchParam, $input, $output, $isForManualModeration)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getUserReviewQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $userReviews = $qb->getQuery()->getResult();

        foreach ($userReviews as $userReview) {
            $buildRequest      = $this->getContainer()->get('fa_user.review_moderation.request_build');
            $moderationRequest = $buildRequest->init($userReview, $isForManualModeration);

            print_r($moderationRequest);

            $moderationRequest = json_encode($moderationRequest);

            //echo "\n";
            //echo $moderationRequest;
            //echo "\n";
            //exit;

            if ($buildRequest->sendRequest($moderationRequest)) {
                $userReview->setStatus(UserReviewRepository::MODERATION_QUEUE_STATUS_SENT);
                $this->getContainer()->get('doctrine')->getManager()->persist($userReview);
            }

        }

        $this->getContainer()->get('doctrine')->getManager()->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send ad for moderation.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     * @param object $manualModerationReason
     */
    protected function sendReviewForModeration($searchParam, $input, $output, $isForManualModeration)
    {
        $count     = $this->getUserReviewCount($searchParam);
        $step      = 1000;
        $stat_time = time();

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:send:review-for-moderation '.$commandOptions;
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
     * Get query builder for ad moderate.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getUserReviewQueryBuilder($searchParam)
    {
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $userReviewRepository  = $entityManager->getRepository('FaUserBundle:UserReview');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('user_review' => array ('created_at' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($userReviewRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get count for Ad to be moderated.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getUserReviewCount($searchParam)
    {
        $qb = $this->getUserReviewQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
