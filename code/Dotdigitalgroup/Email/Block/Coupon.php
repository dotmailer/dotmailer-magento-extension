<?php

class Dotdigitalgroup_Email_Block_Coupon extends Mage_Core_Block_Template
{

    /**
     * Generates the coupon code based on the code id.
     *
     * @return bool
     * @throws Exception
     */
    public function generateCoupon()
    {
        $params = $this->getRequest()->getParams();
        if ( ! isset($params['id']) || ! isset($params['code'])) {
            Mage::helper('ddg')->log('Coupon no id or code is set');

            return false;
        }
        //coupon rule id
        $couponCodeId = $params['id'];

        if ($couponCodeId) {

            $rule = Mage::getModel('salesrule/rule')->load($couponCodeId);
            //coupon code id not found
            if ( ! $rule->getId()) {
                Mage::helper('ddg')->log(
                    'Rule with couponId model not found : ' . $couponCodeId
                );

                return false;
            }
            $generator = Mage::getModel('salesrule/coupon_massgenerator');
            $generator->setFormat(
                Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC
            );
            $generator->setRuleId($couponCodeId);
            $generator->setUsesPerCoupon(1);
            $generator->setDash(3);
            $generator->setLength(9);
            $generator->setPrefix('DOT-');
            $generator->setSuffix('');
            //set the generation settings
            $rule->setCouponCodeGenerator($generator);
            $rule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
            //generate the coupon
            $coupon     = $rule->acquireCoupon();
            $couponCode = $coupon->getCode();
            //save the type of coupon
            $couponModel = Mage::getModel('salesrule/coupon')
                ->loadByCode(
                    $couponCode
                );
            $couponModel->setType(Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON)
                ->setGeneratedByDotmailer(1);

            if ($this->validateDate($params['expiration_date'])) {
                $couponModel->setExpirationDate($params['expiration_date']);
            } elseif ($rule->getToDate()) {
                $couponModel->setExpirationDate($rule->getToDate());
            }
            $couponModel->save();

            return $couponCode;
        }

        return false;
    }

    /**
     * Validate date
     *
     * @param $date
     * @return bool
     */
    protected function validateDate($date)
    {
        $validator = new Zend_Validate_Date();
        if (isset($date) && $date != '' && $validator->isValid($date)) {
            return true;
        }
        Mage::helper('ddg')->log('Date ' . $date . ' is not valid date');
        return false;
    }

    /**
     * Get style text from config
     *
     * @return array
     */
    protected function getStyle()
    {
        return explode(
            ',', Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_STYLE
        )
        );
    }

    /**
     * Get coupon font color
     *
     * @return mixed
     */
    protected function getCouponColor()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_COLOR
        );
    }

    /**
     * Get font size
     *
     * @return mixed
     */
    protected function getFontSize()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT_SIZE
        );
    }

    /**
     * Get font
     *
     * @return mixed
     */
    protected function getFont()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT
        );
    }

    /**
     * Get background color
     *
     * @return mixed
     */
    protected function getBackgroundColor()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_BG_COLOR
        );
    }
}