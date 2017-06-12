<?php

/**
 * Class Dotdigitalgroup_Email_Block_Adminhtml_System_Automation_Connect
 */
class Dotdigitalgroup_Email_Block_Adminhtml_System_Automation_Connect
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element
    ) 
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml();
    }

    /**
     * @return string
     */
    protected function _getAddRowButtonHtml()
    {
        $url          = Mage::helper('ddg')->getAuthoriseUrl();
        // disable if ssl is missing
        $disabled     = !$this->_checkForSecureUrl();
        $adminUser    = Mage::getSingleton('admin/session')->getUser();
        $refreshToken = $adminUser->getRefreshToken();
        $title        = $this->__($refreshToken ? 'Disconnect' : 'Connect');
        $url          = $refreshToken ? $this->getUrl('*/email_studio/disconnect') : $url;

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setDisabled($disabled)
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }

    /**
     * @return $this|bool
     */
    protected function _checkForSecureUrl()
    {
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
        return preg_match('/https/', $baseUrl) ? $this : false;
    }
}
