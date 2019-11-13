<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Dynamic_Timeperiod
{
    protected static $_options;

    const MAP_YEARLY = 'Y';
    const MAP_MONTHLY = 'M';
    const MAP_WEEKLY = 'W';

    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = array(
                array(
                    'label' => Mage::helper('cron')->__('Yearly'),
                    'value' => self::MAP_YEARLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Monthly'),
                    'value' => self::MAP_MONTHLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Weekly'),
                    'value' => self::MAP_WEEKLY,
                ),
            );
        }
        return self::$_options;
    }
}