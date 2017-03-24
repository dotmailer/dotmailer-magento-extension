<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Subscriber
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Render grid columns.
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = $this->_getValue($row);
        $storeId = $row->getStoreId();
        $websiteHash = Mage::getSingleton('adminhtml/system_store')
            ->getWebsiteOptionHash(true);
        if ($value == 0 && $storeId > 0) {
            return $websiteHash[Mage::app()->getStore($storeId)->getWebsite()->getId()];
        }

        return $websiteHash[$value];
    }
}
