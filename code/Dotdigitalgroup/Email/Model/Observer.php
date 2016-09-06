<?php

class Dotdigitalgroup_Email_Model_Observer
{
    /**
     * before block to html observer
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeBlockToHtml(Varien_Event_Observer $observer)
    {
        $grid = $observer->getBlock();

        /**
         * Mage_Adminhtml_Block_Customer_Grid
         */
        if ($grid instanceof Mage_Adminhtml_Block_Promo_Quote_Edit_Tab_Coupons_Grid) {
            $grid->addColumnAfter(
                'expiration_date',
                array(
                    'header' => Mage::helper('salesrule')->__('Expiration date'),
                    'index' => 'expiration_date',
                    'type' => 'datetime',
                    'default' => '-',
                    'align' => 'center',
                    'width' => '160'
                ),
                'created_at'
            )->addColumnAfter(
                'added_by_dotmailer',
                array(
                    'header' => Mage::helper('salesrule')->__('Created By dotmailer'),
                    'index' => 'added_by_dotmailer',
                    'type' => 'options',
                    'options' => array(null => 'No', '1' => 'Yes', '' => 'No'),
                    'width' => '30',
                    'align' => 'center',
                ),
                'expiration_date'
            );
        }
    }
}