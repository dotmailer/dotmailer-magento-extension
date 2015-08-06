<?php

class Dotdigitalgroup_Email_Helper_Review extends Mage_Core_Helper_Abstract
{
    /**
     * configs
     */
    const XML_PATH_REVIEW_STATUS                                  = 'connector_automation_studio/review_settings/status';
    const XML_PATH_REVIEW_DELAY                                   = 'connector_automation_studio/review_settings/delay';
    const XML_PATH_REVIEW_NEW_PRODUCT                             = 'connector_automation_studio/review_settings/new_product';
    const XML_PATH_REVIEW_CAMPAIGN                                = 'connector_automation_studio/review_settings/campaign';
    const XML_PATH_REVIEW_ANCHOR                                  = 'connector_automation_studio/review_settings/anchor';
    const XML_PATH_REVIEW_DISPLAY_TYPE                            = 'connector_dynamic_content/products/review_display_type';

    /**
     * get config value on website level
     *
     * @param $path
     * @param $website
     * @return mixed
     */
    public function getReviewWebsiteSettings($path, $website)
    {
        $helper = Mage::helper('ddg');
        return $helper->getWebsiteConfig($path, $website);
    }

    /**
     * @param $website
     * @return boolean
     */
    public function isEnabled($website)
    {
        return $this->getReviewWebsiteSettings(Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_ENABLED, $website);
    }

    /**
     * @param $website
     * @return string
     */
    public function getOrderStatus($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_STATUS, $website);
    }

    /**
     * @param $website
     * @return int
     */
    public function getDelay($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_DELAY, $website);
    }

    /**
     * @param $website
     * @return boolean
     */
    public function isNewProductOnly($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_NEW_PRODUCT, $website);
    }

    /**
     * @param $website
     * @return int
     */
    public function getCampaign($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_CAMPAIGN, $website);
    }

    /**
     * @param $website
     * @return string
     */
    public function getAnchor($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_ANCHOR, $website);
    }

    /**
     * @param $website
     * @return string
     */
    public function getDisplayType($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_DISPLAY_TYPE, $website);
    }
}
