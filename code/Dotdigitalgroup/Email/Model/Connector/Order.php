<?php

/**
 * @codingStandardsIgnoreStart
 * Class Dotdigitalgroup_Email_Model_Connector_Order
 */
class Dotdigitalgroup_Email_Model_Connector_Order
{

    /**
     * Order Increment ID
     *
     * @var string
     */
    public $id;
    /**
     * Email
     *
     * @var string
     */
    public $email;
    /**
     * @var int
     */
    public $quote_id;
    /**
     * @var string
     */
    public $store_name;
    /**
     * @var string
     */
    public $purchase_date;
    /**
     * @var array
     */
    public $delivery_address = array();
    /**
     * @var array
     */
    public $billing_address = array();
    /**
     * @var array
     */
    public $products = array();
    /**
     * @var float
     */
    public $order_subtotal;
    /**
     * @var float
     */
    public $discount_ammount;
    /**
     * @var float
     */
    public $order_total;
    /**
     * Payment name
     *
     * @var string
     */
    public $payment;
    /**
     * @var string
     */
    public $delivery_method;
    /**
     * @var float
     */
    public $delivery_total;
    /**
     * @var string
     */
    public $currency;

    /**
     * @var string
     */
    public $couponCode;

    /**
     * @var array
     */
    public $custom = array();

    /**
     * @var string
     */
    public $order_status;

    protected $_attributeSet;

    /**
     * set the order information
     *
     * @param Mage_Sales_Model_Order $orderData
     */
    public function setOrderData(Mage_Sales_Model_Order $orderData)
    {
        $this->id         = $orderData->getIncrementId();
        $this->quote_id   = $orderData->getQuoteId();
        $this->email      = $orderData->getCustomerEmail();
        $this->store_name = $orderData->getStoreName();

        $created_at = new Zend_Date(
            $orderData->getCreatedAt(), Zend_Date::ISO_8601
        );

        $this->purchase_date   = $created_at->toString(Zend_Date::ISO_8601);
        $this->delivery_method = $orderData->getShippingDescription();
        $this->delivery_total = (float)number_format(
            $orderData->getShippingAmount(), 2, '.', ''
        );
        $this->currency        = $orderData->getStoreCurrencyCode();

        if ($payment = $orderData->getPayment()) {
            $this->payment = $payment->getMethodInstance()->getTitle();
        }

        $this->couponCode = $orderData->getCouponCode();

        //set order custom attributes
        $this->_setOrderCustomAttributes($orderData);
        //billing
        $this->_setBillingData($orderData);
        //shipping
        $this->_setShippingData($orderData);
        //order items
        $this->_setOrderItems($orderData);
        //sales data
        $this->order_subtotal   = (float)number_format(
            $orderData->getData('subtotal'), 2, '.', ''
        );
        $this->discount_ammount = (float)number_format(
            $orderData->getData('discount_amount'), 2, '.', ''
        );
        $orderTotal             = abs(
            $orderData->getData('grand_total') - $orderData->getTotalRefunded()
        );
        $this->order_total      = (float)number_format($orderTotal, 2, '.', '');
        $this->order_status     = $orderData->getStatus();
    }

    /**
     * Shipping address.
     */
    protected function _setShippingData($orderData)
    {

        if ($orderData->getShippingAddress()) {
            $shippingData = $orderData->getShippingAddress()->getData();

            $this->delivery_address = array(
                'delivery_address_1' => $this->_getStreet(
                    $shippingData['street'], 1
                ),
                'delivery_address_2' => $this->_getStreet(
                    $shippingData['street'], 2
                ),
                'delivery_city'      => $shippingData['city'],
                'delivery_region'    => $shippingData['region'],
                'delivery_country'   => $shippingData['country_id'],
                'delivery_postcode'  => $shippingData['postcode']
            );
        }
    }

