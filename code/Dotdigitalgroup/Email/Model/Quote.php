<?php

class Dotdigitalgroup_Email_Model_Quote extends Mage_Core_Model_Abstract
{

    /**
     * @var mixed
     */
    public $start;
    /**
     * @var
     */
    public $quotes;
    /**
     * @var int
     */
    public $countQuotes = 0;
    /**
     * @var array
     */
    public $quoteIds;

    /**
     * Constructor.
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
        } else {
            $this->setUpdatedAt($now);
        }

        return $this;
    }

    /**
     * Sync.
     *
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $helper   = Mage::helper('ddg');
        //resource allocation
        $helper->allowResourceFullExecution();

        foreach (Mage::app()->getWebsites(true) as $website) {
            $apiEnabled = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $enabled    = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED,
                $website
            );
            $storeIds   = $website->getStoreIds();
            //api and sync enabled, also the should have stores created for this website
            if ($enabled && $apiEnabled && ! empty($storeIds)) {
                $this->start = microtime(true);
                //get quotes for website to import
                $this->_exportQuoteForWebsite($website);

                //send quote as transactional data
                if (isset($this->quotes[$website->getId()])) {
                    $websiteQuotes = $this->quotes[$website->getId()];
                    //register in queue with importer
                    $check = Mage::getModel('ddg_automation/importer')
                        ->registerQueue(
                            Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_QUOTE,
                            $websiteQuotes,
                            Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                            $website->getId()
                        );

                    //set imported
                    if ($check) {
                        $this->getResource()->setImported($this->quoteIds);
                    }
                }

                if ($this->countQuotes) {
                    //@codingStandardsIgnoreStart
                    $message = 'Total time for Quotes bulk sync : ' . gmdate("H:i:s", microtime(true) - $this->start);
                    //@codingStandardsIgnoreEnd
                    $helper->log($message);
                }

                //update quotes
                $this->_exportQuoteForWebsiteInSingle($website);
            }
        }

        $response['message'] = "quote updated: " . $this->countQuotes;

        return $response;
    }

    /**
     * Export quotes to website.
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportQuoteForWebsite(Mage_Core_Model_Website $website)
    {
        try {
            //reset quotes
            $this->quotes = array();
            $this->quoteIds = array();
            $websiteId       = $website->getId();
            $limit           = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
                $website
            );
            $collection      = $this->_getQuoteToImport($website, $limit);

            $ids    = $collection->getColumnValues('quote_id');
            $quotes = Mage::getModel('sales/quote')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $ids));

            foreach ($quotes as $quote) {
                $connectorQuote              = Mage::getModel(
                    'ddg_automation/connector_quote', $quote
                );
                $this->quotes[$websiteId][] = $connectorQuote;
                $this->quoteIds[] = $quote->getId();
                $this->countQuotes++;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * get quotes to import
     *
     * @param Mage_Core_Model_Website $website
     * @param int                     $limit
     * @param                         $modified
     *
     * @return mixed
     */
    protected function _getQuoteToImport(Mage_Core_Model_Website $website, $limit = 100, $modified = false)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->addFieldToFilter('customer_id', array('notnull' => true));

        if ($modified) {
            $collection->addFieldToFilter('modified', 1)
                ->addFieldToFilter('imported', 1);
        } else {
            $collection->addFieldToFilter('imported', array('null' => true));
        }

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($limit);
        //@codingStandardsIgnoreEnd
        return $collection;
    }

    /**
     * Update quotes for website in single.
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportQuoteForWebsiteInSingle(Mage_Core_Model_Website $website)
    {
        try {
            $emailQuoteIds = array();
            $limit         = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
                $website
            );
            $collection    = $this->_getQuoteToImport($website, $limit, true);

            $ids    = $collection->getColumnValues('quote_id');
            //no single quote found
            if (empty($ids)) {
                return;
            }

            $quotes = Mage::getModel('sales/quote')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $ids));

            foreach ($quotes as $quote) {
                $quoteId = $quote->getId();

                $connectorQuote = Mage::getModel(
                    'ddg_automation/connector_quote', $quote
                );
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_QUOTE,
                        $connectorQuote,
                        Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE,
                        $website->getId()
                    );
                if ($check) {
                    $message = 'Quote updated : ' . $quoteId;
                    Mage::helper('ddg')->log($message);
                    //reset the modify for the email quote
                    $emailQuoteIds[] = $quoteId;
                    $this->countQuotes++;
                }
            }

            //needed to reset the modified for the email quote.
            $this->getResource()->setImported($emailQuoteIds);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Load quote from connector table.
     *
     * @param $quoteId
     *
     * @return bool
     */
    public function loadQuote($quoteId)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        }

        return false;
    }
}