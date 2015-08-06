<?php

class Dotdigitalgroup_Email_Model_Review extends Mage_Core_Model_Abstract
{
    private $_start;
    private $_countReviews;
    private $_reviews;
    private $_reviewIds;

    const EMAIL_REVIEW_IMPORTED = 1;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/review');
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

    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $helper = Mage::helper('ddg');
        $this->_countReviews = 0;
        $this->_reviews = array();
        $this->_start = microtime(true);
        //resource allocation
        $helper->allowResourceFullExecution();
        foreach (Mage::app()->getWebsites(true) as $website) {

	        $enabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $website);
            $sync = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED, $website);

	        if ($enabled && $sync) {
				//start the sync
	            if (! $this->_countReviews)
	                $helper->log('---------- Start reviews sync ----------');
                $this->_exportReviewsForWebsite($website);
            }

            if (isset($this->_reviews[$website->getId()])) {
                $reviews = $this->_reviews[$website->getId()];
                //send reviews as transactional data
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')->registerQueue(
                    Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_REVIEWS,
                    $reviews,
                    Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                    $website->getId()
                );
                //if no error then set imported
                if ($check) {
                    $this->_setImported($this->_reviewIds);
                }
                $this->_countReviews += count($reviews);
            }
        }

        if ($this->_countReviews) {
	        $message = 'Total time for sync : ' . gmdate( "H:i:s", microtime( true ) - $this->_start ) . ', Total synced = ' . $this->_countReviews;
	        $helper->log( $message );
	        $response['message'] = $message;
        }
        return $response;
    }

    private function _exportReviewsForWebsite(Mage_Core_Model_Website $website)
    {
        $limit = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
        $reviews = $this->_getReviewsToExport($website, $limit);
        $this->_reviewIds = array();

        if($reviews->getSize()){
            foreach($reviews as $review){
                try {
                    $mageReview = Mage::getModel('review/review')->load($review->getReviewId());

                    $product = Mage::getModel('catalog/product')
                        ->setStoreId($mageReview->getStoreId())
                        ->load($mageReview->getEntityPkValue());

                    $customer = Mage::getModel('customer/customer')->load($mageReview->getCustomerId());

                    $connectorReview = Mage::getModel('ddg_automation/customer_review', $customer)
                        ->setReviewData($mageReview)
                        ->setProduct($product);

                    $votesCollection = Mage::getModel('rating/rating_option_vote')
                        ->getResourceCollection()
                        ->setReviewFilter($mageReview->getReviewId());
                    $votesCollection->getSelect()->join(
                        array('rating'=> 'rating'),
                        'rating.rating_id = main_table.rating_id',
                        array('rating_code' => 'rating.rating_code')
                    );

                    foreach($votesCollection as $ratingItem){
                        $rating = Mage::getModel('ddg_automation/customer_review_rating', $ratingItem);
                        $connectorReview->createRating($ratingItem->getRatingCode(), $rating);
                    }
                    $this->_reviews[$website->getId()][] = $connectorReview;
                    $this->_reviewIds[] = $review->getReviewId();
                }catch(Exception $e){
                    Mage::logException($e);
                }
            }
        }
    }

    private function _getReviewsToExport(Mage_Core_Model_Website $website, $limit = 100)
    {
        return $this->getCollection()
            ->addFieldToFilter('review_imported', array('null' => 'true'))
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()))
            ->setPageSize($limit);
    }

    /**
     * Reset the email reviews for reimport.
     *
     * @return int
     */
    public function reset()
    {
        /** @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');

        /** @var $conn Varien_Db_Adapter_Pdo_Mysql */
        $conn = $coreResource->getConnection('core_write');
        try{
            $num = $conn->update($coreResource->getTableName('ddg_automation/review'),
                array('review_imported' => new Zend_Db_Expr('null')),
                $conn->quoteInto('review_imported is ?', new Zend_Db_Expr('not null'))
            );
        }catch (Exception $e){
            Mage::logException($e);
        }

        return $num;
    }

    /**
     * set imported in bulk query
     *
     * @param $ids
     */
    private function _setImported($ids)
    {
        try{
            $coreResource = Mage::getSingleton('core/resource');
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_review');
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();
            $write->update($tableName, array('review_imported' => 1, 'updated_at' => $now), "review_id IN ($ids)");
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
}