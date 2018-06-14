<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Trial extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    const TRIAL_EXTERNAL_URL = 'https://www.dotmailer.com/trial/';

    /**
     * @codingStandardsIgnoreStart
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('ddg');
        if (! $helper->isFrontendAdminSecure()) {
            $html = '<a class="various" href=' .
                self::TRIAL_EXTERNAL_URL . ' target="_blank"><img style="margin-bottom:15px;" src=' .
                Mage::getDesign()->getSkinUrl('connector/banner.png') .
                ' alt="Open Trial Account"></a>';
        } else {
            $internalUrl = $this->getUrl('adminhtml/trial/index');
            $html = '<a class="various fancybox.iframe" data-fancybox-type="iframe" href=' .
                $internalUrl . '><img style="margin-bottom:15px;" src=' .
                Mage::getDesign()->getSkinUrl('connector/banner.png') .
                ' alt="Open Trial Account"></a>';
            $html .=
                "<script>
                var j = jQuery.noConflict();
                j(document).ready(function() {
                    j('.various').fancybox({
                        width	: 508,
                        height	: 613,
                        scrolling   : 'no',
                        afterClose : function() {
                          location.reload();
                        }
                    });
                }); 
            </script>";
        }

        return $html;
    }
}