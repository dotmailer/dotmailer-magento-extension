<?php

class Dotdigitalgroup_Email_Model_Quote extends Mage_Core_Model_Abstract
{
    private $_start;
    private $_quotes;
    private $_count = 0;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('email_connector/quote');
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
        $helper = Mage::helper('connector');
        //resource allocation
        $helper->allowResourceFullExecution();

        foreach (Mage::app()->getWebsites(true) as $website) {
            $enabled = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED, $website);
            if ($enabled) {
                //using bulk api
                $helper->log('---------- Start quote bulk sync ----------');
                $this->_start = microtime(true);
                $this->_exportQuoteForWebsite($website);
                //send quote as transactional data
                if (isset($this->_quotes[$website->getId()])) {
                    $client = Mage::helper('connector')->getWebsiteApiClient($website);
                    $websiteQuotes = $this->_quotes[$website->getId()];
                    //import quote in bulk
                    $client->postContactsTransactionalDataImport($websiteQuotes, 'Quote');
                }
                $message = 'Total time for quote bulk sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);

                //remove quotes
                $this->_deleteQuoteForWebsite($website);
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
    private function _exportQuoteForWebsite(Mage_Core_Model_Website $website)
    {
        try{
            //reset quotes
            $this->_quotes = array();
            $limit = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
            $collection = $this->_getQuoteToImport($website, $limit);
            foreach($collection as $emailQuote){
                $store = Mage::app()->getStore($emailQuote->getStoreId());
                $quote = Mage::getModel('sales/quote')->setStore($store)->load($emailQuote->getQuoteId());
                if($quote->getId())
                {
                    $connectorQuote = Mage::getModel('email_connector/connector_quote', $quote);
                    $this->_quotes[$website->getId()][] = $connectorQuote;
                }
                $emailQuote->setImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED)
                    ->setModified(null)
                    ->save();
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
     * @return mixed
     */
    private function _getQuoteToImport(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('imported', array('null' => true))
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()))
            ->addFieldToFilter('customer_id', array('notnull' => true));

        $collection->getSelect()->limit($limit);
        return $collection->load();
    }

    /**
     * remove quotes for website
     *
     * @param Mage_Core_Model_Website $website
     */
    private function _deleteQuoteForWebsite(Mage_Core_Model_Website $website)
    {
        try{
            $limit = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
            $client = Mage::helper('connector')->getWebsiteApiClient($website);
            $collection = $this->_getQuoteToDelete($website, $limit);
            foreach($collection as $emailQuote){
                $result = $client->deleteContactsTransactionalData($emailQuote->getQuoteId(), 'Quote');
                if (!isset($result->message)){
                    $emailQuote->setModified(null)->save();
                    $this->_count++;
                }
                $message = 'Quote removed : ' . $emailQuote->getQuoteId();
                Mage::helper('connector')->log($message);
            }
        }catch(Exception $e){
            Mage::logException($e);
        }
    }

    /**
     * get collection that will be used to remove quotes from connector
     *
     * @param Mage_Core_Model_Website $website
     * @param int $limit
     * @return mixed
     */
    private function _getQuoteToDelete(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('converted_to_order', 1)
            ->addFieldToFilter('modified', 1)
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()));

        $collection->getSelect()->limit($limit);
        return $collection->load();
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

    /**
     * Reset the email quote for reimport.
     *
     * @return int
     */
    public function resetQuotes()
    {
        /** @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');

        /** @var $conn Varien_Db_Adapter_Pdo_Mysql */
        $conn = $coreResource->getConnection('core_write');
        try{
            $num = $conn->update($coreResource->getTableName('email_connector/quote'),
                array('imported' => new Zend_Db_Expr('null')),
                array(
                    $conn->quoteInto('imported is ?', new Zend_Db_Expr('not null')),
                    $conn->quoteInto('converted_to_order is ?', new Zend_Db_Expr('null'))
                )
            );
        }catch (Exception $e){
            Mage::logException($e);
        }

        return $num;
    }
}