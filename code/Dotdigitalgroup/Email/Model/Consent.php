<?php

class Dotdigitalgroup_Email_Model_Consent extends Mage_Core_Model_Abstract
{
    const CONSENT_TEXT_LIMIT = '1000';

    /**
     * Single fields for the consent contact.
     *
     * @var array
     */
    public $singleFields = array(
        'DATETIMECONSENTED',
        'URL',
        'USERAGENT',
        'IPADDRESS'
    );

    /**
     * Bulk api import for consent contact fields.
     *
     * @var array
     */
    static public $bulkFields = array(
        'CONSENTTEXT',
        'CONSENTURL',
        'CONSENTDATETIME',
        'CONSENTIP',
        'CONSENTUSERAGENT'
    );

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/consent');
    }

    /**
     * @param $websiteId
     * @param $email
     * @return array
     */
    public function getConsentDataByContact($websiteId, $email)
    {
        $configHelper = Mage::helper('ddg/config');
        //model not loaded try to load with contact email data
        if (! $this->getId()) {
            $consentResource = Mage::getResourceModel('ddg_automation/consent');
            //load model using email and website id
            $contactModel = Mage::getModel('ddg_automation/contact')->getCollection()
                ->loadByCustomerEmail($email, $websiteId);
            if ($contactModel) {
                $consentResource->load($this, $contactModel->getEmailContactId(), 'email_contact_id');
            }
        }
        //consent from the customer registration page or checkout
        if ($this->isLinkMatchCustomerRegistrationOrCheckout($this->getConsentUrl())) {
            if (! $configHelper->isConsentCustomerEnabled($websiteId)) {
                return [];
            }
            $consentText = $configHelper->getConsentCustomerText($websiteId);
        } else {

            if (! $configHelper->isConsentSubscriberEnabled($websiteId)) {
                return [];
            }
            $consentText = $configHelper->getConsentSubscriberText($websiteId);
        }

        $dateTimeConsent = Mage::getModel('core/date')->date(
            Zend_Date::ISO_8601, $this->getConsentDatetime()
        );
        return $consentData = [
            $consentText,
            $this->getConsentUrl(),
            $dateTimeConsent,
            $this->getConsentIp(),
            $this->getConsentUserAgent()
        ];
    }

    /**
     * @param $consentUrl
     * @return bool
     */
    private function isLinkMatchCustomerRegistrationOrCheckout($consentUrl)
    {
        if (strpos($consentUrl, 'checkout/') !== false || strpos($consentUrl, 'customer/account/') !== false) {
            return true;
        }

        return false;
    }
}