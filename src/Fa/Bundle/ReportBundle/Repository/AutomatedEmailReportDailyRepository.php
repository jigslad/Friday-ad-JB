<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Doctrine\ORM\QueryBuilder;
use Fa\Bundle\ReportBundle\Entity\AutomatedEmailReportDaily;
use Doctrine\ORM\Query;

/**
 * AutomatedEmailReportDailyRepository repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AutomatedEmailReportDailyRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'aerd';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get automated email report query
     *
     * @param array   $searchParams Search parameters array.
     * @param array   $sorter       Sort parameters array.
     * @param object  $container    Container object.
     *
     * @return array
     */
    public function getAutomatedEmailReportQuery($searchParams, $sorter, $container, $isCountQuery = false)
    {
        $qb = $this->createQueryBuilder(self::ALIAS);
        if ($isCountQuery) {
            $qb->select('COUNT( DISTINCT '.self::ALIAS.'.identifier)');
        } else {
            $qb->select(self::ALIAS.'.identifier', 'SUM('.self::ALIAS.'.email_sent_counter) as total_email_sent_counter', 'SUM('.self::ALIAS.'.email_open_counter) as total_email_open_counter')
            ->groupBy(self::ALIAS.'.identifier');
        }

        $qb = $this->addFilter($qb, $searchParams, $sorter, $container, $isCountQuery);

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\ReportBundle\Walker\AutomatedEmailSqlWalker');
        $query->setHint("automatedEmailReport.count", true);

        return $query;
    }

    /**
     *
     * @param object  $qb           QueryBuilder object.
     * @param array   $searchParams Search parameters array.
     * @param array   $sorter       Sort parameters array.
     * @param object  $container    Container object.
     * @param boolean $isCountQuery Is count query flag.
     *
     * @return QueryBuilder
     */
    private function addFilter($qb, $searchParams, $sorter, $container, $isCountQuery = false)
    {
        $finalStartDate = CommonManager::getTimeStampFromStartDate($searchParams['from_date']);
        $finalEndDate = CommonManager::getTimeStampFromEndDate($searchParams['to_date']);
        $qb->andWhere('('.self::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');


        // filter for paid ads only.
        if (isset($searchParams['identifier']) && $searchParams['identifier']) {
            $qb->andWhere(self::ALIAS.'.identifier = \''.$searchParams['identifier'].'\'');
        }

        // sorting.
        if (!$isCountQuery) {
            $qb->orderBy('total_email_sent_counter', 'DESC');
        }

        return $qb;
    }

    /**
     * Update automated email counter.
     *
     * @param string  $identifier Email identifier.
     */
    public function updateAutomatedEmailCounter($identifier)
    {
        $date = strtotime(date('Y-m-d'));
        $automatedEmailCounterObj = $this->findOneBy(array('identifier' => $identifier, 'created_at' => $date));
        if (!$automatedEmailCounterObj) {
            $automatedEmailCounterObj = new AutomatedEmailReportDaily();
            $automatedEmailCounterObj->setIdentifier($identifier);
        }
        $automatedEmailCounterObj->setEmailSentCounter($automatedEmailCounterObj->getEmailSentCounter() + 1);
        $automatedEmailCounterObj->setCreatedAt($date);
        $this->_em->persist($automatedEmailCounterObj);
        $this->_em->flush();
    }

    /**
     * Update automated email counter.
     *
     * @param string  $identifier Email identifier.
     */
    public function updateAutomatedEmailReadCounter($identifier, $date)
    {
        $automatedEmailCounterObj = $this->findOneBy(array('identifier' => $identifier, 'created_at' => $date));
        if ($automatedEmailCounterObj) {
            $automatedEmailCounterObj->setEmailOpenCounter($automatedEmailCounterObj->getEmailOpenCounter() + 1);
            $this->_em->persist($automatedEmailCounterObj);
            $this->_em->flush();
        }
    }

    /**
     * Update automated email counter in redis.
     *
     * @param string    $identifier Email identifier.
     * @param container $container  Container object.
     *
     */
    public function updateAutomatedEmailCounterInRedis($identifier, $container)
    {
        $date = strtotime(date('Y-m-d'));
        CommonManager::updateCacheCounterUsingZIncr($container, 'automated_email_counter_'.$date, $identifier);
    }

    /**
     * Get ad report fields
     */
    public function getAdReportFields()
    {
        $adReportFields = array();
        $adReportFields['identifier'] = 'Template name';
        $adReportFields['total_email_sent_counter'] = 'Volumes sent';
        $adReportFields['total_email_open_counter'] = 'Volumes opened';

        return $adReportFields;
    }
}
