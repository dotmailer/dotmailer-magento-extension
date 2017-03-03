<?php

/**
 * @codingStandardsIgnoreStart
 * Class Dotdigitalgroup_Email_Model_Rules
 */
class Dotdigitalgroup_Email_Model_Rules extends Mage_Core_Model_Abstract
{
    const REVIEW = 2;
    const ABANDONED = 1;

    /**
     * @var array
     */
    public $defaultOptions;
    /**
     * @var
     */
    public $conditionMap;
    /**
     * @var
     */
    public $attributeMapForQuote;
    /**
     * @var
     */
    public $attributeMapForOrder;
    /**
     * @var
     */
    public $productAttribute;
    /**
     * @var array
     */
    public $used = array();

    /**
     * Constructor.
     */
    public function _construct()
    {
        $this->defaultOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_type')->defaultOptions();

        $this->conditionMap = array(
                'eq' => 'neq',
                'neq' => 'eq',
                'gteq' => 'lt',
                'lteq' => 'gt',
                'gt' => 'lteq',
                'lt' => 'gteq',
                'like' => 'nlike',
                'nlike' => 'like'
            );
        $this->attributeMapForQuote = array(
                'method' => 'method',
                'shipping_method' => 'shipping_method',
                'country_id' => 'country_id',
                'city' => 'city',
                'region_id' => 'region_id',
                'customer_group_id' => 'main_table.customer_group_id',
                'coupon_code' => 'main_table.coupon_code',
                'subtotal' => 'main_table.subtotal',
                'grand_total' => 'main_table.grand_total',
                'items_qty' => 'main_table.items_qty',
                'customer_email' => 'main_table.customer_email',
            );
        $this->attributeMapForOrder = array(
                'method' => 'method',
                'shipping_method' => 'main_table.shipping_method',
                'country_id' => 'country_id',
                'city' => 'city',
                'region_id' => 'region_id',
                'customer_group_id' => 'main_table.customer_group_id',
                'coupon_code' => 'main_table.coupon_code',
                'subtotal' => 'main_table.subtotal',
                'grand_total' => 'main_table.grand_total',
                'items_qty' => 'items_qty',
                'customer_email' => 'main_table.customer_email',
            );
        parent::_construct();
        $this->_init('ddg_automation/rules');
    }

