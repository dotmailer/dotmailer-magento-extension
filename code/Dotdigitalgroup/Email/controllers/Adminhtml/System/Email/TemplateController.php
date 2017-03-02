<?php

require_once 'Mage/Adminhtml/controllers/System/Email/TemplateController.php';

class Dotdigitalgroup_Email_Adminhtml_System_Email_TemplateController
    extends Mage_Adminhtml_System_Email_TemplateController
{

    /**
     * Set template data to retrieve it in template info form.
     */
    public function defaultTemplateAction()
    {
        if (! $this->getRequest()->getParam('connector') or $this->getRequest()
                ->getParam('connector') == ''
        ) {
            parent::defaultTemplateAction();
        }

        $template            = $this->_initTemplate('id');
        $templateCode        = $this->getRequest()->getParam('code');
        $connectorTemplateId = $this->getRequest()->getParam('connector');

        $template->loadDefault(
            $templateCode, $this->getRequest()->getParam('locale')
        );
        $template->setData('orig_template_code', $templateCode);
        $template->setData(
            'template_variables',
            Zend_Json::encode($template->getVariablesOptionArray(true))
        );

        $templateBlock = $this->getLayout()->createBlock(
            'adminhtml/system_email_template_edit'
        );
        $template->setData(
            'orig_template_used_default_for',
            $templateBlock->getUsedDefaultForPaths(false)
        );

        if ($connectorTemplateId) {
            $client            = Mage::helper('ddg')->getWebsiteApiClient(
                Mage::app()->getWebsite()
            );
            if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
                $connectorTemplate = $client->getApiTemplate($connectorTemplateId);
                if (isset($connectorTemplate->id)) {
                    $template->setTemplateText($connectorTemplate->htmlContent);
                }

                $template->setTemplateStyles('');
            }
        }

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode($template->getData())
        );
    }
}