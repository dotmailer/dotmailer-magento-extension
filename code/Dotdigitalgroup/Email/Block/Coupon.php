<?php

class Dotdigitalgroup_Email_Block_Coupon extends Mage_Core_Block_Template
{
    /**
	 * Generates the coupon code based on the code id.
	 * @return bool
	 * @throws Exception
	 */
    public function generateCoupon()
    {
        $params = $this->getRequest()->getParams();
        if (!isset($params['id']) || !isset($params['code']))
            throw new Exception('Coupon no id or code is set');
        //coupon rule id
        $couponCodeId = $params['id'];
        $authCode = $params['code'];

        //authenticate
        Mage::helper('connector')->auth($authCode);

        if ($couponCodeId) {

            $rule = Mage::getModel('salesrule/rule')->load($couponCodeId);
            $generator = Mage::getModel('salesrule/coupon_massgenerator');
            $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC );
            $generator->setRuleId($couponCodeId);
            $generator->setUsesPerCoupon(1);
            $generator->setDash(3);
            $generator->setLength(9);
            $generator->setPrefix('');
            $generator->setSuffix('');
            //set the generation settings
            $rule->setCouponCodeGenerator($generator);
            $rule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
            //generate the coupon
            $coupon = $rule->acquireCoupon();
            $couponCode = $coupon->getCode();
            //save the type of coupon
            $couponModel = Mage::getModel('salesrule/coupon')->loadByCode($couponCode);
            $couponModel->setType(Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON);
            $couponModel->save();

            return $couponCode;
        }
        return false;
    }

}