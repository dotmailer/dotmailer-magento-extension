<?php

class Dotdigitalgroup_Email_Adminhtml_Email_CampaignController extends Mage_Adminhtml_Controller_Action
{
    /**
     * constructor - set the used module name
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
	 * New campaings.
	 */
    public function newAction()
    {
        // We just forward the new action to a blank edit form
        $this->_forward('edit');
    }


    /**
	 * Edit campign.
	 */
    public function editAction()
    {
        $contactId  = (int) $this->getRequest()->getParam('id');
        $contact = $this->_initAction();
        if ($contactId && !$contact->getId()) {
            $this->_getSession()->addError(Mage::helper('ddg')->__('This campaign no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }
        if ($data = Mage::getSingleton('adminhtml/session')->getCampaignData(true)) {
            $contact->setData($data);
        }
        Mage::dispatchEvent('email_campaign_controller_edit_action', array('contact' => $contact));
        $this->loadLayout();
        if ($contact->getId()) {
            if (!Mage::app()->isSingleStoreMode() && ($switchBlock = $this->getLayout()->getBlock('store_switcher'))) {
                $switchBlock->setDefaultStoreName(Mage::helper('ddg')->__('Default Values'))
                    ->setSwitchUrl($this->getUrl('*/*/*', array('_current'=>true, 'active_tab'=>null, 'tab' => null, 'store'=>null)));
            }
        } else {
            $this->getLayout()->getBlock('left')->unsetChild('store_switcher');
        }
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
    }

    /**
	 * Save campaign.
	 */
    public function saveAction()
    {
        $storeId        = $this->getRequest()->getParam('store');
        $redirectBack   = $this->getRequest()->getParam('back', false);
        $contactId      = $this->getRequest()->getParam('id');
        $data = $this->getRequest()->getPost();
        if ($data) {
            $campaign    = $this->_initAction();

            $campaignData = $this->getRequest()->getPost('campaign', array());
            $campaign->addData($campaignData);

            try {
                $campaign->save();
                $this->_getSession()->addSuccess(Mage::helper('ddg')->__('Campaign was saved.'));
            }catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage())
                    ->setContactData($campaignData);
                $redirectBack = true;
            }catch (Exception $e){
                Mage::logException($e);
                $this->_getSession()->addError(Mage::helper('ddg')->__('Error saving campaign'))
                    ->setContactData($campaignData);
                $redirectBack = true;
            }
        }
        if ($redirectBack) {
            $this->_redirect('*/*/edit', array(
                'id'    => $contactId,
                '_current'=>true
            ));
        } else {
            $this->_redirect('*/*/', array('store'=>$storeId));
        }
    }

    /**
	 * Delete campaign.
	 */
    public function deleteAction()
	{
        if ($id = $this->getRequest()->getParam('id')) {
            $campaign = Mage::getModel('ddg_automation/campaign')->load($id);
            try {
                $campaign->delete();
                $this->_getSession()->addSuccess(Mage::helper('ddg')->__('The campaign has been deleted.'));
            }
            catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/', array('store'=>$this->getRequest()->getParam('store'))));
    }

    /**
	 * Delete mass campaigns.
	 */
    public function massDeleteAction()
	{
        $campaignIds = $this->getRequest()->getParam('campaign');
        if (!is_array($campaignIds)) {
            $this->_getSession()->addError($this->__('Please select campaigns.'));
        } else {
            $num = Mage::getResourceModel('ddg_automation/campaign')->massDelete($campaignIds);
            if(is_int($num)){
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__('Total of %d record(s) have been deleted.', $num));
            }
            else
                $this->_getSession()->addError($num->getMessage());
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
            $this->_getSession()->addError($this->__('Please select campaigns.'));
        } else {
            $num = Mage::getResourceModel('ddg_automation/campaign')->massResend($campaignIds);
            if(is_int($num)){
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__('Total of %d record(s) have resend .', $num)
                );
            }else
                $this->_getSession()->addError($num->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    /**
	 * main page.
	 */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
	 * manage the campaigns.
	 *
	 * @return Dotdigitalgroup_Email_Model_Campaign
	 */
    protected function _initAction()
    {
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('Manage Campaigns'));

        $campaignId  = (int) $this->getRequest()->getParam('id');
        $campaign    = Mage::getModel('ddg_automation/campaign');

        if ($campaignId) {
            $campaign->load($campaignId);
        }
        Mage::register('email_campaign', $campaign);
        return $campaign;
    }

    /**
	 * Export campaigns to CSV file.
	 */
    public function exportCsvAction()
    {
        $fileName   = 'campaign.csv';
        $content  = $this->getLayout()->createBlock('ddg_automation/adminhtml_campaign_grid')
            ->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('email_connector/reports/email_connector_campaign');
    }

}
