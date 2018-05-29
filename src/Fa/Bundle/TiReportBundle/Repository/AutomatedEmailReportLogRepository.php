<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Doctrine\ORM\QueryBuilder;
use Fa\Bundle\TiReportBundle\Entity\AutomatedEmailReportLog;
use Doctrine\ORM\Query;

/**
 * AutomatedEmailReportLogRepository repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AutomatedEmailReportLogRepository extends EntityRepository
{
    /**
     * Update automated email counter.
     *
     * @param string  $identifier Email identifier.
     */
    public function updateEmailLog($identifier, $to)
    {
        $date = strtotime(date('Y-m-d'));
        $automatedEmailCounterObj = $this->findOneBy(array('identifier' => $identifier, 'created_at' => $date, 'email' => $to));
        if (!$automatedEmailCounterObj) {
            $automatedEmailCounterObj = new AutomatedEmailReportLog();
            $automatedEmailCounterObj->setIdentifier($identifier);
        }

        $automatedEmailCounterObj->setEmail($to);
        $automatedEmailCounterObj->setCreatedAt($date);
        $automatedEmailCounterObj->setIsOpened(0);
        $this->_em->persist($automatedEmailCounterObj);
        $this->_em->flush();
        return $automatedEmailCounterObj->getId();
    }
}