    /**
     * Before save.
     *
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        } else {
            $this->setUpdatedAt($now);
        }

        $this->setCondition(serialize($this->getCondition()));
        $this->setWebsiteIds(implode(',', $this->getWebsiteIds()));

        return $this;
    }

    /**
     * After load.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->setCondition(unserialize($this->getCondition()));

        return $this;
    }

    /**
     * Check if rule already exist for website.
     *
     * @param $websiteId
     * @param $type
     * @param bool $ruleId
     * @return bool
     */
    public function checkWebsiteBeforeSave($websiteId, $type, $ruleId = false)
    {
        $collection = $this->getCollection();
        $collection
            ->addFieldToFilter('type', array('eq' => $type))
            ->addFieldToFilter('website_ids', array('finset' => $websiteId));
        if ($ruleId) {
            $collection->addFieldToFilter('id', array('neq' => $ruleId));
        }

        $collection->setPageSize(1);

        if ($collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * Get website active rule.
     *
     * @param $type
     * @param $websiteId
     * @return array|Varien_Object
     */
    public function getActiveRuleForWebsite($type, $websiteId)
    {
        $collection = $this->getCollection();
        $collection
            ->addFieldToFilter('type', array('eq' => $type))
            ->addFieldToFilter('status', array('eq' => 1))
            ->addFieldToFilter('website_ids', array('finset' => $websiteId))
            ->setPageSize(1);
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return array();
    }

    /**
     * Process rule on collection.
     *
     * @param $collection
     * @param $type
     * @param $websiteId
     * @return mixed
     */
    public function process($collection, $type, $websiteId)
    {
        $rule = $this->getActiveRuleForWebsite($type, $websiteId);
        //if no rule then return the collection untouched
        if (empty($rule)) {
            return $collection;
        }

        //if rule has no conditions then return the collection untouched
        $condition = unserialize($rule->getCondition());

        if (empty($condition)) {
            return $collection;
        }

        //join tables to collection according to type
        if ($type == self::ABANDONED) {
            $collection->getSelect()
                ->joinLeft(
                    array('quote_address' => Mage::getSingleton('core/resource')
                        ->getTableName('sales_flat_quote_address')),
                    "main_table.entity_id = quote_address.quote_id",
                    array('shipping_method', 'country_id', 'city', 'region_id')
                )->joinLeft(
                    array('quote_payment' => Mage::getSingleton('core/resource')
                        ->getTableName('sales_flat_quote_payment')),
                    "main_table.entity_id = quote_payment.quote_id",
                    array('method')
                )->where('address_type = ?', 'shipping');
        } elseif ($type == self::REVIEW) {
            $collection->getSelect()
                ->join(
                    array('order_address' => Mage::getSingleton('core/resource')
                        ->getTableName('sales_flat_order_address')),
                    "main_table.entity_id = order_address.parent_id",
                    array('country_id', 'city', 'region_id')
                )->join(
                    array('order_payment' => Mage::getSingleton('core/resource')
                        ->getTableName('sales_flat_order_payment')),
                    "main_table.entity_id = order_payment.parent_id",
                    array('method')
                )->join(
                    array('quote' => Mage::getSingleton('core/resource')
                        ->getTableName('sales_flat_quote')),
                    "main_table.quote_id = quote.entity_id",
                    array('items_qty')
                )->where('order_address.address_type = ?', 'shipping');
        }
        //process rule on collection according to combination
        $combination = $rule->getCombination();

        // ALL TRUE
        if ($combination == 1) {
            return $this->_processAndCombination($collection, $condition, $type);
        }

        //ANY TRUE
        if ($combination == 2) {
            return $this->_processOrCombination($collection, $condition, $type);
        }
    }

    /**
     * Process And combination on collection.
     *
     * @param $collection
     * @param $conditions
     * @param $type
     * @return mixed
     */
    protected function _processAndCombination($collection, $conditions, $type)
    {
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond = $condition['conditions'];
            $value = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' or $value == null) {
                continue;
            }

            //ignore conditions for already used attribute
            if (in_array($attribute, $this->used)) {
                continue;
            }

            //set used to check later
            $this->used[] = $attribute;

            if ($type == self::REVIEW && isset($this->attributeMapForOrder[$attribute])) {
                $attribute = $this->attributeMapForOrder[$attribute];
            } elseif ($type == self::ABANDONED && isset($this->attributeMapForQuote[$attribute])) {
                $attribute = $this->attributeMapForQuote[$attribute];
            } else {
                $this->productAttribute[] = $condition;
                continue;
            }

            if ($cond == 'null') {
                if ($value == '1') {
                    $collection->addFieldToFilter($attribute, array('notnull' => true));
                } elseif ($value == '0') {
                    $collection->addFieldToFilter($attribute, array($cond => true));
                }
            } else {
                if ($cond == 'like' or $cond == 'nlike') {
                    $value = '%' . $value . '%';
                }

                $collection->addFieldToFilter($attribute, array($this->conditionMap[$cond] => $value));
            }
        }

        return $this->_processProductAttributes($collection);
    }

    /**
     * Process OR combination on collection.
     *
     * @param $collection
     * @param $conditions
     * @param $type
     * @return mixed
     */
    protected function _processOrCombination($collection, $conditions, $type)
    {
        $fieldsConditions = array();
        $multiFieldsConditions = array();
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond = $condition['conditions'];
            $value = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' or $value == null) {
                continue;
            }

            if ($type == self::REVIEW && isset($this->attributeMapForQuote[$attribute])) {
                $attribute = $this->attributeMapForOrder[$attribute];
            } elseif ($type == self::ABANDONED && isset($this->attributeMapForOrder[$attribute])) {
                $attribute = $this->attributeMapForQuote[$attribute];
            } else {
                $this->productAttribute[] = $condition;
                continue;
            }

