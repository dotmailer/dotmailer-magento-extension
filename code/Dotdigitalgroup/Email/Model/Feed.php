<?php

class Dotdigitalgroup_Email_Model_Feed extends Mage_AdminNotification_Model_Feed
{

    /**
     * Check for and extension update.
     *
     * @return $this
     */
    public function checkForUpgrade()
    {
        //not enabled
        if (!(bool)Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_FEED_ENABLED
        )
        ) {
            return $this;
        }

        //@codingStandardsIgnoreStart
        //time this was last checked
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }
        //@codingStandardsIgnoreEnd

        //data feed
        $feedData = array();

        $feedXml = $this->getFeedData();

        if ($feedXml) {
            foreach ($feedXml->release as $data) {
                //only if the version number was updated for the connector
                if (version_compare(
                    $data->version, Mage::helper('ddg')->getConnectorVersion(),
                    '>'
                )) {
                    //@codingStandardsIgnoreStart
                    $feedData[] = array(
                        'severity'    => $data->severity,
                        'date_added'  => $this->getDate($data->date_added),
                        'title'       => (string)$data->title,
                        'description' => (string)$data->description,
                        'url'         => (string)$data->link,
                    );
                    //@codingStandardsIgnoreEnd
                }
            }

            //admin notification with updated version
            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(
                    array_reverse($feedData)
                );
            }
        }

        //set the last update check
        $this->setLastUpdate();

        return $this;
    }

    /**
     * Set the last update time.
     *
     * @return $this|Mage_AdminNotification_Model_Feed
     */
    public function setLastUpdate()
    {
        //@codingStandardsIgnoreStart
        Mage::app()->saveCache(
            time(),
            Dotdigitalgroup_Email_Helper_Config::CONNECTOR_FEED_LAST_CHECK_TIME
        );
        //@codingStandardsIgnoreEnd

        return $this;
    }

    /**
     * @return SimpleXMLElement|string
     */
    public function getFeedUrl()
    {
        if ($this->_feedUrl === null) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_FEED_USE_HTTPS
            ) ? 'https://' : 'http://')
                . Mage::getStoreConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_FEED_URL
                );
        }

        return $this->_feedUrl;
    }

    /**
     * @return int|mixed
     */
    public function getFrequency()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_FEED_FREQUENCY
        ) * 3600;
    }

}