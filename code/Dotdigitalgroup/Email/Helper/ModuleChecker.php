<?php

class Dotdigitalgroup_Email_Helper_ModuleChecker
{
    /**
     * Checks if review module is available
     * @return bool
     */
    public function isReviewModuleAvailable()
    {
        if (Mage::helper('core')->isModuleEnabled('Mage_Review') && Mage::getModel('review/review')){
            return true;
        }
        return false;
    }
}
