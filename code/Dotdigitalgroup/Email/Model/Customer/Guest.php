<?php

class Dotdigitalgroup_Email_Model_Customer_Guest
{
    protected $_countGuests = 0;
    protected $_start;

	/**
	 * GUEST SYNC.
	 */
	public function sync()
    {
        /** @var Dotdigitalgroup_Email_Helper_Data $helper */
        $helper = Mage::helper('ddg');
        $this->_start = microtime(true);
        foreach(Mage::app()->getWebsites() as $website) {

	        //check if the guest is mapped and enabled
	        $enabled = $helper->getGuestAddressBook($website);
	        $mapped = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED);
	        if ($enabled && $mapped) {

		        //ready to start sync
		        if (!$this->_countGuests)
		            $helper->log('----------- Start guest sync ----------');

		        //sync guests for website
		        $this->exportGuestPerWebsite($website);
	        }
        }
	    if ($this->_countGuests)
            $helper->log('---- End Guest total time for guest sync : ' . gmdate("H:i:s", microtime(true) - $this->_start));
    }

    public function exportGuestPerWebsite(Mage_Core_Model_Website $website)
    {
        $helper = Mage::helper('ddg');
        $fileHelper = Mage::helper('ddg/file');
        $guests = Mage::getModel('ddg_automation/contact')->getGuests($website);
        if ($guests->getSize()) {
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);
            $guestFilename = strtolower($website->getCode() . '_guest_' . date('d_m_Y_Hi') . '.csv');
            $helper->log('Guest file: ' . $guestFilename);
            $storeName = $helper->getMappedStoreName($website);
            $fileHelper->outputCSV($fileHelper->getFilePath($guestFilename), array('Email', 'emailType', $storeName));
            foreach ($guests as $guest) {
                $email = $guest->getEmail();
                try{
                    $guest->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED)
                        ->save();
                    $storeName = $website->getName();
                    // save data for guests
                    $fileHelper->outputCSV($fileHelper->getFilePath($guestFilename), array($email, 'Html', $storeName));
                    $this->_countGuests++;
                }catch (Exception $e){
                    Mage::logException($e);
                }
            }
            if ($this->_countGuests) {
                //Add to guest address book
                $client->postAddressBookContactsImport($guestFilename, $helper->getGuestAddressBook($website));
            }
            //archive guest file
            $fileHelper->archiveCSV($guestFilename);
        }
    }
}