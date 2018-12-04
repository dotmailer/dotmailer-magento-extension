<?php

class Dotdigitalgroup_Email_Model_Automation extends Mage_Core_Model_Abstract
{
    const AUTOMATION_TYPE_NEW_CUSTOMER = 'customer_automation';
    const AUTOMATION_TYPE_NEW_SUBSCRIBER = 'subscriber_automation';
    const AUTOMATION_TYPE_NEW_ORDER = 'order_automation';
    const AUTOMATION_TYPE_NEW_GUEST_ORDER = 'guest_order_automation';
    const AUTOMATION_TYPE_NEW_REVIEW = 'review_automation';
    const AUTOMATION_TYPE_NEW_WISHLIST = 'wishlist_automation';
    const AUTOMATION_STATUS_PENDING = 'pending';
    const ORDER_STATUS_AUTOMATION = 'order_automation_';
    const AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER = 'first_order_automation';

    /**
     * Automation enrolment limit.
     * @var int
     */
    public $limit = 100;
    /**
     * @var string
     */
    public $email;
    /**
     * @var int
     */
    public $typeId;
    /**
     * @var string
     */
    public $storeName;
    /**
     * @var string
     */
    public $programId;
    /**
     * @var string
     */
    public $programStatus = 'Active';
    /**
     * @var
     */
    public $programMessage;
    /**
     * @var string
     */
    public $automationType;

