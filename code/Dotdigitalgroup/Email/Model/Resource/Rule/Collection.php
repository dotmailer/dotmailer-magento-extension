<?php

class Dotdigitalgroup_Email_Model_Resource_Rule_Collection extends Mage_SalesRule_Model_Resource_Rule_Collection
{

    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @use $this->addWebsiteGroupDateFilter()
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function setValidationFilter($websiteId, $customerGroupId, $couponCode = '', $now = null)
    {
        if (! $this->getFlag('validation_filter')) {
            if ($now === null) {
                //@codingStandardsIgnoreStart
                $now = Mage::getModel('core/date')->date('Y-m-d');
                //@codingStandardsIgnoreEnd
            }

            /* We need to overwrite joinLeft if coupon is applied */
            $this->getSelect()->reset();
            Mage_Rule_Model_Resource_Rule_Collection_Abstract::_initSelect();

            $this->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now);
            $select = $this->getSelect();

            $connection = $this->getConnection();
            if ($couponCode !== '') {
                $select->joinLeft(
                    array('rule_coupons' => $this->getTable('salesrule/coupon')),
                    $connection->quoteInto(
                        'main_table.rule_id = rule_coupons.rule_id AND main_table.coupon_type != ?',
                        Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON
                    ),
                    array('code')
                );

                $noCouponCondition = $connection->quoteInto(
                    'main_table.coupon_type = ? ',
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON
                );

                $orWhereConditions = array(
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND rule_coupons.type = 0)',
                        Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                        Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                        Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                    ),
                );
                $orWhereCondition = implode(' OR ', $orWhereConditions);
                $select->where(
                    $noCouponCondition . ' OR ((' . $orWhereCondition . ') AND rule_coupons.code = ?)', $couponCode
                );

                $select->where(
                    '(rule_coupons.expiration_date IS NULL) AND (to_date is null or to_date >= ?) OR
                         (rule_coupons.expiration_date IS NOT NULL) AND (rule_coupons.expiration_date >= ?) ',
                    $now
                );
            } else {
                $this->addFieldToFilter('main_table.coupon_type', Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON);
            }

            $select->where(
                '(main_table.to_date IS NULL) OR
                 (main_table.to_date >= ?)',
                $now
            );

            $this->setOrder('sort_order', self::SORT_ORDER_ASC);
            $this->setFlag('validation_filter', true);
        }

        return $this;
    }

    /**
     * Filter collection by website(s), customer group(s) and date.
     * Filter collection to only active rules.
     * Sorting is not involved
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param null $now
     * @return $this
     */
    public function addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now = null)
    {
        if (! $this->getFlag('website_group_date_filter')) {
            if ($now === null) {
                //@codingStandardsIgnoreStart
                $now = Mage::getModel('core/date')->date('Y-m-d');
                //@codingStandardsIgnoreEnd
            }

            $this->addWebsiteFilter($websiteId);
            $entityInfo = $this->_getAssociatedEntityInfo('customer_group');
            $connection = $this->getConnection();
            $this->getSelect()
                ->joinInner(
                    array('customer_group_ids' => $this->getTable($entityInfo['associations_table'])),
                    $connection->quoteInto(
                        'main_table.' . $entityInfo['rule_id_field']
                        . ' = customer_group_ids.' . $entityInfo['rule_id_field']
                        . ' AND customer_group_ids.' . $entityInfo['entity_id_field'] . ' = ?',
                        (int)$customerGroupId
                    ),
                    array()
                )
                ->where('from_date is null or from_date <= ?', $now);


            $this->addIsActiveFilter();

            $this->setFlag('website_group_date_filter', true);
        }

        return $this;
    }

}
