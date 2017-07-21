<?php

namespace YagGames\Model;

class ContestRankingsProcessedTable extends BaseTable
{
    public function insert(ContestRankingsProcessed $contestRankingsProcessed)
    {
        try {
            if (!$this->isValid($contestRankingsProcessed)) {
                return false;
            }
            $this->tableGateway->insert($contestRankingsProcessed->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestRankingsProcessed $contestRankingsProcessed)
    {
        try {
            if (!$this->isValid($contestRankingsProcessed)) {
                return false;
            }
            $this->tableGateway->update($contestRankingsProcessed->getArrayCopy());
            return true;
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }
}
