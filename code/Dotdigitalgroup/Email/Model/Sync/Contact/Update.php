<?php

class Dotdigitalgroup_Email_Model_Sync_Contact_Update extends Dotdigitalgroup_Email_Model_Sync_Contact_Delete
{
    /**
     * @codingStandardsIgnoreStart
     * @param $collection
     */
    public function processCollection($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            $this->client = $this->helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());
            $result = '';

            if ($this->client) {
                if ($item->getImportMode() == Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_EMAIL_UPDATE) {
                    $emailBefore = $importData['emailBefore'];
                    $email = $importData['email'];
                    $isSubscribed = $importData['isSubscribed'];
                    $subscribersAddressBook = $this->helper->getWebsiteConfig(
                        Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
                        $websiteId
                    );
                    $result = $this->client->postContacts($emailBefore);
                    //check for matching email
                    if (isset($result->id)) {
                        if ($email != $result->email) {
                            $data = array(
                                'Email' => $email,
                                'EmailType' => 'Html'
                            );
                            //update the contact with same id - different email
                            $this->client->updateContact($result->id, $data);
                        }

                        if (!$isSubscribed && $result->status == 'Subscribed') {
                            $this->client->deleteAddressBookContact($subscribersAddressBook, $result->id);
                        }
                    }
                } elseif ($item->getImportMode() ==
                    Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_RESUBSCRIBED
                ) {
                    $email = $importData['email'];
                    $apiContact = $this->client->postContacts($email);

                    //resubscribe suppressed contacts
                    if (isset($apiContact->message) && $apiContact->message ==
                        Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_CONTACT_SUPPRESSED
                    ) {
                        $apiContact = $this->client->getContactByEmail($email);
                        $result = $this->client->postContactsResubscribe($apiContact);
                    }
                } elseif ($item->getImportMode() == Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_UPDATE) {
                    $email = $importData['email'];
                    $id = $importData['id'];
                    $result = $this->client->postContacts($email);
                    if (isset($result->id)) {
                        $contactId = $result->id;
                        $this->client->deleteAddressBookContact(
                            Mage::helper('ddg')->getSubscriberAddressBook($websiteId), $contactId
                        );
                    } else {
                        $contactEmail = Mage::getModel('ddg_automation/contact')->load($id);

                        if ($contactEmail->getId()) {
                            $contactEmail->setSuppressed('1')
                                ->save();
                        }
                    }
                }

                if ($result) {
                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }
}