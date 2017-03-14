<?php

class Dotdigitalgroup_Email_Block_Customer_Account_Books
    extends Mage_Customer_Block_Account_Dashboard
{

    /**
     * @var Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    public $client;

    /**
     * @var int
     */
    public $contactId;

    /**
     * Subscription pref save url.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('connector/customer_newsletter/save');
    }

    /**
     * Get config values.
     *
     * @param $path
     * @param $website
     *
     * @return mixed
     */
    protected function _getWebsiteConfigFromHelper($path, $website)
    {
        return Mage::helper('ddg')->getWebsiteConfig($path, $website);
    }

    /**
     * Get api client.
     *
     * @return Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    protected function _getApiClient()
    {
        if (empty($this->client)) {
            $website = $this->getCustomer()->getStore()->getWebsite();
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);
            $this->client = $client;
        }

        return $this->client;
    }

    /**
     * can show additional books?
     *
     * @return mixed
     */
    public function getCanShowAdditionalBooks()
    {
        return $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS,
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * Getter for additional books. Fully processed.
     *
     * @return array
     */
    public function getAdditionalBooksToShow()
    {
        $additionalBooksToShow = array();
        $additionalFromConfig  = $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS,
            $this->getCustomer()->getStore()->getWebsite()
        );

        if ($additionalFromConfig !== '') {
            $additionalFromConfig = explode(',', $additionalFromConfig);
            $this->getConnectorContact();
            if ($this->contactId) {
                $addressBooks          = $this->_getApiClient()
                    ->getContactAddressBooks(
                        $this->contactId
                    );
                $processedAddressBooks = array();
                if (is_array($addressBooks)) {
                    foreach ($addressBooks as $addressBook) {
                        $processedAddressBooks[$addressBook->id]
                            = $addressBook->name;
                    }
                }

                foreach ($additionalFromConfig as $bookId) {
                    $connectorBook = $this->_getApiClient()->getAddressBookById(
                        $bookId
                    );
                    if (isset($connectorBook->id)) {
                        $subscribed = 0;
                        if (isset($processedAddressBooks[$bookId])) {
                            $subscribed = 1;
                        }

                        $additionalBooksToShow[] = array(
                            "name"       => $connectorBook->name,
                            "value"      => $connectorBook->id,
                            "subscribed" => $subscribed
                        );
                    }
                }
            }
        }

        return $additionalBooksToShow;
    }

    /**
     * can show data fields?
     *
     * @return mixed
     */
    public function getCanShowDataFields()
    {
        return $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS,
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * Get datafields to show. Fully processed.
     *
     * @codingStandardsIgnoreStart
     *
     * @return array
     */
    public function getDataFieldsToShow()
    {
        $datafieldsToShow     = array();
        $dataFieldsFromConfig = $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS,
            $this->getCustomer()->getStore()->getWebsite()
        );
        if ($dataFieldsFromConfig !== '') {
            $dataFieldsFromConfig = explode(',', $dataFieldsFromConfig);
            $contact              = $this->getConnectorContact();
            if ($this->contactId) {
                $contactDataFields          = $contact->dataFields;
                $processedContactDataFields = array();
                foreach ($contactDataFields as $contactDataField) {
                    $processedContactDataFields[$contactDataField->key]
                        = $contactDataField->value;
                }

                $connectorDataFields          = $this->_getApiClient()
                    ->getDataFields();
                $processedConnectorDataFields = array();
                foreach ($connectorDataFields as $connectorDataField) {
                    $processedConnectorDataFields[$connectorDataField->name]
                        = $connectorDataField;
                }

                foreach ($dataFieldsFromConfig as $dataFieldFromConfig) {
                    if (isset($processedConnectorDataFields[$dataFieldFromConfig])) {
                        $value = "";
                        if (isset(
                            $processedContactDataFields[$processedConnectorDataFields[$dataFieldFromConfig]
                                ->name]
                        )) {
                            if ($processedConnectorDataFields[$dataFieldFromConfig]->type == "Date") {
                                $value = $processedContactDataFields[
                                    $processedConnectorDataFields[$dataFieldFromConfig]->name
                                ];
                                //@codingStandardsIgnoreStart
                                $value = Mage::app()->getLocale()->date($value)->toString("Y/M/d");
                                //@codingStandardsIgnoreEnd
                            } else {
                                $value = $processedContactDataFields[
                                    $processedConnectorDataFields[$dataFieldFromConfig]->name
                                ];
                            }
                        }

                        $datafieldsToShow[] = array(
                            'name'  => $processedConnectorDataFields[$dataFieldFromConfig]->name,
                            'type'  => $processedConnectorDataFields[$dataFieldFromConfig]->type,
                            'value' => $value
                        );
                    }
                }
            }
        }

        //@codingStandardsIgnoreEnd

        return $datafieldsToShow;
    }

    /**
     * Find out if anything is true.
     *
     * @return bool
     */
    public function canShowAnything()
    {
        if ($this->getCanShowDataFields() or $this->getCanShowAdditionalBooks()
        ) {
            $books  = $this->getAdditionalBooksToShow();
            $fields = $this->getDataFieldsToShow();
            if (!empty($books) or !empty($fields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get connector contact.
     *
     * @return mixed
     */
    public function getConnectorContact()
    {
        $contact = $this->_getApiClient()->getContactByEmail(
            $this->getCustomer()->getEmail()
        );
        if (isset($contact->id)) {
            $this->_getCustomerSession()->setConnectorContactId($contact->id);
            $this->contactId = $contact->id;
        } else {
            $contact = $this->_getApiClient()->postContacts(
                $this->getCustomer()->getEmail()
            );
            if ($contact->id) {
                $this->_getCustomerSession()->setConnectorContactId(
                    $contact->id
                );
                $this->contactId = $contact->id;
            }
        }

        return $contact;
    }

    /**
     * @return Mage_Core_Model_Abstract|Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }
}