    /**
     * @var array
     */
    public $automationTypes = array(
        self::AUTOMATION_TYPE_NEW_CUSTOMER =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER,
        self::AUTOMATION_TYPE_NEW_SUBSCRIBER =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER,
        self::AUTOMATION_TYPE_NEW_ORDER =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER,
        self::AUTOMATION_TYPE_NEW_GUEST_ORDER =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER,
        self::AUTOMATION_TYPE_NEW_REVIEW =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW,
        self::AUTOMATION_TYPE_NEW_WISHLIST =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST,
        self::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER =>
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_NEW_ORDER
    );

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/automation');
    }

    /**
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        } else {
            $this->setUpdatedAt($now);
        }

        return $this;
    }

    /**
     * Automation enrollment.
     *
     * @codingStandardsIgnoreStart
     */
    public function enrollment()
    {
        $automationOrderStatusCollection = $this->getCollection()
            ->addFieldToFilter(
                'enrolment_status', self::AUTOMATION_STATUS_PENDING
            );
        $automationOrderStatusCollection
            ->addFieldToFilter(
                'automation_type',
                array('like' => '%' . 'order_automation_' . '%')
            )->getSelect()->group('automation_type');

        $statusTypes = $automationOrderStatusCollection->getColumnValues('automation_type');
        foreach ($statusTypes as $type) {
            $this->automationTypes[$type]
                = Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS;
        }

        $helper = Mage::helper('ddg');
        //send the campaign by each types
        foreach ($this->automationTypes as $type => $config) {
            $contacts = array();
            foreach (Mage::app()->getWebsites(true) as $website) {
                if (strpos($type, self::ORDER_STATUS_AUTOMATION) !== false) {
                    $configValue = unserialize($helper->getWebsiteConfig($config, $website));

                    if (is_array($configValue) && !empty($configValue)) {
                        foreach ($configValue as $one) {
                            if (strpos($type, $one['status']) !== false) {
                                $contacts[$website->getId()]['programId']
                                    = $one['automation'];
                            }
                        }
                    }
                } else {
                    $contacts[$website->getId()]['programId']
                        = $helper->getWebsiteConfig($config, $website);
                }
            }

            //get collection from type
            $automationCollection = $this->getCollection()
                ->addFieldToFilter(
                    'enrolment_status', self::AUTOMATION_STATUS_PENDING
                );
            $automationCollection->addFieldToFilter('automation_type', $type);
            //limit because of the each contact request to get the id
            $automationCollection->getSelect()->limit($this->limit);
            foreach ($automationCollection as $automation) {
                //customerid, subscriberid, wishlistid..
                $email           = $automation->getEmail();
                $this->typeId    = $automation->getTypeId();
                $websiteId       = $automation->getWebsiteId();
                $this->storeName = $automation->getStoreName();
                $typeDouble = $type;
                //Set type to generic automation status if type contains constant value
                if (strpos($typeDouble, self::ORDER_STATUS_AUTOMATION) !== false) {
                    $typeDouble = self::ORDER_STATUS_AUTOMATION;
                }

                //Only if api is enabled and credentials are filled
                if ($helper->getWebsiteApiClient($websiteId)) {
                    $contactId = Mage::helper('ddg')->getContactId(
                        $email, $websiteId
                    );
                    //contact id is valid, can update datafields
                    if ($contactId) {
                        //need to update datafields
                        $this->updateDatafieldsByType(
                            $typeDouble, $email, $websiteId
                        );
                        $contacts[$automation->getWebsiteId()]['contacts'][$automation->getId()] = $contactId;
                    } else {
                        // the contact is suppressed or the request failed
                        $automation->setEnrolmentStatus('Suppressed')->save();
                    }
                } else {
                    unset($contacts[$websiteId]);
                }
            }

            foreach ($contacts as $websiteId => $websiteContacts) {
                if (isset($websiteContacts['contacts'])) {
                    $this->programId = $websiteContacts['programId'];
                    $contactsArray = $websiteContacts['contacts'];
                    //only for subscribed contacts
                    if (!empty($contactsArray)
                        && $this->_checkCampignEnrolmentActive($this->programId, $websiteId)
                    ) {
                        $result = $this->sendContactsToAutomation(
                            array_values($contactsArray),
                            $websiteId
                        );
                        //check for error message
                        if (isset($result->message)) {
                            $this->programStatus = 'Failed';
                            $this->programMessage = $result->message;
                        }

                        //program is not active
                    } elseif ($this->programMessage
                        == 'Error: ERROR_PROGRAM_NOT_ACTIVE '
                    ) {
                        $this->programStatus = 'Deactivated';
                    }

                    //update contacts with the new status, and log the error message if fails
                    $num = $this->getResource()->updateContacts(
                        $contactsArray, $this->programStatus, $this->programMessage
                    );
                    if ($num) {
                        Mage::helper('ddg')->log(
                            'Automation type : ' . $type . ', enrolled : ' . $num
                        );
                    }
                }
            }
        }
        //@codingStandardsIgnoreEnd
    }

    /**
     * Update single contact datafields for this automation type.
     *
     * @param $type
     * @param $email
     * @param $websiteId
     */
    public function updateDatafieldsByType($type, $email, $websiteId)
    {
        switch ($type) {
            case self::AUTOMATION_TYPE_NEW_CUSTOMER :
            case self::AUTOMATION_TYPE_NEW_SUBSCRIBER :
            case self::AUTOMATION_TYPE_NEW_WISHLIST :
                $this->_updateDefaultDatafields($email, $websiteId);
                break;
            case self::AUTOMATION_TYPE_NEW_ORDER :
            case self::AUTOMATION_TYPE_NEW_GUEST_ORDER :
            case self::AUTOMATION_TYPE_NEW_REVIEW :
            case self::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER :
            case self::ORDER_STATUS_AUTOMATION :
                $this->_updateNewOrderDatafields($websiteId);
                break;
            default:
                $this->_updateDefaultDatafields($email, $websiteId);
                break;
        }
    }

    /**
     * @param $email
     * @param $websiteId
     */
    protected function _updateDefaultDatafields($email, $websiteId)
    {
        $website = Mage::app()->getWebsite($websiteId);
        Mage::helper('ddg')->updateDataFields(
            $email, $website, $this->storeName
        );
    }

    /**
     * Order datafields.
     *
     * @param $websiteId
     */
    protected function _updateNewOrderDatafields($websiteId)
    {
        $website = Mage::app()->getWebsite($websiteId);
        $order   = Mage::getModel('sales/order')->load($this->typeId);
        //data fields
        if ($lastOrderId = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID
        )
        ) {
            $data[] = array(
                'Key'   => $lastOrderId,
                'Value' => $order->getId()
            );
        }

        if ($orderIncrementId = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID
        )
        ) {
            $data[] = array(
                'Key'   => $orderIncrementId,
                'Value' => $order->getIncrementId()
            );
        }

        if ($storeName = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = array(
                'Key'   => $storeName,
                'Value' => $this->storeName
            );
        }

        if ($websiteName = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $data[] = array(
                'Key'   => $websiteName,
                'Value' => $website->getName()
            );
        }

        if ($lastOrderDate = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE
        )
        ) {
            $data[] = array(
                'Key'   => $lastOrderDate,
                'Value' => $order->getCreatedAt()
            );
        }

        if (($customerId = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ID
        ))
            && $order->getCustomerId()
        ) {
            $data[] = array(
                'Key'   => $customerId,
                'Value' => $order->getCustomerId()
            );
        }

        if (!empty($data)) {
            //update data fields
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);
            if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
                $client->updateContactDatafieldsByEmail(
                    $order->getCustomerEmail(), $data
                );
            }
        }
    }

    /**
     * Program check if is valid and active.
     *
     * @param $programId
     * @param $websiteId
     *
     * @return bool
     */
    protected function _checkCampignEnrolmentActive($programId, $websiteId)
    {
        //program is not set
        if (!$programId) {
            return false;
        }

        $client  = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
        if ($client === false) {
            return false;
        }

        $program = $client->getProgramById($programId);
        //program status
        if (isset($program->status)) {
            $this->programStatus = $program->status;
        }

        if (isset($program->status) && $program->status == 'Active') {
            return true;
        }

        return false;
    }

    /**
     * Enrol contacts for a program.
     *
     * @param $contacts
     * @param $websiteId
     *
     * @return bool|null
     */
    public function sendContactsToAutomation($contacts, $websiteId)
    {
        $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
        if ($client === false) {
            return false;
        }

        $data   = array(
            'Contacts'     => $contacts,
            'ProgramId'    => $this->programId,
            'AddressBooks' => array()
        );
        //api add contact to automation enrolment
        $result = $client->postProgramsEnrolments($data);

        return $result;
    }
}