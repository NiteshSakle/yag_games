<?php

namespace YagGames\Model;

class MembersTable extends BaseTable
{

    public function update(Members $members)
    {
        try {
            if (!$this->isValid($members)) {
                return false;
            }
            $updateDate = array();
            foreach (get_object_vars($members) as $propertyName => $propertyValue) {
                if (isset($propertyValue)) {
                    $updateDate["$propertyName"] = $propertyValue;
                }
            }

            $this->tableGateway->update($updateDate, array('mem_id' => $members->mem_id));
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($memId)
    {
        $rowset = $this->tableGateway->select(array('mem_id' => $memId));
        $member = $rowset->current();
        return $member;
    }

    public function updateSpecificFields($memId, $updateData)
    {
        try {
            $this->tableGateway->update($updateData, array('mem_id' => $memId));
            return true;
        } catch (Exception $ex) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

}
