<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Orderstatus
{

    /**
     * Returns the order statuses
     *
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        $options  = array();
        $options[] = [
            'label' => '---- Default Option ----',
            'value' => '0',
        ];
        foreach ($statuses as $code => $label) {
            $options[] = array(
                'value' => $code,
                'label' => $label
            );
        }

        return $options;
    }
}