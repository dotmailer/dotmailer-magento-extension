<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Trial extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<a class="various fancybox.iframe" data-fancybox-type="iframe" href=' . Mage::helper('ddg')->getIframeFormUrl() . '>Open Trial Account</a>';
        $script = "
            <script 'typ'>
                var j = jQuery.noConflict();
                j(document).ready(function() {
                    j('.various').fancybox({
                        maxWidth	: 508,
                        maxHeight	: 641,
                        fitToView	: false,
                        width		: '100%',
                        height		: '100%',
                        autoSize	: false,
                        closeClick	: false,
                        openEffect	: 'none',
                        closeEffect	: 'none'
                    });
                });
            </script>
        ";
        return $html . $script;
    }
}