            if ($cond == 'null') {
                if ($value == '1') {
                    if (isset($fieldsConditions[$attribute])) {
                        $multiFieldsConditions[$attribute][] = array('notnull' => true);
                        continue;
                    }

                    $fieldsConditions[$attribute] = array('notnull' => true);
                } elseif ($value == '0') {
                    if (isset($fieldsConditions[$attribute])) {
                        $multiFieldsConditions[$attribute][] = array($cond => true);;
                        continue;
                    }

                    $fieldsConditions[$attribute] = array($cond => true);
                }
            } else {
                if ($cond == 'like' or $cond == 'nlike') {
                    $value = '%' . $value . '%';
                }

                if (isset($fieldsConditions[$attribute])) {
                    $multiFieldsConditions[$attribute][] = array($this->conditionMap[$cond] => $value);
                    continue;
                }

                $fieldsConditions[$attribute] = array($this->conditionMap[$cond] => $value);
            }
        }

        //all rules condition will be with or combination
        if (!empty($fieldsConditions)) {
            $column = array();
            $cond = array();
            foreach ($fieldsConditions as $key => $fieldsCondition) {
                $column[] = (string)$key;
                $cond[] = $fieldsCondition;
                if (!empty($multiFieldsConditions[$key])) {
                    foreach ($multiFieldsConditions[$key] as $multiFieldsCondition) {
                        $column[] = (string)$key;
                        $cond[] = $multiFieldsCondition;
                    }
                }
            }

            $collection->addFieldToFilter(
                $column,
                $cond
            );
        }

        return $this->_processProductAttributes($collection);
    }

    /**
     * Process product attributes on collection.
     *
     * @param $collection
     * @return mixed
     */
    protected function _processProductAttributes($collection)
    {

        //if no product attribute or collection empty return collection
        if (empty($this->productAttribute) or !$collection->getSize()) {
            return $collection;
        }

        foreach ($collection as $key => $collectionItem) {
            $items = $collectionItem->getAllItems();
            foreach ($items as $item) {
                //loaded product
                $product = $item->getProduct();

                //attributes array from loaded product
                $attributes = Mage::getModel('eav/config')->getEntityAttributeCodes(
                    Mage_Catalog_Model_Product::ENTITY,
                    $product
                );

                foreach ($this->productAttribute as $productAttribute) {
                    $attribute = $productAttribute['attribute'];
                    $cond = $productAttribute['conditions'];
                    $value = $productAttribute['cvalue'];

                    if ($cond == 'null') {
                        if ($value == '0') {
                            $cond = 'neq';
                        } elseif ($value == '1') {
                            $cond = 'eq';
                        }

                        $value = '';
                    }

                    //if attribute is in product's attributes array
                    if (in_array($attribute, $attributes)) {
                        $attr = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attribute);
                        //frontend type
                        $frontType = $attr->getFrontend()->getInputType();
                        //if type is select
                        if ($frontType == 'select' or $frontType == 'multiselect') {
                            $attributeValue = $product->getAttributeText($attribute);
                            //evaluate conditions on values. if true then unset item from collection
                            if ($this->_evaluate($value, $cond, $attributeValue)) {
                                $collection->removeItemByKey($key);
                                continue 3;
                            }
                        } else {
                            $getter = 'get';
                            $exploded = explode('_', $attribute);
                            foreach ($exploded as $one) {
                                $getter .= ucfirst($one);
                            }

                            $attributeValue = call_user_func(array($product, $getter));
                            //if retrieved value is an array then loop through all array values. Ex. categories
                            if (is_array($attributeValue)) {
                                foreach ($attributeValue as $attrValue) {
                                    //evaluate conditions on values. if true then unset item from collection
                                    if ($this->_evaluate($value, $cond, $attrValue)) {
                                        $collection->removeItemByKey($key);
                                        continue 3;
                                    }
                                }
                            } else {
                                //evaluate conditions on values. if true then unset item from collection
                                if ($this->_evaluate($value, $cond, $attributeValue)) {
                                    $collection->removeItemByKey($key);
                                    continue 3;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $collection;
    }

    /**
     * Evaluate two values against condition.
     *
     *
     * @param $varOne
     * @param $op
     * @param $varTwo
     * @return bool
     */
    protected function _evaluate($varOne, $op, $varTwo)
    {
        switch ($op) {
            case "eq":
                return $varOne == $varTwo;
            case "neq":
                return $varOne != $varTwo;
            case "gteq":
                return $varOne >= $varTwo;
            case "lteq":
                return $varOne <= $varTwo;
            case "gt":
                return $varOne > $varTwo;
            case "lt":
                return $varOne < $varTwo;
            case "like":
                if (strpos($varTwo, $varOne) !== false) {
                    return true;
                }
                break;
            case "nlike":
                if (strpos($varTwo, $varOne) === false) {
                    return true;
                }
                break;
        }

        return false;
    }
}