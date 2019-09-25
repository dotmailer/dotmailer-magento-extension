<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Catalog_Processed
{
    /**
     * Catalog processed options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array(
            '1' => Mage::helper('ddg')->__('Processed'),
            '0' => Mage::helper('ddg')->__('Not processed'),
        );
    }
}