<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Rules_Value
{

    /**
     * Get element type.
     *
     * @param $attribute
     *
     * @return string
     */
    public function getValueElementType($attribute)
    {
        switch ($attribute) {
            case 'method':
            case 'shipping_method':
            case 'country_id':
            case 'region_id':
            case 'customer_group_id':
                return 'select';
            default:
                $attribute = Mage::getSingleton('eav/config')->getAttribute(
                    'catalog_product', $attribute
                );
                if ($attribute->usesSource()) {
                    return 'select';
                }
        }

        return 'text';
    }

    /**
     * Get options array.
     *
     * @param      $attribute
     * @param bool $isEmpty
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getValueSelectOptions($attribute, $isEmpty = false)
    {
        $options = array();
        if ($isEmpty) {
            $options = Mage::getModel('adminhtml/system_config_source_yesno')
                ->toOptionArray();

            return $options;
        }

        switch ($attribute) {
            case 'country_id':
                $options = Mage::getModel('adminhtml/system_config_source_country')
                    ->toOptionArray();
                break;

            case 'region_id':
                $options = Mage::getModel('adminhtml/system_config_source_allregion')
                    ->toOptionArray();
                break;

            case 'shipping_method':
                $options = Mage::getModel('adminhtml/system_config_source_shipping_allmethods')
                    ->toOptionArray();
                break;

            case 'method':
                $options = Mage::getModel('adminhtml/system_config_source_payment_allmethods')
                    ->toOptionArray();
                break;

            case 'customer_group_id':
                $options = Mage::getModel('adminhtml/system_config_source_customer_group')
                    ->toOptionArray();
                break;

            default:
                $attribute = Mage::getSingleton('eav/config')->getAttribute(
                    'catalog_product', $attribute
                );
                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions(false);
                }
        }

        return $options;
    }

    /**
     * Options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = Mage::getModel('adminhtml/system_config_source_payment_allmethods')
            ->toOptionArray();

        return $options;
    }
}