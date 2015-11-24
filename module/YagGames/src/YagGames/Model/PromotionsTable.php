<?php

namespace YagGames\Model;

use Exception;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;

class PromotionsTable extends BaseTable
{

    public function insert(Promotions $promotions)
    {
        try {            
            if (!$this->isValid($promotions)) {
                return false;
            }
            $this->tableGateway->insert($promotions->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($promoId)
    {
        $rowset = $this->tableGateway->select(array('id' => $promoId));
        $promotionsRow = $rowset->current();
        return $promotionsRow;
    }

    public function checkPromoCodeExist($promoCode)
    {
        try {
            $select = new \Zend\Db\Sql\Select;
            $select->from(array('p' => 'ps4_promotions'))
                   ->columns(array('count' => new Expression('COUNT(p.promo_id)')))
                   ->where(array('promo_code' => $promoCode));    
            $statement = $this->getSql()->prepareStatementForSqlObject($select)->execute();
            $row = $statement->current();
            
            if (is_array($row) && $row['count'] > 0) {
                return TRUE;
            }
            return FALSE;
        } catch (Exception $ex) {
            $this->logException($ex);
            return FALSE;
        }
    }

}
