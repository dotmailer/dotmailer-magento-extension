<?php

class Dotdigitalgroup_Email_Model_Quote extends Mage_Core_Model_Abstract
{
    protected $_start;
    protected $_quotes;
    protected $_count = 0;
    protected $_quoteIds;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/quote');
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
        }else {
            $this->setUpdatedAt($now);
        }
        return $this;
    }

    /**
     * sync
     *
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $helper = Mage::helper('ddg');
        //resource allocation
        $helper->allowResourceFullExecution();

        foreach (Mage::app()->getWebsites(true) as $website) {

            $apiEnabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $website);
            $enabled    = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED, $website);
            $storeIds = $website->getStoreIds();
			//api and sync enabled, also the should have stores created for this website
	        if ($enabled && $apiEnabled && !empty($storeIds)) {
                //using bulk api
                $helper->log('---------- Start quote bulk sync ----------');
                $this->_start = microtime(true);
		        /**
		         * get quotes for website to import.
		         */
                $this->_exportQuoteForWebsite($website);

                //send quote as transactional data
                if (isset($this->_quotes[$website->getId()])) {
                    $websiteQuotes = $this->_quotes[$website->getId()];
                    //register in queue with importer
                    $check = Mage::getModel('ddg_automation/importer')->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_QUOTE,
                        $websiteQuotes,
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website->getId()
                    );

                    //set imported
                    if ($check) {
                        $this->getResource()->setImported($this->_quoteIds);
                    }
                }
                $message = 'Total time for quote bulk sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);

                //update quotes
                $this->_exportQuoteForWebsiteInSingle($website);

            }
        }
        $response['message'] = "quote updated: ". $this->_count;
        return $response;
    }

    /**
     * export quotes to website
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportQuoteForWebsite(Mage_Core_Model_Website $website)
    {
	    try{
		    //reset quotes
		    $this->_quotes = array();
		    $this->_quoteIds = array();
		    $limit = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
		    $collection = $this->_getQuoteToImport($website, $limit);

		    foreach ($collection as $emailQuote) {
			    $store = Mage::app()->getStore($emailQuote->getStoreId());
			    $quote = Mage::getModel('sales/quote')->setStore($store)->load($emailQuote->getQuoteId());

			    if ($quote->getId()) {

				    $connectorQuote = Mage::getModel('ddg_automation/connector_quote', $quote);
				    $this->_quotes[$website->getId()][] = $connectorQuote;
			    }
			    $this->_quoteIds[] = $emailQuote->getQuoteId();
			    $this->_count++;
		    }
	    }catch(Exception $e){
		    Mage::logException($e);
	    }
    }

    /**
     * get quotes to import
     *
     * @param Mage_Core_Model_Website $website
     * @param int $limit
     * @param $modified
     *
     * @return mixed
     */
    protected function _getQuoteToImport(Mage_Core_Model_Website $website, $limit = 100, $modified = false)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()))
            ->addFieldToFilter('customer_id', array('notnull' => true));

        if ($modified) {
            $collection->addFieldToFilter('modified', 1)
                ->addFieldToFilter('imported', 1);
        } else {
            $collection->addFieldToFilter('imported', array('null' => true));
        }
        $collection->getSelect()->limit($limit);

	    return $collection;
    }

    /**
     * update quotes for website in single
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportQuoteForWebsiteInSingle(Mage_Core_Model_Website $website)
    {
        try {
            $limit = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
            $collection = $this->_getQuoteToImport($website, $limit, true);


	        foreach ($collection as $emailQuote) {
		        $store = Mage::app()->getStore($emailQuote->getStoreId());
		        $quote = Mage::getModel('sales/quote')->setStore($store)->load($emailQuote->getQuoteId());
		        if($quote->getId()) {

                    $connectorQuote = Mage::getModel('ddg_automation/connector_quote', $quote);
                    //register in queue with importer
                    $check = Mage::getModel('ddg_automation/importer')->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_QUOTE,
                        $connectorQuote,
                        Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE,
                        $website->getId()
                    );
                    if ($check) {
                        $message = 'Quote updated : ' . $emailQuote->getQuoteId();
                        Mage::helper('ddg')->log($message);
                        $emailQuote->setModified(null)->save();
                        $this->_count++;
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * load quote from connector table
     *
     * @param $quoteId
     * @return bool
     */
    public function loadQuote($quoteId)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        if ($collection->count()) {
            return $collection->getFirstItem();
        }
        return false;
    }
}