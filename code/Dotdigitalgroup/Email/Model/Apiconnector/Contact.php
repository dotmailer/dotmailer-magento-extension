<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Contact
{

    protected $_start;
    protected $_countCustomers = 0;

    /**
     * Contact sync.
     *
     * @return array
     */
    public function sync()
    {
        $result = array('success' => true, 'message' => '');
        /** @var Dotdigitalgroup_Email_Helper_Data $helper */
        $helper       = Mage::helper('ddg');
        $this->_start = microtime(true);
        //resourse allocation
        $helper->allowResourceFullExecution();
        foreach (Mage::app()->getWebsites(true) as $website) {
            $enabled = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $sync    = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED,
                $website
            );
            if ($enabled && $sync) {

                if ( ! $this->_countCustomers) {
                    $helper->log('---------- Start customer sync ----------');
                }
                $numUpdated = $this->exportCustomersForWebsite($website);
                // show message for any number of customers
                if ($numUpdated) {
                    $result['message'] .= '</br>' . $website->getName()
                        . ', updated customers = ' . $numUpdated;
                }
            }
        }
        //sync proccessed
        if ($this->_countCustomers) {
            $message = 'Total time for sync : ' . gmdate("H:i:s", microtime(true) - $this->_start) .
                ', Total updated = ' . $this->_countCustomers;
            $helper->log($message);
            $message .= $result['message'];
            $result['message'] = $message;
        }

        return $result;
    }

    /**
     * Execute the contact sync for the website
     * number of customer synced.
     *
     * @param Mage_Core_Model_Website $website
     *
     * @return int|void
     */
    public function exportCustomersForWebsite(Mage_Core_Model_Website $website)
    {
        $customers = $headers = $allMappedHash = array();
        $helper    = Mage::helper('ddg');
        $pageSize  = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            $website
        );

        //skip if the mapping field is missing
        if ( ! $helper->getCustomerAddressBook($website)) {
            return 0;
        }

        $fileHelper   = Mage::helper('ddg/file');
        $contactModel = Mage::getModel('ddg_automation/contact');
        $contacts     = $contactModel->getContactsToImportForWebsite(
            $website->getId(), $pageSize
        );

        // no contacts for this website
        if ( ! $contacts->getSize()) {
            return 0;
        }

        //create customer filename
        $customersFile = strtolower(
            $website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv'
        );
        $helper->log('Customers file : ' . $customersFile);

        //get customer ids
        $customerIds = $contacts->getColumnValues('customer_id');

        //customer collection
        $customerCollection = $this->getCollection(
            $customerIds, $website->getId()
        );

        /**
         * HEADERS.
         */
        $mappedHash = $fileHelper->getWebsiteCustomerMappingDatafields(
            $website
        );
        $headers    = $mappedHash;

        //custom customer attributes
        $customAttributes = $helper->getCustomAttributes($website);
        if ($customAttributes) {
            foreach ($customAttributes as $data) {
                $headers[]                         = $data['datafield'];
                $allMappedHash[$data['attribute']] = $data['datafield'];
            }
        }
        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $fileHelper->outputCSV(
            $fileHelper->getFilePath($customersFile), $headers
        );
        /**
         * END HEADERS.
         */

        //customer data
        foreach ($customerCollection as $customer) {
            $connectorCustomer = Mage::getModel(
                'ddg_automation/apiconnector_customer', $mappedHash
            );
            $connectorCustomer->setCustomerData($customer);
            //count number of customers
            $customers[] = $customer->getId();

            if ($connectorCustomer) {
                foreach ($customAttributes as $data) {
                    $attribute = $data['attribute'];
                    $value     = $customer->getData($attribute);
                    $connectorCustomer->setData($value);
                }
            }

            //contact email and email type
            $connectorCustomer->setData($customer->getEmail());
            $connectorCustomer->setData('Html');
            // save csv file data for customers
            $fileHelper->outputCSV(
                $fileHelper->getFilePath($customersFile),
                $connectorCustomer->toCSVArray()
            );
            //clear collection and free memory
            $customer->clearInstance();
        }

        $customerNum = count($customers);
        $helper->log(
            'Website : ' . $website->getName() . ', customers = ' . $customerNum
        );
        $helper->log(
            '---------------------------- execution time :' . gmdate(
                "H:i:s", microtime(true) - $this->_start
            )
        );

        if (is_file($fileHelper->getFilePath($customersFile))) {
            if ($customerNum > 0) {
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_CONTACT,
                        '',
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website->getId(),
                        $customersFile
                    );

                //set imported
                if ($check) {
                    Mage::getResourceModel('ddg_automation/contact')->update(
                        $customers
                    );
                }
            }
        }
        $this->_countCustomers += $customerNum;

        return $customerNum;
    }

    /**
     * Sync a single contact.
     *
     * @param null $contactId
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function syncContact($contactId = null)
    {
        if ($contactId) {
            $contact = Mage::getModel('ddg_automation/contact')->load(
                $contactId
            );
        } else {
            $contact = Mage::registry('current_contact');
        }
        if ( ! $contact->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                'No contact found!'
            );

            return false;
        }

        $websiteId = $contact->getWebsiteId();
        $website   = Mage::app()->getWebsite($websiteId);
        $updated   = 0;
        $customers = $headers = $allMappedHash = array();
        $helper    = Mage::helper('ddg');
        $helper->log('---------- Start single customer sync ----------');
        //skip if the mapping field is missing
        if ( ! $helper->getCustomerAddressBook($website)) {
            return false;
        }
        $fileHelper = Mage::helper('ddg/file');

        $customerId = $contact->getCustomerId();
        if ( ! $customerId) {
            Mage::getSingleton('adminhtml/session')->addError(
                'Cannot manually sync guests!'
            );

            return false;
        }

        //create customer filename
        $customersFile = strtolower(
            $website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv'
        );
        $helper->log('Customers file : ' . $customersFile);

        /**
         * HEADERS.
         */
        $mappedHash = $fileHelper->getWebsiteCustomerMappingDatafields(
            $website
        );
        $headers    = $mappedHash;
        //custom customer attributes
        $customAttributes = $helper->getCustomAttributes($website);
        foreach ($customAttributes as $data) {
            $headers[]                         = $data['datafield'];
            $allMappedHash[$data['attribute']] = $data['datafield'];
        }

        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $fileHelper->outputCSV(
            $fileHelper->getFilePath($customersFile), $headers
        );
        /**
         * END HEADERS.
         */

        $customerCollection = $this->getCollection(
            array($customerId), $website->getId()
        );

        foreach ($customerCollection as $customer) {
            /**
             * DATA.
             */
            $connectorCustomer = Mage::getModel(
                'ddg_automation/apiconnector_customer', $mappedHash
            );
            $connectorCustomer->setCustomerData($customer);
            //count number of customers
            $customers[] = $connectorCustomer;
            foreach ($customAttributes as $data) {
                $attribute = $data['attribute'];
                $value     = $customer->getData($attribute);
                $connectorCustomer->setData($value);
            }
            //contact email and email type
            $connectorCustomer->setData($customer->getEmail());
            $connectorCustomer->setData('Html');
            // save csv file data for customers
            $fileHelper->outputCSV(
                $fileHelper->getFilePath($customersFile),
                $connectorCustomer->toCSVArray()
            );

            /**
             * END DATA.
             */

            $updated++;
        }

        if (is_file($fileHelper->getFilePath($customersFile))) {
            //import contacts
            if ($updated > 0) {
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_CONTACT,
                        '',
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website->getId(),
                        $customersFile
                    );

                //set imported
                if ($check) {
                    Mage::getResourceModel('ddg_automation/contact')->update(
                        $customerId
                    );
                }
            }
        }

        return $contact->getEmail();
    }


    /**
     * get customer collection
     *
     * @param $customerIds
     * @param $websiteId
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     * @throws Mage_Core_Exception
     */
    public function getCollection($customerIds, $websiteId = 0)
    {
        $customerCollection             = Mage::getResourceModel(
            'customer/customer_collection'
        )
            ->addNameToSelect()
            ->addAttributeToSelect('*')
            ->joinAttribute(
                'billing_street', 'customer_address/street', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'billing_city', 'customer_address/city', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'billing_country_code', 'customer_address/country_id',
                'default_billing', null, 'left'
            )
            ->joinAttribute(
                'billing_postcode', 'customer_address/postcode',
                'default_billing', null, 'left'
            )
            ->joinAttribute(
                'billing_telephone', 'customer_address/telephone',
                'default_billing', null, 'left'
            )
            ->joinAttribute(
                'billing_region', 'customer_address/region', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'shipping_street', 'customer_address/street',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_city', 'customer_address/city', 'default_shipping',
                null, 'left'
            )
            ->joinAttribute(
                'shipping_country_code', 'customer_address/country_id',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_postcode', 'customer_address/postcode',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_telephone', 'customer_address/telephone',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_region', 'customer_address/region',
                'default_shipping', null, 'left'
            )
            ->addAttributeToFilter('entity_id', array('in' => $customerIds));
        $customerLog                    = Mage::getSingleton('core/resource')
            ->getTableName(
                'log_customer'
            );
        $salesFlatOrderGrid          = Mage::getSingleton('core/resource')
            ->getTableName('sales_flat_order_grid');
        $salesFlatQuote               = Mage::getSingleton('core/resource')
            ->getTableName('sales_flat_quote');
        $salesFlatOrder               = Mage::getSingleton('core/resource')
            ->getTableName('sales_flat_order');
        $salesFlatOrderItem          = Mage::getSingleton('core/resource')
            ->getTableName('sales_flat_order_item');
        $catalogCategoryProductIndex = Mage::getSingleton('core/resource')
            ->getTableName('catalog_category_product');
        $eavAttributeOptionValue     = Mage::getSingleton('core/resource')
            ->getTableName('eav_attribute_option_value');
        $catalogProductEntityInt     = Mage::getSingleton('core/resource')
            ->getTableName('catalog_product_entity_int');
        $eavAttribute                  = Mage::getSingleton('core/resource')
            ->getTableName('eav_attribute');


        // get the last login date from the log_customer table
        $customerCollection->getSelect()->columns(
            array('last_logged_date' => new Zend_Db_Expr(
                "(SELECT login_at FROM  $customerLog WHERE customer_id =e.entity_id ORDER BY log_id DESC LIMIT 1)"
            ))
        );

        // customer order information
        $alias     = 'subselect';
        $statuses  = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses  = explode(',', $statuses);
        $subselect = Mage::getModel(
            'Varien_Db_Select',
            Mage::getSingleton('core/resource')->getConnection('core_read')
        )
            ->from(
                $salesFlatOrderGrid, array(
                    'customer_id as s_customer_id',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value',
                )
            )
            ->where("status in (?)", $statuses)
            ->group('customer_id');

        $columns = array(
            'last_order_date'            => new Zend_Db_Expr(
                "(SELECT created_at FROM $salesFlatOrderGrid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"
            ),
            'last_order_id'              => new Zend_Db_Expr(
                "(SELECT entity_id FROM $salesFlatOrderGrid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"
            ),
            'last_increment_id'          => new Zend_Db_Expr(
                "(SELECT increment_id FROM $salesFlatOrderGrid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"
            ),
            'last_quote_id'              => new Zend_Db_Expr(
                "(SELECT entity_id FROM $salesFlatQuote WHERE customer_id = e.entity_id ORDER BY created_at DESC LIMIT 1)"
            ),
            'first_category_id'          => new Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'last_category_id'           => new Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_first_brand' => new Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_last_brand'  => new Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesFlatOrder as sfo
                        left join $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'week_day'                   => new Zend_Db_Expr(
                "(
                        SELECT dayname(created_at) as week_day
                        FROM $salesFlatOrder
                        WHERE customer_id = e.entity_id
                        GROUP BY week_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
            ),
            'month_day'                  => new Zend_Db_Expr(
                "(
                        SELECT monthname(created_at) as month_day
                        FROM $salesFlatOrder
                        WHERE customer_id = e.entity_id
                        GROUP BY month_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
            ),
            'most_category_id'           => new Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesFlatOrder as sfo
                        LEFT JOIN $salesFlatOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id AND ccpi.category_id is not null
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
                    WHERE sfo.customer_id = e.entity_id AND ea.attribute_code = '$brand' AND eaov.value is not null
                    GROUP BY eaov.value
                    HAVING count(*) > 0
                    ORDER BY count(*) DESC
                    LIMIT 1
                )"
            );
        }

        $customerCollection->getSelect()->columns($columns);

        $customerCollection->getSelect()
            ->joinLeft(
                array($alias => $subselect),
                "{$alias}.s_customer_id = e.entity_id"
            );

        return $customerCollection;
    }
}