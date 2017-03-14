<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sync_Catalog_Type
{

    /**
     * Options getter. Styling options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = Mage::getModel('catalog/product_type')->getAllOptions();
        //Add default option to first key of array. First key has empty value and empty label.
        $options[0]['label'] = '---- Default Option ----';
        $options[0]['value'] = '0';
        return $options;
    }
}