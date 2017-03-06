<?php

class Dotdigitalgroup_Email_Model_Newsletter_Subscriber
{

    const STATUS_SUBSCRIBED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_UNSUBSCRIBED = 3;
    const STATUS_UNCONFIRMED = 4;

    public $start;

    /**
     * Global number of subscriber updated.
     *
     * @var int
     */
    public $countSubscriber = 0;

    /**
     * SUBSCRIBER SYNC.
     *
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $helper = Mage::helper('ddg');
        $this->start = microtime(true);

        foreach (Mage::app()->getWebsites(true) as $website) {
            //if subscriber is enabled and mapped
            $apiEnabled  = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $enabled     = (bool)$website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED
            );
            $addressBook = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID
            );

            //enabled and mapped
            if ($enabled && $addressBook && $apiEnabled) {
                //ready to start sync
                if (!$this->countSubscriber) {
                    $helper->log(
                        '---------------------- Start subscriber sync -------------------'
                    );
                }

                $numUpdated = $this->exportSubscribersPerWebsite($website);
                // show message for any number of customers
                if ($numUpdated) {
                    $response['message'] .= '</br>' . $website->getName()
                        . ', updated subscribers = ' . $numUpdated;
                }
            }
        }

        //global number of subscribers to set the message
        if ($this->countSubscriber) {
            //@codingStandardsIgnoreStart
            //reponse message
            $message = 'Total time for sync : ' . gmdate("H:i:s", microtime(true) - $this->start);
            //@codingStandardsIgnoreEnd

            //put the message in front
            $message .= $response['message'];
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Export subscriber per website.
     *
     * @param Mage_Core_Model_Website $website
     *
     * @return int
     */
    public function exportSubscribersPerWebsite(Mage_Core_Model_Website $website) 
    {
        $updated     = 0;
        $limit       = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_LIMIT
        );
        $emailContactModel = Mage::getModel('ddg_automation/contact');

        //Customer Subscribers
        $subscribersAreCustomers = $emailContactModel
            ->getSubscribersToImport($website, $limit);

        //Guest Subscribers
        $subscribersAreGuest = $emailContactModel
            ->getSubscribersToImport($website, $limit, false);

        $subscribersGuestEmails = $subscribersAreGuest->getColumnValues('email');
        $existInSales = array();
        if (!empty($subscribersGuestEmails)) {
            $existInSales = $this->checkInSales($subscribersGuestEmails);
        }
        $emailsNotInSales = array_diff($subscribersGuestEmails, $existInSales);

        $customerSubscribers = $subscribersAreCustomers->getColumnValues('email');
        $emailsWithNoSaleData = array_merge($emailsNotInSales, $customerSubscribers);

        //Subscriber that are customer or/and the one that
        //do not exist in sales order table
        $subscribersWithNoSaleData = array();
        if (!empty($emailsWithNoSaleData)) {
            $subscribersWithNoSaleData = $emailContactModel
                ->getSubscribersToImportFromEmails($emailsWithNoSaleData);
        }
        if (!empty($subscribersWithNoSaleData)) {
            $updated += $this->exportSubscribers(
                $website, $subscribersWithNoSaleData
            );
            //add updated number for the website
            $this->countSubscriber += $updated;
        }

        //Subscriber that are guest and also
        //exist in sales order table
        $subscribersWithSaleData = array();
        if (!empty($subscribersWithSaleData)) {
            $subscribersWithSaleData = $emailContactModel
                ->getSubscribersToImportFromEmails($existInSales);
        }

        if (!empty($subscribersWithSaleData)) {
            $updated += $this->exportSubscribersWithSales(
                $website, $subscribersWithSaleData
            );
            //add updated number for the website
            $this->countSubscriber += $updated;
        }

