<?php

class Dotdigitalgroup_Email_Customer_NewsletterController extends Mage_Core_Controller_Front_Action
{

    /**
     * Check customer authentication
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        if (!$this->_getCustomerSession()->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * get customer session
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * save action
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Mage_Core_Exception
     */
    public function saveAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('customer/account/');
        }
        //params
        $additional_subscriptions = Mage::app()->getRequest()->getParam('additional_subscriptions');
        $data_fields = Mage::app()->getRequest()->getParam('data_fields');
        $customer_id = Mage::app()->getRequest()->getParam('connector_customer_id');
        $customer_email = Mage::app()->getRequest()->getParam('connector_customer_email');

        //client
        $website = Mage::getModel('customer/session')->getCustomer()->getStore()->getWebsite();
        $client = Mage::getModel('ddg_automation/apiconnector_client');
        $client->setApiUsername(Mage::helper('ddg')->getApiUsername($website))
            ->setApiPassword(Mage::helper('ddg')->getApiPassword($website));

        $contact = $client->getContactById($customer_id);
        if(isset($contact->id)){
            //contact address books
            $bookError = false;
            $addressBooks = $client->getContactAddressBooks($contact->id);
            $subscriberAddressBook = Mage::helper('ddg')->getSubscriberAddressBook(Mage::app()->getWebsite());
            $processedAddressBooks = array();
            if(is_array($addressBooks)){
                foreach($addressBooks as $addressBook){
                    if($subscriberAddressBook != $addressBook->id)
                        $processedAddressBooks[$addressBook->id] = $addressBook->name;
                }
            }
            if(isset($additional_subscriptions)){
                foreach($additional_subscriptions as $additional_subscription){
                    if(!isset($processedAddressBooks[$additional_subscription])){
                        $bookResponse = $client->postAddressBookContacts($additional_subscription, $contact);
                        if(isset($bookResponse->message))
                            $bookError = true;

                    }
                }
                foreach($processedAddressBooks as $bookId => $name){
                    if(!in_array($bookId, $additional_subscriptions)) {
                        $bookResponse = $client->deleteAddressBookContact($bookId, $contact->id);
                        if(isset($bookResponse->message))
                            $bookError = true;
                    }
                }
            }
            else{
                foreach($processedAddressBooks as $bookId => $name){
                    $bookResponse = $client->deleteAddressBookContact($bookId, $contact->id);
                    if(isset($bookResponse->message))
                        $bookError = true;
                }
            }

            //contact data fields
            $data = array();
            $dataFields = $client->getDataFields();
            $processedFields = array();
            foreach($dataFields as $dataField){
                $processedFields[$dataField->name] = $dataField->type;
            }
            foreach($data_fields as $key => $value){
                if(isset($processedFields[$key]) && $value){
                    if($processedFields[$key] == 'Numeric'){
                        $data_fields[$key] = (int)$value;
                    }
                    if($processedFields[$key] == 'String'){
                        $data_fields[$key] = (string)$value;
                    }
                    if($processedFields[$key] == 'Date'){
                        $date = new Zend_Date($value, "Y/M/d");
                        $data_fields[$key] = $date->toString(Zend_Date::ISO_8601);
                    }
                    $data[] = array(
                        'Key' => $key,
                        'Value' => $data_fields[$key]
                    );
                }
            }
            $contactResponse = $client->updateContactDatafieldsByEmail($customer_email, $data);

            if(isset($contactResponse->message) && $bookError)
                Mage::getSingleton('customer/session')->addError($this->__('An error occurred while saving your subscription preferences.'));
            else
                Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription preferences has been saved.'));
        }
        else{
            Mage::getSingleton('customer/session')->addError($this->__('An error occurred while saving your subscription preferences.'));
        }
        $this->_redirect('customer/account/');
    }
}
