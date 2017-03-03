<?php

class Dotdigitalgroup_Email_Adminhtml_Email_ContactController
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
     * Main page.
     */
    public function indexAction()
    {
        $this->_title($this->__('Email'))
            ->_title($this->__('Manage Contacts'));
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->renderLayout();
    }

    /**
     * Edit action. Sync contact and redirect back.
     */
    public function editAction()
    {
        $contactId = (int)$this->getRequest()->getParam('id');
        $contact   = $this->_initAction();
        if ($contactId && ! $contact->getId()) {
            $this->_getSession()->addError(
                Mage::helper('ddg')->__('This contact no longer exists.')
            );
            $this->_redirect('*/*/');

            return;
        }

        $contactEmail = Mage::getModel('ddg_automation/apiconnector_contact')
            ->syncContact();
        if ($contactEmail) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                'Successfully synced : ' . $contactEmail
            );
        }

        Mage::dispatchEvent(
            'email_contact_controller_edit_action', array('contact' => $contact)
        );

        $this->_redirect('*/*');
    }

    /**
     * Delete a contact.
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            $contact = Mage::getModel('ddg_automation/contact')->setId($id);
            try {
                $contact->delete();
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__('The contact has been deleted.')
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
     * Mass delete contacts.
     */
    public function massDeleteAction()
    {
        $contactIds = $this->getRequest()->getParam('contact');
        if (!is_array($contactIds)) {
            $this->_getSession()->addError(
                $this->__('Please select contacts.')
            );
        } else {
            $num = Mage::getResourceModel('ddg_automation/contact')->massDelete(
                $contactIds
            );
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
     * Mark a contact to be resend.
     */
    public function massResendAction()
    {
        $contactIds = $this->getRequest()->getParam('contact');

        if (!is_array($contactIds)) {
            $this->_getSession()->addError(
                $this->__('Please select contacts.')
            );
        } else {
            $num = Mage::getResourceModel('ddg_automation/contact')->massResend(
                $contactIds
            );
            if (is_int($num)) {
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__(
                        'Total of %d record(s) set for resend.', $num
                    )
                );
            } else {
                $this->_getSession()->addError($num->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }


    /**
     * Main grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Export action.
     */
    public function exportCsvAction()
    {
        $fileName = 'contacts.csv';
        $content  = $this->getLayout()->createBlock(
            'ddg_automation/adminhtml_contact_grid'
        )
            ->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'email_connector/reports/email_connector_contact'
        );
    }

    /**
     * @return mixed
     */
    protected function _initAction()
    {
        $contactId = (int)$this->getRequest()->getParam('id');
        $contact   = Mage::getModel('ddg_automation/contact')
            ->setStoreId($this->getRequest()->getParam('store', 0));

        if ($contactId) {
            $contact->load($contactId);
        }

        Mage::register('current_contact', $contact);

        return $contact;
    }
}
