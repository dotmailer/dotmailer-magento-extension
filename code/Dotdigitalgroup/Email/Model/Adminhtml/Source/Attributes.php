<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Attributes
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter();

        $attributeArray = array(
            array(
                'label' => Mage::helper('ddg')->__('Select Attribute....'),
                'value' => ''
            )
        );

        foreach ($attributes as $attribute) {
            $attributeArray[] = array(
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            );
        }

        return $attributeArray;
    }
}