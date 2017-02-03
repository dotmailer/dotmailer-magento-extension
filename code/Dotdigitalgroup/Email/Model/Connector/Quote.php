<?php

class Dotdigitalgroup_Email_Model_Connector_Quote
{

    /**
     * @var int
     */
    public $id;
    /**
     * Email
     *
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $store_name;
    /**
     * @var string
     */
    public $created_date;
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
    public $quote_subtotal;
    /**
     * @var float
     */
    public $discount_amount;
    /**
     * @var float
     */
    public $quote_total;
    /**
     * @var array
     */
    public $categories = array();
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


    public $couponCode;

    /**
     * @var array
     */
    public $custom = array();

    protected $_attributeSet;

    /**
     * set the quote information
     *
     * @param Mage_Sales_Model_Quote $quoteData
     */
    public function __construct(Mage_Sales_Model_Quote $quoteData)
    {
        $this->id         = $quoteData->getId();
        $this->email      = $quoteData->getCustomerEmail();
        $this->store_name = $quoteData->getStore()->getName();

        $created_at = new Zend_Date(
            $quoteData->getCreatedAt(), Zend_Date::ISO_8601
        );

        $this->created_date = $created_at->toString(Zend_Date::ISO_8601);
        if ($quoteData->getShippingAddress()) {
            $this->delivery_method = $quoteData->getShippingAddress()
                ->getShippingDescription();
            $this->delivery_total  = $quoteData->getShippingAddress()
                ->getShippingAmount();
        }
        $this->currency = $quoteData->getStoreCurrencyCode();
        if ($payment = $quoteData->getPayment()) {
            $this->payment = $payment->getMethod();
        }

        $this->couponCode = $quoteData->getCouponCode();

        /**
         * custom quote attributes
         */
        $helper           = Mage::helper('ddg');
        $website          = Mage::app()->getStore($quoteData->getStore())
            ->getWebsite();
        $customAttributes = $helper->getConfigSelectedCustomQuoteAttributes(
            $website
        );
        if ($customAttributes) {
            $fields = Mage::getResourceModel('ddg_automation/quote')
                ->getQuoteTableDescription();
            $this->custom = array();
            foreach ($customAttributes as $customAttribute) {
                if (isset($fields[$customAttribute])) {
                    $field = $fields[$customAttribute];
                    $value = $this->_getCustomAttributeValue(
                        $field, $quoteData
                    );
                    if ($value) {
                        $this->_assignCustom($field, $value);
                    }
                }
            }
        }

        /**
         * Billing address.
         */
        if ($quoteData->getBillingAddress()) {
            $billingData           = $quoteData->getBillingAddress()->getData();
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
        /**
         * Shipping address.
         */
        if ($quoteData->getShippingAddress()) {
            $shippingData = $quoteData->getShippingAddress()->getData();

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

        /**
         * Quote items.
         *
         * @var Mage_Sales_Model_Quote_Item $productItem
         */
        foreach ($quoteData->getAllItems() as $productItem) {

            $product = $productItem->getProduct();

            if ($product) {
                // category names
                $categoryCollection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name');

                foreach ($categoryCollection as $cat) {
                    $categories                 = array();
                    $categories[]               = $cat->getName();
                    $this->categories[]['Name'] = substr(
                        implode(', ', $categories), 0, 244
                    );
                }

                //get attribute set name
                $attributeSetName = $this->_getAttributeSetName($product);
                $this->products[] = array(
                    'name'          => $productItem->getName(),
                    'sku'           => $productItem->getSku(),
                    'qty'           => (int)number_format(
                        $productItem->getData('qty'), 2
                    ),
                    'price'         => (float)number_format(
                        $productItem->getPrice(), 2, '.', ''
                    ),
                    'attribute-set' => $attributeSetName
                );
            } else {
                // when no product information is available limit to this data
                $this->products[] = array(
                    'name'  => $productItem->getName(),
                    'sku'   => $productItem->getSku(),
                    'qty'   => (int)number_format(
                        $productItem->getData('qty'), 2
                    ),
                    'price' => (float)number_format(
                        $productItem->getPrice(), 2, '.', ''
                    )
                );
            }
        }

        $this->quote_subtotal  = (float)number_format(
            $quoteData->getData('subtotal'), 2, '.', ''
        );
        $discountAmount        = $quoteData->getData('subtotal')
            - $quoteData->getData('subtotal_with_discount');
        $this->discount_amount = (float)number_format(
            $discountAmount, 2, '.', ''
        );
        $this->quote_total     = (float)number_format(
            $quoteData->getData('grand_total'), 2, '.', ''
        );

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

    /**
     * get custom attribute value
     *
     * @param $field
     * @param $quoteData
     *
     * @return float|int|null|string
     */
    protected function _getCustomAttributeValue($field, $quoteData)
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
                    $value = (int)$quoteData->$function();
                    break;

                case 'decimal':
                    $value = (float)number_format(
                        $quoteData->$function(), 2, '.', ''
                    );
                    break;

                case 'timestamp':
                case 'datetime':
                case 'date':
                    $date  = new Zend_Date(
                        $quoteData->$function(), Zend_Date::ISO_8601
                    );
                    $value = $date->toString(Zend_Date::ISO_8601);
                    break;

                default:
                    $value = $quoteData->$function();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $value;
    }

    /**
     * create property on runtime
     *
     * @param $field
     * @param $value
     */
    protected function _assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * get attribute set name
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
     * load attribute model
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
}
