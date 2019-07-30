<?php

class Dotdigitalgroup_Email_Model_Resource_Abandoned extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/abandoned', 'id');
    }

    /**
     * Update an abandoned cart.
     *
     * @param array $data
     *
     * @return int
     */
    public function updateAbandonedCart($data)
    {
        $conn = $this->_getWriteAdapter();

        try {
            return $conn->update(
                $this->getMainTable(),
                array(
                    'abandoned_cart_number' => $data['abandoned_cart_number'],
                    'is_active' => $data['is_active'],
                    'quote_updated_at' => $data['quote_updated_at']
                ),
                array('quote_id = ?' => $data['quote_id'])
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
