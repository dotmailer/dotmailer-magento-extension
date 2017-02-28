<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Trial extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('ddg');
        if (!$helper->isFrontendAdminSecure()) {
            $html = '<a class="various" href=' .
                Mage::getDesign()->getSkinUrl('connector/trialerror.png') . '><img style="margin-bottom:15px;" src=' .
                Mage::getDesign()->getSkinUrl('connector/banner.png') .
                ' alt="Open Trial Account"></a>';
            $script =
            "<script>
                var j = jQuery.noConflict();
                j(document).ready(function() {
                    j('.various').fancybox();   
                }); 
            </script>";
        } else {
            $html = '<a class="various fancybox.iframe" data-fancybox-type="iframe" href=' .
                $helper->getIframeFormUrl() . '><img style="margin-bottom:15px;" src=' .
                Mage::getDesign()->getSkinUrl('connector/banner.png') .
                ' alt="Open Trial Account"></a>';
            $script =
            "<script>
                var j = jQuery.noConflict();
                j(document).ready(function() {
                    j('.various').fancybox({
                        width	: 508,
                        height	: 612,
                        scrolling   : 'no',
                        fitToView	: false,
                        autoSize	: false,
                        closeClick	: false,
                        openEffect	: 'none',
                        closeEffect	: 'none'
                    });
                    
                    j(document).on('click', 'a.fancybox-close', function(){
                        location.reload();
                    });
                }); 
            </script>";
        }

        return $html . $script;
    }
}