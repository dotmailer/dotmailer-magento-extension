<?php

class Dotdigitalgroup_Email_Block_Recommended_Products
    extends Dotdigitalgroup_Email_Block_Edc
{

    /**
     * Get the products to display for table.
     *
     * @return array
     * @throws Exception
     */
    public function getLoadedProductCollection()
    {
        $orderModel = Mage::registry('current_order');
        if (! $orderModel) {
            Mage::log('no current_order found for EDC');

            return array();
        }

        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        //number of product items to be displayed
        $limit             = Mage::helper('ddg/recommended')
            ->getDisplayLimitByMode(
                $mode
            );
        $orderItems = $orderModel->getAllVisibleItems();
        $productsToDisplay = $this->getProductsToDisplay(
            $orderItems, $limit, $mode, 'PRODUCT'
        );

        return $productsToDisplay;
    }
}