    /**
     * Billing address.
     */
    protected function _setBillingData($orderData)
    {
        if ($orderData->getBillingAddress()) {
            $billingData           = $orderData->getBillingAddress()->getData();
            $this->billing_address = array(
                'billing_address_1' => $this->_getStreet(
                    $billingData['street'], 1
                ),
                'billing_address_2' => $this->_getStreet(
                    $billingData['street'], 2
                ),
                'billing_city'      => $billingData['city'],
                'billing_region'    => $billingData['region'],
                'billing_country'   => $billingData['country_id'],
                'billing_postcode'  => $billingData['postcode'],
            );
        }
    }

    /**
     * custom order attributes
     */
    protected function _setOrderCustomAttributes($orderData)
    {
    	$this->custom = array();

        $helper           = Mage::helper('ddg');
        $website          = Mage::app()->getStore($orderData->getStore())
            ->getWebsite();
        $customAttributes = $helper->getConfigSelectedCustomOrderAttributes(
            $website
        );
        if ($customAttributes) {
            $fields = Mage::getResourceModel('ddg_automation/order')
                ->getOrderTableDescription();
            
            foreach ($customAttributes as $customAttribute) {
                if (isset($fields[$customAttribute])) {
                    $field = $fields[$customAttribute];
                    $value = $this->_getCustomAttributeValue(
                        $field, $orderData
                    );
                    if ($value) {
                        $this->_assignCustom($field, $value);
                    }
                }
            }
        }
    }

