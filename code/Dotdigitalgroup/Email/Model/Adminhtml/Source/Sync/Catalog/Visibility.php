<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sync_Catalog_Visibility
{

    /**
     * Options getter. Styling options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = Mage::getModel('catalog/product_visibility')->getAllOptions();
        $options[0]['label'] = '---- Default Option ----';
        $options[0]['value'] = '0';

        return $options;
    }
}