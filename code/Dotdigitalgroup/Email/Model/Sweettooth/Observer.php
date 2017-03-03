<?php

class Dotdigitalgroup_Email_Model_Sweettooth_Observer
{

    /**
     * @param $observer
     * @return $this
     */
    public function ConnectorRewardsPointsIndexerUpdate($observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        if (! $customer) {
            return $this;
        }

        $helper  = Mage::helper('ddg');
        $website = Mage::app()->getWebsite($customer->getWebsiteId());

        if ($helper->isSweetToothToGo($website)) {
            $helper->setConnectorContactToReImport($customer->getId());
        }

        return $this;
    }

}