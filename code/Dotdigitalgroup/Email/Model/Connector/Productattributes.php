<?php

class Dotdigitalgroup_Email_Model_Connector_Productattributes
{
    /**
     * Returns Product Custom Attributes to be Synced
     * @param $configAttributes
     * @param $product
     * @return Dotdigitalgroup_Email_Model_Connector_Productattributes
     * @throws Zend_Date_Exception
     */
    public function initializeCustomAttributes($configAttributes, $product)
    {
        $configAttributes = explode(',', $configAttributes);
        //attributes from attribute set
        $attributesFromAttributeSet = $this->_getAttributesArray(
            $product->getAttributeSetId()
        );

        foreach ($configAttributes as $attributeCode) {
            //if config attribute is in attribute set
            if (in_array(
                $attributeCode, $attributesFromAttributeSet
            )) {
                //attribute input type
                $inputType = $product->getResource()
                    ->getAttribute($attributeCode)
                    ->getFrontend()
                    ->getInputType();

                $value = $this->getAttributeValue($inputType, $product, $attributeCode);
                $this->$attributeCode = $this->limitLength($value);

            }
        }
        return $this;
    }
    /**
     * Get attributes from attribute set.
     *
     * @param $attributeSetId
     *
     * @return array
     */
    private function _getAttributesArray($attributeSetId)
    {
        $result     = array();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->setAttributeSetFilter($attributeSetId)
            ->getItems();

        foreach ($attributes as $attribute) {
            $result[] = $attribute->getAttributeCode();
        }

        return $result;
    }

    /**
     * Returns attribute value based on input type field
     * @param $inputType
     * @param $product
     * @param $attributeCode
     * @return string
     * @throws Zend_Date_Exception
     */
    private function getAttributeValue($inputType, $product, $attributeCode)
    {
        switch ($inputType) {
            case 'multiselect':
            case 'select':
            case 'dropdown':
                return $product->getAttributeText(
                    $attributeCode
                );
                break;
            case 'date':
                $date = new Zend_Date(
                    $product->getData($attributeCode), Zend_Date::ISO_8601
                );
                return $date->toString(Zend_Date::ISO_8601);
                break;
            default:
                return $product->getData($attributeCode);
                break;
        }
    }

    /**
     * Validates the length of the string/array
     * @param $value
     * @return string|null
     */
    private function limitLength($value)
    {
        // check limit on text and assign value to array
        if (is_string($value)) {
            return $this->_limitLength($value);
        } elseif (is_array($value)) {
            $value = implode(', ', $value);
            return $this->_limitLength($value);
        }
        return null;
    }

    /**
     *  Check string length and limit to 250.
     *
     * @param $value
     *
     * @return string
     */
    private function _limitLength($value)
    {
        if (strlen($value) > 250) {
            $value = substr($value, 0, 250);
        }

        return $value;
    }
}