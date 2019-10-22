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
                        $subscribersAddressBook = Mage::helper('ddg')->getSubscriberAddressBook($websiteId);
                        $result = ($subscribersAddressBook) ?
                            $this->client->postAddressBookContactResubscribe($subscribersAddressBook, $email) :
                            $this->client->postContactsResubscribe($this->client->getContactByEmail($email));
                    }
                } elseif ($item->getImportMode() == Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_UPDATE) {
                    $result = $this->handleUnsubscribe($importData, $websiteId);
                }

                if ($result) {
                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }

    /**
     * Handle a contact unsubscribe:
     * - set SUBSCRIBER_STATUS data field to 'Unsubscribed'
     * - if EC contact exists and has been updated, delete contact from the subscribers address book
     * - if not, mark as suppressed in our table
     *
     * @param array $importData
     * @param string $websiteId
     * @return mixed
     */
    protected function handleUnsubscribe($importData, $websiteId)
    {
        $email = $importData['email'];
        $id = $importData['id'];

        $subscriberStatuses = Mage::getModel('ddg_automation/apiconnector_customer')
            ->subscriberStatus;
        $unsubscribedValue = $subscriberStatuses[Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED];

        $data[] = [
            'Key' => 'SUBSCRIBER_STATUS',
            'Value' => $unsubscribedValue
        ];

        $result = $this->client->updateContactDatafieldsByEmail($email, $data);
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

        return $result;
    }
}