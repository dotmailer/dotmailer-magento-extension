<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sync_Catalog_Attributes
{

    /**
     * all attributes type catalog
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes     = Mage::getResourceModel(
            'catalog/product_attribute_collection'
        )->addVisibleFilter();
        $attributeArray = array();
        $attributeArray[] = array(
            'label' => '---- Default Option ----',
            'value' => '0',
        );

        //exclude these from showing in the options
        $exclude = array('gallery', 'image', 'media_gallery', 'small_image',
                         'thumbnail');

        foreach ($attributes as $attribute) {
            if ( ! in_array($attribute->getData('attribute_code'), $exclude)) {
                $attributeArray[] = array(
                    'label' => $attribute->getData('frontend_label'),
                    'value' => $attribute->getData('attribute_code')
                );
            }
        }

        return $attributeArray;
    }
}