    /**
     * get the street name by line number
     *
     * @param $street
     * @param $line
     *
     * @return string
     */
    protected function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if ($line == 1) {
            return $street[0];
        }

        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        } else {
            return '';
        }
    }

    /**
     * exposes the class as an array of objects.
     *
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);

    }

    protected function _getCustomAttributeValue($field, $orderData)
    {
        $type = $field['DATA_TYPE'];

        $function = 'get';
        $exploded = explode('_', $field['COLUMN_NAME']);
        foreach ($exploded as $one) {
            $function .= ucfirst($one);
        }

        $value = null;
        try {
            switch ($type) {
                case 'int':
                case 'smallint':
                    $value = (int)$orderData->$function();
                    break;

                case 'decimal':
                    $value = (float)number_format(
                        $orderData->$function(), 2, '.', ''
                    );
                    break;

                case 'timestamp':
                case 'datetime':
                case 'date':
                    $date  = new Zend_Date(
                        $orderData->$function(), Zend_Date::ISO_8601
                    );
                    $value = $date->toString(Zend_Date::ISO_8601);
                    break;

                default:
                    $value = $orderData->$function();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $value;
    }

    /**
     * Create property on runtime.
     *
     * @param $field
     * @param $value
     */
    protected function _assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * Get attributes from attribute set.
     *
     * @param $attributeSetId
     *
     * @return array
     */
    protected function _getAttributesArray($attributeSetId)
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
     *  Check string length and limit to 250.
     *
     * @param $value
     *
     * @return string
     */
    protected function _limitLength($value)
    {
        if (strlen($value) > 250) {
            $value = substr($value, 0, 250);
        }

        return $value;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     *
     * @return array
     */
    protected function _getOrderItemOptions($orderItem)
    {
        $orderItemOptions = $orderItem->getProductOptions();

        //if product doesn't have options
        if (! array_key_exists('options', $orderItemOptions)) {
            return array();
        }

        $orderItemOptions = $orderItemOptions['options'];

        //if product options isn't array
        if (! is_array($orderItemOptions)) {
            return array();
        }

        $options = array();

        foreach ($orderItemOptions as $orderItemOption) {
            if (array_key_exists('value', $orderItemOption)
                && array_key_exists(
                    'label', $orderItemOption
                )
            ) {
                $label             = str_replace(
                    ' ', '-', $orderItemOption['label']
                );
                $options[][$label] = $orderItemOption['value'];
            }
        }

        return $options;
    }

    /**
     * Get attribute set name.
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    protected function _getAttributeSetName(Mage_Catalog_Model_Product $product)
    {
        //check if empty. on true load model and cache result
        if (empty($this->_attributeSet)) {
            $this->_loadAttributeModel($product);
            if (empty($this->_attributeSet)) {
                return '';
            } else {
                return $this->_attributeSet->getAttributeSetName();
            }
        }

        //if cached attribute set id equals product's attribute set id
        if ($this->_attributeSet->getId() == $product->getAttributeSetId()) {
            return $this->_attributeSet->getAttributeSetName();
        }

        //if both above false. load model and cache result
        $this->_loadAttributeModel($product);
        if (empty($this->_attributeSet)) {
            return '';
        } else {
            return $this->_attributeSet->getAttributeSetName();
        }
    }

    /**
     * Load attribute model.
     *
     * @param Mage_Catalog_Model_Product $product
     */
    protected function _loadAttributeModel(Mage_Catalog_Model_Product $product)
    {
        $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
        $attributeSetModel->load($product->getAttributeSetId());
        if ($attributeSetModel->getId()) {
            $this->_attributeSet = $attributeSetModel;
        }
    }

    /**
     * @param $orderData
     */
    protected function _setOrderItems($orderData)
    {
        $website = Mage::app()->getStore($orderData->getStore())->getWebsite();

        $syncCustomOption = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS,
            $website
        );

        /**
         * Order items.
         *
         * @var Mage_Sales_Model_Order_Item $productItem
         */
        foreach ($orderData->getAllItems() as $productItem) {
            //product custom options
            $customOptions = array();
            if ($syncCustomOption) {
                $customOptions = $this->_getOrderItemOptions($productItem);
            }

            $product = $productItem->getProduct();

            if ($product) {
                // category names
                $categoryCollection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name');
                $productCat         = array();
                foreach ($categoryCollection as $cat) {
                    $categories           = array();
                    $categories[]         = $cat->getName();
                    $productCat[]['Name'] = substr(
                        implode(', ', $categories), 0, 244
                    );
                }

                $attributes = array();
                //selected attributes from config
                $configAttributes = Mage::helper('ddg')->getWebsiteConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_ATTRIBUTES,
                    $orderData->getStore()->getWebsite()
                );
                if ($configAttributes) {
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

                            //fetch attribute value from product depending on input type
                            switch ($inputType) {
                                case 'multiselect':
                                case 'select':
                                case 'dropdown':
                                    $value = $product->getAttributeText(
                                        $attributeCode
                                    );
                                    break;
                                case 'date':
                                    $date = new Zend_Date(
                                        $product->getData($attributeCode), Zend_Date::ISO_8601
                                    );
                                    $value = $date->toString(Zend_Date::ISO_8601);
                                    break;
                                default:
                                    $value = $product->getData($attributeCode);
                                    break;
                            }

                            // check limit on text and assign value to array
                            if (is_string($value)) {
                                $attributes[][$attributeCode]
                                    = $this->_limitLength($value);
                            } elseif (is_array($value)) {
                                $value = implode($value, ', ');
                                $attributes[][$attributeCode]
                                    = $this->_limitLength($value);
                            }
                        }
                    }
                }

                $attributeSetName = $this->_getAttributeSetName($product);
                $this->products[] = array(
                    'name'           => $productItem->getName(),
                    'sku'            => $productItem->getSku(),
                    'qty'            => (int)number_format(
                        $productItem->getData('qty_ordered'), 2
                    ),
                    'price'          => (float)number_format(
                        $productItem->getPrice(), 2, '.', ''
                    ),
                    'attribute-set'  => $attributeSetName,
                    'categories'     => $productCat,
                    'attributes'     => $attributes,
                    'custom-options' => $customOptions
                );
            } else {
                // when no product information is available limit to this data
                $this->products[] = array(
                    'name'           => $productItem->getName(),
                    'sku'            => $productItem->getSku(),
                    'qty'            => (int)number_format(
                        $productItem->getData('qty_ordered'), 2
                    ),
                    'price'          => (float)number_format(
                        $productItem->getPrice(), 2, '.', ''
                    ),
                    'attribute-set'  => '',
                    'categories'     => array(),
                    'attributes'     => array(),
                    'custom-options' => $customOptions
                );
            }
        }
    }
}
