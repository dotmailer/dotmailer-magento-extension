<?php

class Dotdigitalgroup_Email_Model_Newsletter_Subscriber
{
    const STATUS_SUBSCRIBED     = 1;
    const STATUS_NOT_ACTIVE     = 2;
    const STATUS_UNSUBSCRIBED   = 3;
    const STATUS_UNCONFIRMED    = 4;

    protected $_start;

    /**
     * Global number of subscriber updated.
     * @var
     */
    protected $_countSubscriber = 0;

    /**
     * SUBSCRIBER SYNC.
     * @return $this
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        /** @var Dotdigitalgroup_Email_Helper_Data $helper */
        $helper = Mage::helper('ddg');

        $this->_start = microtime(true);

        foreach (Mage::app()->getWebsites(true) as $website) {
            //if subscriber is enabled and mapped
            $apiEnabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $website);
            $enabled = (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED);
            $addressBook = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID);

	        //enabled and mapped
            if ($enabled && $addressBook && $apiEnabled) {

	            //ready to start sync
	            if (!$this->_countSubscriber)
	                $helper->log('---------------------- Start subscriber sync -------------------');

                $numUpdated = $this->exportSubscribersPerWebsite($website);
                // show message for any number of customers
                if ($numUpdated)
                    $response['message'] .=  '</br>' . $website->getName() . ', updated subscribers = ' . $numUpdated;

            }
        }

        //global number of subscribers to set the message
        if ($this->_countSubscriber) {
            //reponse message
            $message = 'Total time for sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);

            //put the message in front
            $message .= $response['message'];
            $result['message'] = $message;
        }

        return $response;
    }

    /**
     * Export subscriber per website.
     * @param Mage_Core_Model_Website $website
     *
     * @return int
     */
    public function exportSubscribersPerWebsite(Mage_Core_Model_Website $website)
    {
        $updated = 0;
        $fileHelper = Mage::helper('ddg/file');
        $limit = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_LIMIT);
        $subscribers = Mage::getModel('ddg_automation/contact')->getSubscribersToImport($website, $limit);
        if (count($subscribers)) {
            $subscribersFilename = strtolower($website->getCode() . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
            //get mapped storename
            $subscriberStorename = Mage::helper('ddg')->getMappedStoreName($website);
            //file headers
            $fileHelper->outputCSV($fileHelper->getFilePath($subscribersFilename), array('Email', 'emailType', $subscriberStorename));
            foreach ($subscribers as $subscriber) {
                try{
                    $email = $subscriber->getEmail();
                    $subscriber->setSubscriberImported(1)->save();
                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                    $storeName = Mage::app()->getStore($subscriber->getStoreId())->getName();
                    // save data for subscribers
                    $fileHelper->outputCSV($fileHelper->getFilePath($subscribersFilename), array($email, 'Html', $storeName));
                    $updated++;
                }catch (Exception $e){
                    Mage::logException($e);
                }
            }
            Mage::helper('ddg')->log('Subscriber filename: ' . $subscribersFilename);
            //register in queue with importer
            Mage::getModel('ddg_automation/importer')->registerQueue(
                Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBERS,
                '',
                Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                $website->getId(),
                $subscribersFilename
            );
        }
        //add updated number for the website
        $this->_countSubscriber += $updated;
        return $updated;
    }

}