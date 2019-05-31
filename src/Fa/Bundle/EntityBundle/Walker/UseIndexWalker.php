<?php

namespace Fa\Bundle\EntityBundle\Walker;

use Doctrine\ORM\Query\SqlWalker;

class UseIndexWalker extends SqlWalker
{
    const HINT_USE_INDEX = 'UseIndexWalker.UseIndex';

    public function walkFromClause($fromClause)
    {
        $sql = parent::walkFromClause($fromClause);
        $index = $this->getQuery()->getHint(self::HINT_USE_INDEX);

        return preg_replace('/( INNER JOIN| LEFT JOIN|$)/', sprintf(' USE INDEX(%s)\1', $index), $sql, 1);
    }
}
