<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Reviews_Campaigns
{

    /**
     * Returns account's Campaigns.
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function toOptionArray()
    {
        $fields = array();
        $client = Mage::getModel('ddg_automation/apiconnector_client');

        $website = Mage::app()->getRequest()->getParam('website', false);
        if ($website) {
            $website = Mage::app()->getWebsite($website);
        } else {
            $website = 0;
        }
        $client->setApiUsername(Mage::helper('ddg')->getApiUsername($website));
        $client->setApiPassword(Mage::helper('ddg')->getApiPassword($website));

        $savedCampaigns = Mage::registry('savedcampignsforreview');

        if ($savedCampaigns) {
            $campaigns = $savedCampaigns;
        } else {
            $campaigns = $client->getCampaigns();
            Mage::unregister('savedcampignsforreview');
            Mage::register('savedcampignsforreview', $campaigns);
        }

        $fields[] = array('value' => '0', 'label' => Mage::helper('ddg')->__('-- Please Select --'));

        if (is_array($fields)) {
            foreach ( $campaigns as $one ) {
                if ( isset( $one->id ) )
                    $fields[] = array( 'value' => $one->id, 'label' => Mage::helper( 'ddg' )->__( addslashes($one->name)) );
            }
        }

        return $fields;
    }

}