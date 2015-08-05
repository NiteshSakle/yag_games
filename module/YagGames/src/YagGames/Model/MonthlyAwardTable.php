<?php

namespace YagGames\Model;

class MonthlyAwardTable extends BaseTable {

    public function insert(MonthlyAward $monthlyAward) {
        try {

            if (!$this->isValid($monthlyAward)) {
                return false;
            }
            $this->tableGateway->insert($monthlyAward->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(MonthlyAward $monthlyAward) {
        try {
            if (!$this->isValid($monthlyAward)) {
                return false;
            }
            $this->tableGateway->update($monthlyAward->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestTypeId) {
        $rowset = $this->tableGateway->select(array('id' => $contestTypeId));
        $contestRow = $rowset->current();
        return $contestRow;
    }
}
