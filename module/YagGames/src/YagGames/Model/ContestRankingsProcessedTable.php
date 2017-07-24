<?php

namespace YagGames\Model;

class ContestRankingsProcessedTable extends BaseTable
{
    public function insert(ContestRankingsProcessed $contestRankingsProcessed)
    {
        try {
            $this->created($contestRankingsProcessed);
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
}
