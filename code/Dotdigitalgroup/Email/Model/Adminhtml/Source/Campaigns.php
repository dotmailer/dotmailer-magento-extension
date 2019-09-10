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

        $fields[] = array(
            'value' => '0',
            'label' => Mage::helper('ddg')->__('-- Please Select --')
        );

        if ($websiteName) {
            $website = Mage::app()->getWebsite($websiteName);
        }

        $enabled = Mage::helper('ddg')->isEnabled($website);


        //api enabled get campaigns
        if ($enabled) {
            $savedCampaigns = Mage::registry('savedcampaigns');

            //get campaigns from registry
            if ($savedCampaigns) {
                $campaigns = $savedCampaigns;
            } else {
                $campaigns = $this->fetchCampaigns($website);
            }

            //@codingStandardsIgnoreStart
            foreach ($campaigns as $one) {
                if (isset($one->id)) {
                    $fields[] = array(
                        'value' => $one->id,
                        'label' => Mage::helper('ddg')->__(addslashes($one->name))
                    );
                }
            }
            //@codingStandardsIgnoreEnd
        }

        return $fields;
    }

    /**
     * @param int $website
     * @return array
     * @throws \Exception
     */
    private function fetchCampaigns($website)
    {
        $client = Mage::helper('ddg')->getWebsiteApiClient($website);
        if (!$client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            return;
        }

        $campaigns = [];

        do {
            // due to the API limitation of 1000 campaign responses, loop while the campaigns returned === 1000,
            // skipping by the count of the total received so far
            if (!is_array($campaignResponse = $client->getCampaigns(count($campaigns)))) {
                return (array) $campaignResponse;
            }
            $campaigns = array_merge($campaigns, $campaignResponse);
        } while (count($campaignResponse) === 1000);

        Mage::unregister('savedcampaigns');
        Mage::register('savedcampaigns', $campaigns);

        return $campaigns;
    }

}
