<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Campaigns
{

    /**
     * Returns the campaigns options.
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function toOptionArray()
    {
        $fields = array();
        $websiteName = Mage::app()->getRequest()->getParam('website', false);

        $website = Mage::app()->getRequest()->getParam('website', false);
        if ($website) {
            $website = Mage::app()->getWebsite($website);
        } else {
            $website = 0;
        }
        $fields[] = array('value' => '0', 'label' => Mage::helper('ddg')->__(
            '-- Please Select --'
        ));

        if ($websiteName) {
            $website = Mage::app()->getWebsite($websiteName);
        }

        $enabled = Mage::helper('ddg')->isEnabled($website);
        $client = Mage::helper('ddg')->getWebsiteApiClient($website);

        //api enabled get campaigns
        if ($enabled && $client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            $savedCampaigns = Mage::registry('savedcampigns');

            //get campaigns from registry
            if ($savedCampaigns) {
                $campaigns = $savedCampaigns;
            } else {
                $campaigns = $client->getCampaigns();
                Mage::unregister('savedcampigns');
                Mage::register('savedcampigns', $campaigns);
            }

            foreach ($campaigns as $one) {
                if (isset($one->id)) {
                    $fields[] = array(
                        'value' => $one->id,
                        'label' => Mage::helper('ddg')->__(
                            addslashes($one->name)
                        )
                    );
                }
            }
        }

        return $fields;
    }

}