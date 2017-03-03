<?php

class Dotdigitalgroup_Email_Model_Customer_Guest
{

    /**
     * @var int
     */
    public $countGuests = 0;
    /**
     * @var
     */
    public $start;
    /**
     * @var array
     */
    public $guests = array();

    /**
     * GUEST SYNC.
     */
    public function sync()
    {
        /** @var Dotdigitalgroup_Email_Helper_Data $helper */
        $helper       = Mage::helper('ddg');
        $this->start = microtime(true);
        foreach (Mage::app()->getWebsites() as $website) {
            //check if the guest is mapped and enabled
            $enabled     = $helper->getGuestAddressBook($website);
            $syncEnabled = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED
            );
            $apiEnabled  = $helper->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            if ($enabled && $syncEnabled && $apiEnabled) {
                //Find and create guest
                $this->findAndCreateGuest($website->getId());

                //ready to start sync
                if (! $this->countGuests) {
                    $helper->log('----------- Start guest sync ----------');
                }

                //sync guests for website
                $this->exportGuestPerWebsite($website);
            }
        }

        if ($this->countGuests) {
            //@codingStandardsIgnoreStart
            $helper->log(
                '---- End Guest total time for guest sync : ' .
                gmdate("H:i:s", microtime(true) - $this->start)
            );
            //@codingStandardsIgnoreEnd
        }
    }

    /**
     * Find and create guests.
     */
    public function findAndCreateGuest($websiteId)
    {
        //Reset guests
        $this->guests = array();

        $contacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->getColumnValues('email');

        $storeIds = Mage::app()->getWebsite($websiteId)->getStoreIds();

        //get the order collection
        $salesOrderCollection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('customer_is_guest', array('eq' => 1))
            ->addFieldToFilter('customer_email', array('notnull' => true))
            ->addFieldToFilter('store_id', array('in' => $storeIds));

        //Exclude existed contacts from collection
        if (!empty($contacts)) {
            $salesOrderCollection->addFieldToFilter('customer_email', ['nin' => $contacts]);
        }

        //@codingStandardsIgnoreStart
        //group by email and store
        $salesOrderCollection->getSelect()->group(array('customer_email', 'store_id'));
        //@codingStandardsIgnoreEnd
        foreach ($salesOrderCollection as $order) {
            $storeId = $order->getStoreId();

            //add guest to the list
            $this->guests[] = array(
                'email' => $order->getCustomerEmail(),
                'website_id' => $websiteId,
                'store_id' => $storeId,
                'is_guest' => 1
            );
        }

        /**
         * Add guest to contacts table.
         */
        if (! empty($this->guests)) {
            //@codingStandardsIgnoreStart
            Mage::getResourceModel('ddg_automation/contact')->insert($this->guests);
            //@codingStandardsIgnoreEnd
        }
    }

    /**
     * @param Mage_Core_Model_Website $website
     */
    public function exportGuestPerWebsite(Mage_Core_Model_Website $website)
    {
        $helper     = Mage::helper('ddg');
        $fileHelper = Mage::helper('ddg/file');
        $guests     = Mage::getModel('ddg_automation/contact')->getGuests($website);

        if ($guests->getSize()) {
            //@codingStandardsIgnoreStart
            $guestFilename = strtolower(
                $website->getCode() . '_guest_' . date('d_m_Y_Hi') . '.csv'
            );
            //@codingStandardsIgnoreEnd
            $helper->log('Guest file: ' . $guestFilename);
            $storeName = $helper->getMappedStoreName($website);
            $fileHelper->outputCSV(
                $fileHelper->getFilePath($guestFilename),
                array('Email', 'emailType', $storeName)
            );
            foreach ($guests as $guest) {
                $email = $guest->getEmail();
                try {
                    //@codingStandardsIgnoreStart
                    $guest->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED)
                        ->save();
                    //@codingStandardsIgnoreEnd
                    $storeName = $website->getName();
                    // save data for guests
                    $fileHelper->outputCSV(
                        $fileHelper->getFilePath($guestFilename),
                        array($email, 'Html', $storeName)
                    );
                    $this->countGuests++;
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            if ($this->countGuests) {
                //register in queue with importer
                Mage::getModel('ddg_automation/importer')->registerQueue(
                    Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_GUEST,
                    '',
                    Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                    $website->getId(),
                    $guestFilename
                );
            }
        }
    }
}