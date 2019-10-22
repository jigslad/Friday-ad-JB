<?php


namespace Fa\Bundle\ContentBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Native Banner page repository.
 *
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class NativeBannerRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;
    const ALIAS = 'bn';
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
    private function getBannerTableName()
    {
        return $this->_em->getClassMetadata('FaContentBundle:NativeBanner')->getTableName();
    }
}