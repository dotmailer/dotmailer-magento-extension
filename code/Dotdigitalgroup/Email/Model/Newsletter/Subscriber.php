<?php

class Dotdigitalgroup_Email_Model_Newsletter_Subscriber
{
    const STATUS_SUBSCRIBED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_UNSUBSCRIBED = 3;
    const STATUS_UNCONFIRMED = 4;

    /**
     * Timestamp with the start of the sync.
     *
     * @var mixed
     */
    public $start;

    /**
     * Global number of subscriber updated.
     *
     * @var int
     */
    public $countGlobalSubscribers = 0;

    /**
     * SUBSCRIBER SYNC.
     *
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');

        $this->start = microtime(true);
        $helper = Mage::helper('ddg');
        foreach (Mage::app()->getWebsites(false) as $website) {
            $countSubscribers     = 0;
            $isEnabled      = $helper->isEnabled($website);
            $isMapped       = $helper->getSubscriberAddressBook($website);
            $isSyncEnabled  = $helper->isSubscriberSyncEnabled($website->getId());

            //enabled and mapped
            if ($isEnabled && $isMapped && $isSyncEnabled) {
                $emailContactModel = Mage::getModel('ddg_automation/contact');
                $limit = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_LIMIT);

                //guest subscribers emails
                $subscribersWithGuestEmails = $emailContactModel->getGuestSubscribersToImport($website, $limit)
                    ->getColumnValues('email');
                //sales order emails for guest customer
                $subscriberGuestWithSalesEmails = $emailContactModel
                    ->getSalesOrderWithCutomerEmails($subscribersWithGuestEmails);

                /**
                 * Export subscriber guests with sales data.
                 */
                if (! empty($subscriberGuestWithSalesEmails)) {

                    /**
                     * Register subscribers into importer.
                     * Subscriber that are guest and also exist in sales order table.
                     */
                    $countSubscribers += $this->exportSubscribersWithSales(
                        $website,
                        $emailContactModel->getContactWithEmails($subscriberGuestWithSalesEmails)
                    );

                    //add updated number for the website
                    $this->countGlobalSubscribers += $countSubscribers;
                }

                /**
                 * Merge guest emails with no orders and customer subscribers.
                 * customer_id
                 */
                $subscribersCustomerEmails = $emailContactModel->getSubscribersWithCustomerIdToImport($website, $limit)
                    ->getColumnValues('email');
                $subscribersGuestNoSalesData = array_diff($subscribersWithGuestEmails, $subscriberGuestWithSalesEmails);
                $subscriberCustomers = array_merge($subscribersCustomerEmails, $subscribersGuestNoSalesData);

                /**
                 * Export subscriber customers and guests(without sales data).
                 */
                if (! empty($subscriberCustomers)) {
                    $countSubscribers += $this->exportSubscribers(
                        $website,
                        $emailContactModel->getContactWithEmails($subscriberCustomers)
                    );

                    //add updated number for the website
                    $this->countGlobalSubscribers += $countSubscribers;
                }

                // found subscribers - show message
                if ($countSubscribers) {
                    $response['message'] .= '</br>' . $website->getName() . ', updated subscribers = ' .
                        $countSubscribers;
                }
            }
        }

        //global number of subscribers to set the message
        if ($this->countGlobalSubscribers) {
            //@codingStandardsIgnoreStart
            $message = 'Total time for Subscribers sync : ' . gmdate("H:i:s", microtime(true) - $this->start);
            //@codingStandardsIgnoreEnd

            $helper->log($message);
            //put the message in front
            $message .= $response['message'];
            $response['message'] = $message;
        }

        return $response;
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
        $countSubscribers = 0;
        $fileHelper = Mage::helper('ddg/file');
        //nothing to export
        $emails = $subscribers->getColumnValues('email');
        if (empty($emails))
            return 0;
        $subscribersData = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter(
                'subscriber_email',
                array('in' => $emails)
            )
            ->addFieldToSelect(array('subscriber_email', 'store_id'))
            ->toArray();
        //subscriber removed - none found
        if (empty($subscribersData)) {
            return 0;
        }

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
                $subscriber->setSubscriberImported(1)
                    ->save();
                //@codingStandardsIgnoreEnd
                $countSubscribers++;
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

        return $countSubscribers;
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @param $subscribers
     * @return int
     */
    public function exportSubscribersWithSales(Mage_Core_Model_Website $website, $subscribers)
    {
        if (empty($subscribers))
            return 0;
        $countSubscribers = 0;
        $subscriberIds = $headers = $emailContactIdEmail =array();
        $helper = Mage::helper('ddg');
        $fileHelper = Mage::helper('ddg/file');

        foreach ($subscribers as $emailContact) {
            $emailContactIdEmail[$emailContact->getId()] = $emailContact->getEmail();
        }

        //@codingStandardsIgnoreStart
        $subscribersFile = strtolower($website->getCode() . '_subscribers_with_sales_' . date('d_m_Y_Hi') . '.csv');
        //@codingStandardsIgnoreEnd
        $helper->log('Subscriber file with sales : ' . $subscribersFile);

        //get subscriber emails
        $emails = $subscribers->getColumnValues('email');

        //subscriber collection
        $collection = $this->getCollection($emails, $website->getId());
        //no subscribers found
        if ($collection->getSize() == 0)
            return 0;
        $mappedHash = $fileHelper->getWebsiteSalesDataFields($website);
        $headers = $mappedHash;
        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $fileHelper->outputCSV($fileHelper->getFilePath($subscribersFile), $headers);

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
            $fileHelper->outputCSV($fileHelper->getFilePath($subscribersFile), $connectorSubscriber->toCSVArray());
            //clear collection and free memory
            $subscriber->clearInstance();
            $countSubscribers++;
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
                        ->setSubscriberImportedForContacts($subscriberIds);
                }
            }
        }

        return $countSubscribers;
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
            );
        //only when subscriber emails are set
        if (! empty($emails)) {
            $collection->addFieldToFilter('subscriber_email', array('in' => $emails));
        }

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

    /**
     * Un-subscribe suppressed contacts.
     * @return mixed
     */
    public function unsubscribe()
    {
        $limit = 5;
        $maxToSelect = 1000;
        $result['customers'] = 0;
        $date = Mage::app()->getLocale()->date()->subHour(24);
        $suppressedEmails = array();

        // Datetime format string
        $dateString = $date->toString(Zend_Date::W3C);

        /**
         * Sync all suppressed for each store
         */
        foreach (Mage::app()->getWebsites(true) as $website) {
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);
            $skip = $i = 0;
            $contacts = array();

            // Not enabled and valid credentials
            if (! $client) {
                continue;
            }

            //there is a maximum of request we need to loop to get more suppressed contacts
            for ($i=0; $i<= $limit;$i++) {
                $apiContacts = $client->getContactsSuppressedSinceDate($dateString, $maxToSelect , $skip);

                // skip no more contacts or the api request failed
                if(empty($apiContacts) || isset($apiContacts->message)) {
                    break;
                }
                $contacts = array_merge($contacts, $apiContacts);
                $skip += 1000;
            }

            // Contacts to un-subscribe
            foreach ($contacts as $apiContact) {
                if (isset($apiContact->suppressedContact)) {
                    $suppressedContact = $apiContact->suppressedContact;
                    $suppressedEmails[] = $suppressedContact->email;
                }
            }
        }
        //Mark suppressed contacts
        if (! empty($suppressedEmails)) {
            Mage::getResourceModel('ddg_automation/contact')->unsubscribe($suppressedEmails);
        }
        return $result;
    }
}