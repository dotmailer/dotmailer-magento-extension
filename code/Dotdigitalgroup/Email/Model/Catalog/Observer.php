<?php

class Dotdigitalgroup_Email_Model_Catalog_Observer
{
    /**
     * Update attribute mass action observer for observing attributes, inventory and websites events
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterUpdateAttributes(Varien_Event_Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();

        switch ($eventName) {
            case "catalog_product_attribute_update_after":
                $productIds = $observer->getEvent()->getProductIds();
                break;

            case "catalog_product_stock_item_mass_change":
            case "catalog_product_to_website_change":
                $productIds = $observer->getEvent()->getProducts();
                break;

            default:
                $productIds = array();
                break;
        }

        if (! empty($productIds)) {
            Mage::getResourceModel('ddg_automation/catalog')->setUnProcessed($productIds);
        }
    }
}