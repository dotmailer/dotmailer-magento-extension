<?php

class Dotdigitalgroup_Email_Adminhtml_Email_CampaignController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Constructor - set the used module name.
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Dotdigitalgroup_Email');
    }

    /**
     * Email campaigns.
     */
    public function indexAction()
    {
        $this->_title($this->__('Email'))
            ->_title($this->__('Manage Campaigns'));
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->renderLayout();
    }

    /**
     * Delete campaign.
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            $campaign = Mage::getModel('ddg_automation/campaign')->setId($id);
            try {
                $campaign->delete();
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__('The campaign has been deleted.')
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->getResponse()->setRedirect(
            $this->getUrl(
                '*/*/', array('store' => $this->getRequest()->getParam('store'))
            )
        );
    }

    /**
     * Delete mass campaigns.
     */
    public function massDeleteAction()
    {
        $campaignIds = $this->getRequest()->getParam('campaign');
        if (!is_array($campaignIds)) {
            $this->_getSession()->addError(
                $this->__('Please select campaigns.')
            );
        } else {
            $num = Mage::getResourceModel('ddg_automation/campaign')
                ->massDelete($campaignIds);
            if (is_int($num)) {
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__(
                        'Total of %d record(s) have been deleted.', $num
                    )
                );
            } else {
                $this->_getSession()->addError($num->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Mass mark for resend campaings.
     */
    public function massResendAction()
    {
        $campaignIds = $this->getRequest()->getParam('campaign');
        if (!is_array($campaignIds)) {
            $this->_getSession()->addError(
                $this->__('Please select campaigns.')
            );
        } else {
            $num = Mage::getResourceModel('ddg_automation/campaign')
                ->massResend($campaignIds);

            if (is_int($num)) {
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__(
                        'Total of %d record(s) have resend .', $num
                    )
                );
            } else {
                $this->_getSession()->addError($num->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Main page.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }


    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'email_connector/reports/email_connector_campaign'
        );
    }

    /**
     * Export action.
     */
    public function exportCsvAction()
    {
        $fileName = 'campaign.csv';
        $content  = $this->getLayout()->createBlock(
            'ddg_automation/adminhtml_campaign_grid'
        )
            ->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

}
