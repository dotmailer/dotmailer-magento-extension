<?php

class Dotdigitalgroup_Email_Model_Resource_Coupon extends Mage_SalesRule_Model_Resource_Coupon
{

    /**
     * Update auto generated Specific Coupon if it's rule changed
     *
     * @param Mage_SalesRule_Model_Rule $rule
     * @return Mage_SalesRule_Model_Resource_Coupon
     */
    public function updateSpecificCoupons(Mage_SalesRule_Model_Rule $rule)
    {

        if (!$rule || !$rule->getId() || !$rule->hasDataChanges()) {
            return $this;
        }

        $updateArray = array();
        if ($rule->dataHasChangedFor('uses_per_coupon')) {
            $updateArray['usage_limit'] = $rule->getUsesPerCoupon();
        }

        if ($rule->dataHasChangedFor('uses_per_customer')) {
            $updateArray['usage_per_customer'] = $rule->getUsesPerCustomer();
        }

        $ruleNewDate = new Zend_Date($rule->getToDate());
        $ruleOldDate = new Zend_Date($rule->getOrigData('to_date'));

        if ($ruleNewDate->compare($ruleOldDate)) {
            $updateArray['expiration_date'] = $rule->getToDate();
        }

        if (!empty($updateArray)) {
            $this->_getWriteAdapter()->update(
                $this->getTable('salesrule/coupon'),
                $updateArray,
                array('rule_id = ?' => $rule->getId(), 'added_by_dotmailer is null')
            );
        }

        return $this;
    }
}
