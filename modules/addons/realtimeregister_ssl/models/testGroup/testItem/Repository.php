<?php

namespace AddonModule\RealtimeRegisterSsl\models\testGroup\TestItem;
use AddonModule\RealtimeRegisterSsl\addonLibs;
use AddonModule\RealtimeRegisterSsl\models\testGroup\simpleItem;

/**
 * Description of repository
 *
 */
class Repository extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__.'\TestItem';
    }
    
    public function get()
    {
        $sql = "
            SELECT
                ".addonLibs\MySQL\Query::formatSelectFields(testItem::fieldDeclaration(),'B')."
                ,count(S.`". simpleItem\simpleItem::getProperyColumn('id')."`) as simpleNum
            FROM
                ".testItem::tableName()." B
            LEFT JOIN
                ".simpleItem\simpleItem::tableName()." S
                ON
                    S.`". simpleItem\simpleItem::getProperyColumn('testItemID')."` = B.`"
                .testItem::getProperyColumn('id')."`
        ";

        $conditionParsed = addonLibs\MySQL\Query::parseConditions($this->_filters,$params,'B');

        if ($conditionParsed) {
            $sql .= " WHERE ".$conditionParsed;
        }
        
        $sql .= " GROUP BY `B`.`".testItem::getProperyColumn('id')."` ";

        $sql .= addonLibs\MySQL\Query::formarLimit($this->_limit, $this->_offest);

        $result = addonLibs\MySQL\Query::query($sql,$params);
        
        $output = [];
        
        $class = $this->getModelClass();
        
        while ($row = $result->fetch()) {
            $output[] = new $class($row['id'],$row);
        }

        return $output;
    }
}