        return $updated;
    }

    /**
     * @param $email
     * @param $subscribers
     * @return bool
     */
    protected function getStoreIdForSubscriber($email, $subscribers)
    {

        foreach ($subscribers as $subscriber) {
            if ($subscriber['subscriber_email'] == $email) {
                return $subscriber['store_id'];
            }
        }

        return false;
    }

    /**
     * Export subscribers
     *
     * @param Mage_Core_Model_Website $website
     * @param $subscribers
     * @return int
     */
    protected function exportSubscribers(Mage_Core_Model_Website $website, $subscribers)
    {
        $updated = 0;
        $fileHelper = Mage::helper('ddg/file');
        //@codingStandardsIgnoreStart
        $subscribersFilename = strtolower($website->getCode() . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
        //@codingStandardsIgnoreEnd
        //get mapped storename
        $subscriberStorename = Mage::helper('ddg')->getMappedStoreName($website);
        //file headers
        $fileHelper->outputCSV(
            $fileHelper->getFilePath($subscribersFilename),
            array('Email', 'emailType', $subscriberStorename)
        );

        $subscribersData = Mage::getModel('newsletter/subscriber')
            ->getCollection()
            ->addFieldToFilter(
                'subscriber_email',
                array('in' => $subscribers->getColumnValues('email'))
            )
            ->addFieldToSelect(array('subscriber_email', 'store_id'))
            ->toArray();

        foreach ($subscribers as $subscriber) {
            try {
                $email = $subscriber->getEmail();
                $storeId = $this->getStoreIdForSubscriber(
                    $email, $subscribersData['items']
                );
                $storeName = Mage::app()->getStore($storeId)->getName();
                // save data for subscribers
                $fileHelper->outputCSV(
                    $fileHelper->getFilePath($subscribersFilename),
                    array($email, 'Html', $storeName)
                );
                //@codingStandardsIgnoreStart
                $subscriber->setSubscriberImported(1)->save();
                //@codingStandardsIgnoreEnd
                $updated++;
            } catch (Exception $e) {
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

        return $updated;
    }

    /**
     * Check emails exist in sales order table.
     *
     * @param $emails
     * @return array
     */
    public function checkInSales($emails)
    {
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('customer_email', array('in' => $emails));
        return $collection->getColumnValues('customer_email');
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @param $subscribers
     * @return int
     */
    public function exportSubscribersWithSales(Mage_Core_Model_Website $website, $subscribers)
    {
        $updated = 0;
        $subscriberIds = $headers = array();
        $fileHelper = Mage::helper('ddg/file');
        $helper = Mage::helper('ddg');
        $emailContactIdEmail = array();

        foreach ($subscribers as $emailContact) {
            $emailContactIdEmail[$emailContact->getId()] = $emailContact->getEmail();
        }

        //@codingStandardsIgnoreStart
        $subscribersFile = strtolower($website->getCode() . '_subscribers_with_sales_' . date('d_m_Y_Hi')
            . '.csv'
        );
        //@codingStandardsIgnoreEnd
        $helper->log('Subscriber file with sales : ' . $subscribersFile);

        //get subscriber emails
        $emails = $subscribers->getColumnValues('email');

        //subscriber collection
        $collection = $this->getCollection($emails, $website->getId());

        $mappedHash = $fileHelper->getWebsiteSalesDataFields(
            $website
        );
        $headers = $mappedHash;

        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $fileHelper->outputCSV(
            $fileHelper->getFilePath($subscribersFile), $headers
        );

        //subscriber data
        foreach ($collection as $subscriber) {
            $connectorSubscriber = Mage::getModel(
                'ddg_automation/apiconnector_subscriber', $mappedHash
            );
            $connectorSubscriber->setSubscriberData($subscriber);
            //count number of customers
            $index = array_search(
                $subscriber->getSubscriberEmail(),
                $emailContactIdEmail
            );
            if ($index) {
                $subscriberIds[] = $index;
            }

            //contact email and email type
            $connectorSubscriber->setData($subscriber->getSubscriberEmail());
            $connectorSubscriber->setData('Html');
            // save csv file data
            $fileHelper->outputCSV(
                $fileHelper->getFilePath($subscribersFile),
                $connectorSubscriber->toCSVArray()
            );
            //clear collection and free memory
            $subscriber->clearInstance();
            $updated++;
        }

        $subscriberNum = count($subscriberIds);
        //@codingStandardsIgnoreStart
        if (is_file($fileHelper->getFilePath($subscribersFile))) {
            //@codingStandardsIgnoreEnd
            if ($subscriberNum > 0) {
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBERS,
                        '',
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website->getId(),
                        $subscribersFile
                    );

                //set imported
                if ($check) {
                    Mage::getResourceModel('ddg_automation/contact')
                        ->updateSubscribers($subscriberIds);
                }
            }
        }

        return $updated;
    }

    /**
     * @codingStandardsIgnoreStart
     * @param $emails
     * @param int $websiteId
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getCollection($emails, $websiteId = 0)
    {
        $salesFlatOrder = Mage::getSingleton('core/resource')
            ->getTableName('sales_flat_order');
        $salesFlatOrderItem = Mage::getSingleton('core/resource')
            ->getTableName('sales_flat_order_item');
        $catalogProductEntityInt = Mage::getSingleton('core/resource')
            ->getTableName('catalog_product_entity_int');
        $eavAttribute = Mage::getSingleton('core/resource')
            ->getTableName('eav_attribute');
        $eavAttributeOptionValue = Mage::getSingleton('core/resource')
            ->getTableName('eav_attribute_option_value');
        $catalogCategoryProductIndex = Mage::getSingleton('core/resource')
            ->getTableName('catalog_category_product');

        $collection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToSelect(
                array(
                    'subscriber_email',
                    'store_id',
                    'subscriber_status'
                )
            )
            ->addFieldToFilter('subscriber_email', $emails);

        $alias = 'subselect';
        $statuses = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);
        $subselect = Mage::getModel(
            'Varien_Db_Select',
            Mage::getSingleton('core/resource')->getConnection('core_read')
        )
            ->from(
                $salesFlatOrder, array(
                    'customer_email as s_customer_email',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value',
                )
            )
            ->where("status in (?)", $statuses)
            ->group('customer_email');
        $columns = array(
            'last_order_date' => new Zend_Db_Expr(
                "(SELECT created_at FROM $salesFlatOrder 
                WHERE customer_email =main_table.subscriber_email 
                ORDER BY created_at DESC LIMIT 1)"
            ),
            'last_order_id' => new Zend_Db_Expr(
                "(SELECT entity_id FROM $salesFlatOrder 
                WHERE customer_email =main_table.subscriber_email 
                ORDER BY created_at DESC LIMIT 1)"
            ),
            'last_increment_id' => new Zend_Db_Expr(
                "(SELECT increment_id FROM $salesFlatOrder 
                WHERE customer_email =main_table.subscriber_email 
                ORDER BY created_at DESC LIMIT 1)"
            ),
            'first_category_id' => new Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'last_category_id' => new Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_first_brand' => new Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_last_brand' => new Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'week_day' => new Zend_Db_Expr(
                "(
                        SELECT dayname(created_at) as week_day
                        FROM $salesFlatOrder
                        WHERE customer_email = main_table.subscriber_email
                        GROUP BY week_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
            ),
            'month_day' => new Zend_Db_Expr(
                "(
                        SELECT monthname(created_at) as month_day
                        FROM $salesFlatOrder
                        WHERE customer_email = main_table.subscriber_email
                        GROUP BY month_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
            ),
            'most_category_id' => new Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesFlatOrder as sfo
                        LEFT JOIN $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email AND ccpi.category_id is not null
                        GROUP BY category_id
                        HAVING COUNT(sfoi.product_id) > 0
                        ORDER BY COUNT(sfoi.product_id) DESC
                        LIMIT 1
                    )"
            ),

            'most_brand' => new Zend_Db_Expr('NULL')
        );

        $brand = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $websiteId
        );

        if ($brand) {
            $columns['most_brand'] = new Zend_Db_Expr(
                "(
                    SELECT eaov.value from $salesFlatOrder sfo
                    LEFT JOIN $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    LEFT JOIN $catalogProductEntityInt pei on pei.entity_id = sfoi.product_id
                    LEFT JOIN $eavAttribute ea ON pei.attribute_id = ea.attribute_id
                    LEFT JOIN $eavAttributeOptionValue as eaov on pei.value = eaov.option_id
                    WHERE sfo.customer_email = main_table.subscriber_email AND ea.attribute_code = '$brand' AND eaov.value is not null
                    GROUP BY eaov.value
                    HAVING count(*) > 0
                    ORDER BY count(*) DESC
                    LIMIT 1
                )"
            );
        }

        $collection->getSelect()->columns($columns);

        $collection->getSelect()
            ->joinLeft(
                array($alias => $subselect),
                "{$alias}.s_customer_email = main_table.subscriber_email"
            );

        return $collection;
    }
}