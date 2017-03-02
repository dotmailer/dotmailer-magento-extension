<?php

class Dotdigitalgroup_Email_Model_Review extends Mage_Core_Model_Abstract
{
    const EMAIL_REVIEW_IMPORTED = 1;

    /**
     * @var mixed
     */
    public $start;
    /**
     * @var
     */
    public $countReviews;
    /**
     * @var
     */
    public $reviews;
    /**
     * @var array
     */
    public $reviewIds;


    /**
     * Constructor.
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
        } else {
            $this->setUpdatedAt($now);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function sync()
    {
        $response            = array('success' => true, 'message' => '');
        $helper              = Mage::helper('ddg');
        $this->countReviews = 0;
        $this->reviews      = array();
        $this->start        = microtime(true);
        //resource allocation
        $helper->allowResourceFullExecution();
        foreach (Mage::app()->getWebsites(true) as $website) {
            $enabled  = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $sync     = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED,
                $website
            );
            $storeIds = $website->getStoreIds();
            if ($enabled && $sync && ! empty($storeIds)) {
                //start the sync
                if (! $this->countReviews) {
                    $helper->log('---------- Start reviews sync ----------');
                }

                $this->_exportReviewsForWebsite($website);
            }

            if (isset($this->reviews[$website->getId()])) {
                $reviews = $this->reviews[$website->getId()];
                //send reviews as transactional data
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_REVIEWS,
                        $reviews,
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website->getId()
                    );
                //if no error then set imported
                if ($check) {
                    $this->getResource()->setImported($this->reviewIds);
                }

                //@codingStandardsIgnoreStart
                $this->countReviews += count($reviews);
                //@codingStandardsIgnoreEnd
            }
        }

        if ($this->countReviews) {
            //@codingStandardsIgnoreStart
            $message = 'Total time for sync : ' . gmdate("H:i:s", microtime(true) - $this->start) .
                ', Total synced = ' . $this->countReviews;
            //@codingStandardsIgnoreEnd
            $helper->log($message);
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportReviewsForWebsite(Mage_Core_Model_Website $website)
    {
        $limit            = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $emailReviews     = $this->_getReviewsToExport($website, $limit);
        $this->reviewIds = $emailReviews->getColumnValues('review_id');

        if (! empty($this->reviewIds)) {
            $reviews = Mage::getModel('review/review')
                ->getCollection()
                ->addFieldToFilter(
                    'main_table.review_id', array('in' => $this->reviewIds)
                )
                ->addFieldToFilter('customer_id', array('notnull' => 'true'));

            //@codingStandardsIgnoreStart
            $reviews->getSelect()
                ->joinLeft(
                    array('c' => Mage::getSingleton('core/resource')
                        ->getTableName('customer/entity')),
                    'c.entity_id = customer_id',
                    array('email', 'store_id')
                );

            if ($reviews->getSize()) {
                foreach ($reviews as $mageReview) {
                    try {
                        $product = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addIdFilter($mageReview->getEntityPkValue())
                            ->setStoreId($mageReview->getStoreId())
                            ->addAttributeToSelect(
                                array('product_url', 'name', 'store_id', 'small_image')
                            )
                            ->setPage(1, 1)
                            ->getFirstItem();

                        $connectorReview = Mage::getModel(
                            'ddg_automation/customer_review'
                        )
                            ->setReviewData($mageReview)
                            ->setProduct($product);

                        $votesCollection = Mage::getModel(
                            'rating/rating_option_vote'
                        )
                            ->getResourceCollection()
                            ->setReviewFilter($mageReview->getReviewId());
                        $votesCollection->getSelect()->join(
                            array('rating' => 'rating'),
                            'rating.rating_id = main_table.rating_id',
                            array('rating_code' => 'rating.rating_code')
                        );

                        foreach ($votesCollection as $ratingItem) {
                            $rating = Mage::getModel(
                                'ddg_automation/customer_review_rating',
                                $ratingItem
                            );
                            $connectorReview->createRating(
                                $ratingItem->getRatingCode(), $rating
                            );
                        }
                        $this->reviews[$website->getId()][] = $connectorReview;
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
            //@codingStandardsIgnoreEnd
        }
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @param int $limit
     * @return Varien_Data_Collection
     */
    protected function _getReviewsToExport(Mage_Core_Model_Website $website, $limit = 100)
    {
        return $this->getCollection()
            ->addFieldToFilter('review_imported', array('null' => 'true'))
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->setPageSize($limit);
    }
}