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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;

/**
 * This command is used to insert paa field rule.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class InsertPaaFieldRuleCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:insert:paa-filed-rule')
        ->setDescription("Insert paa field rule.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to insert paa field rule.

Command:
 - php app/console fa:insert:paa-filed-rule
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
        // set entity manager.
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->mainDbName    = $this->getContainer()->getParameter('database_name');


        //get arguments passed in command
        $offset = $input->getOption('offset');

        // insert ads statistics.
        if (isset($offset)) {
            $this->insertPaaFieldRuleWithOffset($input, $output);
        } else {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);

            $this->insertPaaFieldRule($input, $output);

            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Insert missing paa field rule.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function insertPaaFieldRule($input, $output)
    {
        $count  = $this->getPaaFieldRuleCount();

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:insert:paa-filed-rule '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Insert missing paa field rule.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function insertPaaFieldRuleWithOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $paaFieldRules = $this->getPaaFieldRuleResult($offset, $this->limit);
        $paaFieldRuleRepository = $this->entityManager->getRepository('FaAdBundle:PaaFieldRule');

        foreach ($paaFieldRules as $paaFieldRule) {
            $paaFieldRulePhotoObj = $paaFieldRuleRepository->findOneBy(array('paa_field' => 229, 'category' => $paaFieldRule['category_id']));
            if (!$paaFieldRulePhotoObj) {
                $this->addPaaFieldRule($output, 'video', $paaFieldRule['category_id']);
            }
        }
    }

    /**
     * Get query builder for paa field rule.
     *
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getPaaFieldRuleCount($searchParams = array())
    {
        $sql = 'SELECT COUNT(id) as total_paa_field_rule FROM '.$this->mainDbName.'.paa_field_rule WHERE paa_field_id = 1';

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        $res = $stmt->fetch();

        return $res['total_paa_field_rule'];
    }

    /**
     * Get query builder for paa field rule results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getPaaFieldRuleResult($offset, $limit, $searchParam = array())
    {
        $sql = 'SELECT * FROM '.$this->mainDbName.'.paa_field_rule WHERE paa_field_id = 1 LIMIT '.$limit.' OFFSET '.$offset;

        $stmt = $this->executeRawQuery($sql, $this->entityManager);
        return $stmt->fetchAll();
    }

    /**
     * Add paa field rule.
     */
    public function addPaaFieldRule($output, $type, $categoryId)
    {
        $updateSql = 'update '.$this->mainDbName.'.paa_field_rule
            set ord = (ord + 1) where ord > 1 and category_id = '.$categoryId;
        $this->executeRawQuery($updateSql, $this->entityManager);

        $insertSql = 'insert into '.$this->mainDbName.'.paa_field_rule
            (paa_field_id, category_id, label, status, help_text, ord, step, is_toggle) values
            (229, "'.$categoryId.'", "YouTube video", 1, "Adding a video which shows how your item or service can be used can really make your advert stand out. Simply copy a YouTube link from your browser and paste it in the text box below.", 2, 4, 0)';
        $this->executeRawQuery($insertSql, $this->entityManager);
        $output->writeln('Paa field rule inserted for category id: '.$categoryId, true);
    }
}
