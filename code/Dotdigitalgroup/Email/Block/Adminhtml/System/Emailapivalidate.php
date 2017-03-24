<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Emailapivalidate
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setData('onchange', "apiValidation(this.form, this);");

        $url = Mage::helper('adminhtml')->getUrl('*/connector/ajaxvalidation');

        $element->setData(
            'after_element_html',
            "<script>
                document.observe('dom:loaded', function(){
                    apiValidation();

                 });
                function apiValidation(form, element) {
                    var api_user       = $('connector_transactional_emails_credentials_api_username');
                    var api_password   = $('connector_transactional_emails_credentials_api_password');
                    var reloadurl  = '{$url}';
                    var encoded = btoa(api_password.value);
                    new Ajax.Request(reloadurl, {
                        method: 'post',
                        parameters: {'api_username' : api_user.value, 'api_password' : encoded},
                        onComplete: function(transport) {
                            Element.hide('loadingmask');
                            if(transport.responseText == '\"Credentials Valid.\"'){
                                api_user.setStyle({
                                    fontWeight: 'bold',
                                    color:  'green' ,
                                    background: 'transparent url(\""
            . $this->getSkinUrl('images/success_msg_icon.gif') . "\") no-repeat right center'
                                })
                            }else{
                                api_user.setStyle({
                                    fontWeight: 'bold',
                                    color:  'red',
                                    background: 'transparent url(\""
            . $this->getSkinUrl('images/error_msg_icon.gif') . "\") no-repeat right center'
                                });

                            }
                        }
                    });

                    return false;
                }

            </script>"
        );

        return parent::_getElementHtml($element);

    }
}