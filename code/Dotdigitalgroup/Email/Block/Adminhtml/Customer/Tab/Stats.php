<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Customer_Tab_Stats
    extends Mage_Adminhtml_Block_Template
{

    /**
     * @var array
     */
    public $stats = array();

    /**
     * Constructor.
     */
    public function _construct()
    {
        $this->setTemplate('connector/customer/stats.phtml');
    }

    protected function _getCampaignStatsForCustomer()
    {
        $id       = Mage::app()->getRequest()->getParam('id');
        $customer = Mage::getModel('customer/customer')->load($id);
        $email    = $customer->getEmail();
        $website  = $customer->getStore()->getWebsite();

        $client  = Mage::helper('ddg')->getWebsiteApiClient($website);
        if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            $contact = $client->postContacts($email);
            if (! isset($contact->message)) {
                $locale = Mage::app()->getLocale()->getLocale();
                //@codingStandardsIgnoreStart
                $date = Zend_Date::now($locale)->subDay(30);
                $response = $client->getCampaignsWithActivitySinceDate(
                    $date->toString(Zend_Date::ISO_8601)
                );
                //@codingStandardsIgnoreEnd
                if (! isset($response->message) && is_array($response)) {
                    foreach ($response as $one) {
                        $result = $client->getCampaignActivityByContactId(
                            $one->id, $contact->id
                        );
                        if (!empty($result) && !isset($result->message) && $result !== null) {
                            $this->stats[$one->name] = $result;
                        }
                    }
                }
            }
        }
    }

    /**
     * Get stats for customer.
     *
     * @return array
     */
    public function getStats()
    {
        $this->_getCampaignStatsForCustomer();

        return $this->stats;
    }
}