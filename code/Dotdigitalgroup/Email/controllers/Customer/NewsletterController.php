<?php

class Dotdigitalgroup_Email_Customer_NewsletterController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * Check customer authentication.
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (! $this->getRequest()->isDispatched()) {
            return;
        }

        if (! $this->_getCustomerSession()->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Get customer session.
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Save action.
     * @codingStandardsIgnoreStart
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Mage_Core_Exception
     */
    public function saveAction()
    {
        if (! $this->_validateFormKey() or
            ! $this->_getCustomerSession()->getConnectorContactId()
        ) {
            return $this->_redirect('customer/account/');
        }

        //params
        $additionalSubscriptions = Mage::app()->getRequest()->getParam(
            'additional_subscriptions'
        );
        $dataFields              = Mage::app()->getRequest()->getParam(
            'data_fields'
        );
        $customerId              = $this->_getCustomerSession()
            ->getConnectorContactId();
        $customerEmail           = $this->_getCustomerSession()->getCustomer()
            ->getEmail();

        //client
        $website = Mage::getModel('customer/session')->getCustomer()->getStore()
            ->getWebsite();
        $client  = Mage::getModel('ddg_automation/apiconnector_client');
        $client->setApiUsername(Mage::helper('ddg')->getApiUsername($website))
            ->setApiPassword(Mage::helper('ddg')->getApiPassword($website));

        $contact = $client->getContactById($customerId);
        if (isset($contact->id)) {
            //contact address books
            $bookError             = false;
            $addressBooks          = $client->getContactAddressBooks(
                $contact->id
            );
            $subscriberAddressBook = Mage::helper('ddg')
                ->getSubscriberAddressBook(Mage::app()->getWebsite());
            $processedAddressBooks = array();
            if (is_array($addressBooks)) {
                foreach ($addressBooks as $addressBook) {
                    if ($subscriberAddressBook != $addressBook->id) {
                        $processedAddressBooks[$addressBook->id]
                            = $addressBook->name;
                    }
                }
            }

            if (isset($additionalSubscriptions)) {
                foreach ($additionalSubscriptions as $additionalSubscription) {
                    if (! isset($processedAddressBooks[$additionalSubscription])) {
                        $bookResponse = $client->postAddressBookContacts(
                            $additionalSubscription, $contact
                        );
                        if (isset($bookResponse->message)) {
                            $bookError = true;
                        }
                    }
                }

                foreach ($processedAddressBooks as $bookId => $name) {
                    if (! in_array($bookId, $additionalSubscriptions)) {
                        $bookResponse = $client->deleteAddressBookContact(
                            $bookId, $contact->id
                        );
                        if (isset($bookResponse->message)) {
                            $bookError = true;
                        }
                    }
                }
            } else {
                foreach ($processedAddressBooks as $bookId => $name) {
                    $bookResponse = $client->deleteAddressBookContact(
                        $bookId, $contact->id
                    );
                    if (isset($bookResponse->message)) {
                        $bookError = true;
                    }
                }
            }

            //contact data fields
            $data            = array();
            $dFields         = $client->getDataFields();
            $processedFields = array();
            foreach ($dFields as $dataField) {
                $processedFields[$dataField->name] = $dataField->type;
            }

            foreach ($dataFields as $key => $value) {
                if (isset($processedFields[$key]) && $value) {
                    if ($processedFields[$key] == 'Numeric') {
                        $dataFields[$key] = (int)$value;
                    }

                    if ($processedFields[$key] == 'String') {
                        $dataFields[$key] = (string)$value;
                    }

                    if ($processedFields[$key] == 'Date') {
                        $date             = new Zend_Date($value, "Y/M/d");
                        $dataFields[$key] = $date->toString(
                            Zend_Date::ISO_8601
                        );
                    }

                    $data[] = array(
                        'Key'   => $key,
                        'Value' => $dataFields[$key]
                    );
                }
            }

            $contactResponse = $client->updateContactDatafieldsByEmail(
                $customerEmail, $data
            );

            if (isset($contactResponse->message) && $bookError) {
                Mage::getSingleton('customer/session')->addError(
                    $this->__(
                        'An error occurred while saving your subscription preferences.'
                    )
                );
            } else {
                Mage::getSingleton('customer/session')->addSuccess(
                    $this->__('The subscription preferences has been saved.')
                );
            }
        } else {
            Mage::getSingleton('customer/session')->addError(
                $this->__(
                    'An error occurred while saving your subscription preferences.'
                )
            );
        }
        //@codingStandardsIgnoreEnd
        $this->_redirect('customer/account/');
    }
}
