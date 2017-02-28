<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Status
    extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    const CONNECTOR_DASHBOARD_PASSED    = 'available';
    const CONNECTOR_DASHBOARD_WARRNING  = 'connector_warning';
    const CONNECTOR_DASHBOARD_FAILED    = 'error';
    const FAST_FIX_MESSAGE = 'Fast Fix Available, Click To Enable The Mapping And Redirect Back.';

    /**
     * @var array
     */
    protected $_checkpoints
        = array(
            'valid_api_credentials'              => 'API Credentials',
            'cron_running'                       => 'Cron running',
            'conflict_check'                     => 'Conflict Check',
            'address_book_mapped'                => 'Address Book Mapping',
            'file_permission_setttings'          => 'File Permission Settings',
            'missing_files'                      => 'Missing Files',
            'contact_sync_enabled'               => 'Contact Sync Enabled',
            'contact_syncing'                    => 'Contacts Syncing',
            'subscriber_sync_enabled'            => 'Subscribers Sync Enabled',
            'subscribers_syncing'                => 'Subscribers Syncing',
            'automation_active'                  => 'Automation Programs Active',
            'abandoned_carts_enabled'            => 'Abandoned Carts Enabled',
            'data_field_mapped'                  => 'Data Field Mapped',
            'order_enabled'                      => 'Order Sync Enabled',
            'order_syncing'                      => 'Orders Syncing',
            'custom_order_attributes'            => 'Custom Order Attributes',
            'quote_enabled'                      => 'Quote Sync Enabled',
            'last_abandoned_cart_sent_day'       => 'Last Abandoned Cart Sent Day',
            'disable_newsletter_success_enabled' => 'Disable Newsletter Success Enabled',
            'system_information'                 => 'System Information'

        );

    /**
     * Set the template.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('connector/dashboard/status.phtml');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('ddg')->__('Marketing Automation System Status');
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('ddg')->__('Marketing Automation System Status');
    }

    /**
     * Collapse key for the fieldset state.
     *
     * @param $key
     *
     * @return bool
     */
    protected function _getCollapseState($key)
    {
        $extra = Mage::getSingleton('admin/session')->getUser()->getExtra();
        if (isset($extra['configState'][$key])) {
            return $extra['configState'][$key];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getCheckpoints()
    {
        return $this->_checkpoints;
    }

    /**
     * @return array
     */
    protected function getDisplayData()
    {

        $displayData = array();
        foreach ($this->getCheckpoints() as $key => $checkpoint) {
            $method = $this->getFormatedMethodName($key);

            if (is_callable(array($this, $method))) {
                $data        = $this->$method();
                $showHowTo   = '';
                $collapsable = '1';

                if ($data->getStyle() == 'connector_passed') {
                    $showHowTo   = 'no_display';
                    $collapsable = '0';
                }

                if ($this->_getCollapseState($key) !== false) {
                    $collapsable = $this->_getCollapseState($key);
                }

                $displayData[$key] = array(
                    'showHowTo'   => $showHowTo,
                    'collapsable' => $collapsable,
                    'data'        => $data
                );
            }
        }

        return $displayData;
    }

    /**
     * @param $checkpoint
     */
    public function addCheckpoint($checkpoint)
    {
        $this->_checkpoints[$checkpoint->getName()] = $checkpoint;
    }


    /**
     * Check cron for the customer sync.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content|false|Mage_Core_Model_Abstract
     */
    public function cronRunning()
    {
        $resultContent = Mage::getModel('ddg_automation/adminhtml_dashboard_content');
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Cron Status : ')
            ->setMessage('Cron is running.');
        $message
                          = 'No cronjob task found. Check if cron is configured correctly.';
        $howToSetupCron
            = 'For more information 
            <a href="http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job">
            how to setup the Magento cronjob.</a>';
        $lastCustomerSync = Mage::getModel('ddg_automation/cron')
            ->getLastCustomerSync();

        if ($lastCustomerSync === false) {
            $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                ->setHowto($howToSetupCron);
        } else {
            $timespan = Mage::helper('ddg')->dateDiff($lastCustomerSync);
            //last cron was less then 5min
            if ($timespan <= 5 * 60) {
                $resultContent->setTitle('Cronjob is working : ');
                $message = sprintf(
                    '(Last execution: %s minute(s) ago) ', round($timespan / 60)
                );
            } elseif ($timespan > 5 * 60 && $timespan <= 60 * 60) {
                //last cron execution was between 15min and 60min
                $resultContent->setTitle('Last customer sync : ')
                    ->setStyle(self::CONNECTOR_DASHBOARD_FAILED);
                $message = sprintf(' %s minutes. ', round($timespan / 60));
            } else {
                //last cron was more then an hour
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setHowto('Last customer sync is older than one hour.')
                    ->setHowto($howToSetupCron);
            }
        }

        $resultContent->setMessage($message);

        return $resultContent;
    }

    /**
     * Address Book Mapping.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function addressBookMapped()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Configuration For Address Book Status : ')
            ->setMessage('Looks Great.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $link        = Mage::helper('adminhtml')->getUrl(
                '*/system_config/edit/section/connector_sync_settings/website/'
                . $website->getCode()
            );

            $customerMapped   = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID
            ))
                ? true
                :
                'Not mapped!';
            $subscriberMapped = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID
            ))
                ? true
                :
                'Not mapped!';
            $guestMapped      = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID
            ))
                ? true
                :
                'Not mapped!';

            if ($customerMapped !== true || $subscriberMapped !== true
                || $guestMapped !== true
            ) {
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website'     => $websiteName,
                            'Customers'   => ($customerMapped !== true)
                                ? $customerMapped . ' <a href="' . $link
                                . '">configuration</a>' : 'Mapped.',
                            'Subscribers' => ($subscriberMapped !== true)
                                ? $subscriberMapped . ' <a href="' . $link
                                . '">configuration</a>' : 'Mapped.',
                            'Guests'      => ($guestMapped !== true)
                                ? $guestMapped . ' <a href="' . $link
                                . '">configuration</a>' : 'Mapped.'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * ROI Tracking.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function roiTrackingEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('ROI Tracking Status : ')
            ->setMessage('Looks Great.');

        $valid = true;
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();

            $roiConfig    = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED
            )) ? true : 'Not Mapped! ';
            $pageTracking = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED
            )) ? true : 'Not Mapped! ';
            //not mapped show options
            if ($roiConfig !== true || $pageTracking !== true) {
                //links to enable and get redirected back
                $roiUrl  = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED',
                          'website' => $website->getId())
                );
                $pageUrl = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED',
                          'website' => $website->getId())
                );

                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'ROI'     => ($roiConfig !== true) ? $roiConfig
                                . ' <a href="' . $roiUrl . '">enable</a>'
                                : 'Mapped.',
                            'PAGE'    => ($pageTracking !== true)
                                ? $pageTracking . ' <a href="' . $pageUrl
                                . '">enable</a>' : 'Mapped.'
                        )
                    );
                $valid = false;
            }
        }

        //validation failed
        if (! $valid) {
            $resultContent->setHowto(self::FAST_FIX_MESSAGE);
        }

        return $resultContent;
    }

    /**
     * File Permissions.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function filePermissionSetttings()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Files/Folders Permission Settings : ')
            ->setMessage('Looks Great.');

        /**
         * Arhive and email export directories.
         */
        $emailDir   = Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'export'
            . DIRECTORY_SEPARATOR . 'email';
        $archiveDir = Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'export'
            . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR . 'archive';

        $checkEmail   = Mage::helper('ddg/file')->getPathPermission($emailDir);
        $checkArchive = Mage::helper('ddg/file')->getPathPermission(
            $archiveDir
        );

        //file persmission failed
        if ($checkEmail != 755 && $checkEmail != 777
            || $checkArchive != 755
            && $checkArchive != 777
        ) {
            $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                ->setMessage('Wrong Permission For Directory : 777 or 755');

            //list of directory permission checked
            if ($checkEmail != 755 || $checkEmail != 777) {
                $resultContent->setHowto(
                    $emailDir . ' is set to : ' . $checkEmail
                );
            }

            if ($checkArchive != 755 || $checkArchive != 777) {
                $resultContent->setHowto(
                    $archiveDir . ' is set to : ' . $checkArchive
                );
            }
        }

        return $resultContent;
    }

    /**
     * Check for missing files.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function missingFiles()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );

        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Missing Files : ')
            ->setMessage('Looks Great.');

        $filePath = Mage::getModuleDir(
            'etc', Dotdigitalgroup_Email_Helper_Config::MODULE_NAME
        ) . DS . 'files.yaml';
        //@codingStandardsIgnoreStart
        $config   = Zend_Config_Yaml::decode(file_get_contents($filePath));
        //@codingStandardsIgnoreEnd
        /**
         * Code dirs.
         */
        $etcDir        = Mage::getModuleDir(
            'etc', Dotdigitalgroup_Email_Helper_Config::MODULE_NAME
        );
        $controllerDir = Mage::getModuleDir(
            'controllers', Dotdigitalgroup_Email_Helper_Config::MODULE_NAME
        );
        $sqlDir        = Mage::getModuleDir(
            'sql', Dotdigitalgroup_Email_Helper_Config::MODULE_NAME
        );
        $localeDir     = Mage::getBaseDir('locale');
        $rootDir       = Mage::getModuleDir(
            '', Dotdigitalgroup_Email_Helper_Config::MODULE_NAME
        );
        $blockDir      = $rootDir . DS . 'Block';
        $helperDir     = $rootDir . DS . 'Helper';
        $modelDir      = $rootDir . DS . 'Model';

        /**
         * Design dir.
         */
        $designDir = Mage::getBaseDir('design');

        /**
         * Skin dir.
         */
        $skinDir = Mage::getBaseDir('skin');

        /**
         * Js dir
         */
        $jsDir = Mage::getBaseDir('base') . DS . 'js';

        $filesToCheck = array(
            $config['etc'],
            $config['controllers'],
            $config['sql'],
            $config['locale'],
            $config['block'],
            $config['helper'],
            $config['model'],
            $config['design'],
            $config['skin'],
            $config['js']
        );
        $pathToCheck  = array(
            $etcDir,
            $controllerDir,
            $sqlDir,
            $localeDir,
            $blockDir,
            $helperDir,
            $modelDir,
            $designDir,
            $skinDir,
            $jsDir
        );
        foreach ($filesToCheck as $subdir) {
            foreach ($subdir as $path) {
                $file = $pathToCheck[0] . DS . str_replace('#', DS, $path);
                //@codingStandardsIgnoreStart
                if (! file_exists($file)) {
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage('')
                        ->setHowto('File not found : ' . $file);
                }
                //@codingStandardsIgnoreEnd
            }

            array_shift($pathToCheck);
        }

        return $resultContent;
    }

    /**
     * Contact Sync Status.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function contactSyncEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Contacts Sync Status : ')
            ->setMessage('Looks Great.');

        $valid = true;
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $contact     = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED
            )) ? true : 'Disabled!';
            //disabled show data table
            if ($contact !== true) {
                //redirection url to enable website config
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'Status'  => ($contact) ? $contact . ' <a href="'
                                . $url . '">enable</a>' : 'Enabled.'
                        )
                    );
                $valid = false;
            }
        }

        //validation failed
        if (! $valid) {
            $resultContent->setHowto(self::FAST_FIX_MESSAGE);
        }

        return $resultContent;
    }

    /**
     * Check if contact is syncing by counting the number of contacts imported.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function contactSyncing()
    {
        //content to render
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Contacts Sync : ')
            ->setMessage('Looks Great.');
        $contactModel = Mage::getModel('ddg_automation/contact');

        //global email duplicates
        if (Mage::getResourceModel('customer/customer')->findEmailDuplicates()) {
            //duplicate email customers
            $customers      = Mage::helper('ddg')
                ->getCustomersWithDuplicateEmails();
            $customerEmails = implode(
                ',   ', $customers->getColumnValues('email')
            );
            //render the email duplicates
            $resultContent->setHowto('Found Duplicate Customers Emails :')
                ->setHowto($customerEmails);
        }

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteId = $website->getId();
            //total customers for website
            $countCustomers = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToFilter('website_id', $websiteId)
                ->getSize();

            //skip if no customers
            if (! $countCustomers) {
                continue;
            }

            //total contacts from customer address book
            $customerAddressBook      = $this->_getAddressBookContacts(
                $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID
                ), $website
            );
            $countAddressbookContacts = ($customerAddressBook)
                ? $customerAddressBook->contacts : 0;
            //total contacts as customers
            $countCustomerContacts = $contactModel->getNumberCustomerContacts(
                $websiteId
            );

            //suppressed contacts
            $suppressed = $contactModel->getNumberCustomerSuppressed(
                $websiteId
            );

            //table data
            $tableData = array(
                'Website'                       => $website->getName(),
                'Total Customers/Contacts'      => $countCustomers . '/ '
                    . $countCustomerContacts,
                'Customer AddressBook Contacts' => ($customerAddressBook)
                    ? $customerAddressBook->name . ' : '
                    . $countAddressbookContacts : 'Not Mapped.',
                'Suppressed'                    => $suppressed,
                'Synced'                        => $contactModel->getNumberCustomerSynced(
                    $websiteId
                )
            );

            if ($countCustomers > $countCustomerContacts + $suppressed) {
                $tableData['Status'] = 'Syncing..';
                //all customers syned.
            } else {
                $tableData['Status'] = 'Synced';
            }

            //not valid response remove status
            if (! $countAddressbookContacts) {
                unset($tableData['Status']);
            }

            //no contacts
            if (! $countCustomers) {
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setTitle(
                        'Contacts Sync (ignore if you have reset contacts for reimport) : '
                    )
                    ->setMessage('');
                $tableData['Status'] = 'No Imported Contacts Found!';
                unset($tableData['Imported Contacts']);
            }

            $resultContent->setTable($tableData);
        }

        return $resultContent;
    }

    /**
     * Check for subscribers sync status.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function subscriberSyncEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Subscribers Sync Status : ')
            ->setMessage('Looks Great.');

        $passed = true;
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $contact     = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED
            ))
                ? true
                :
                'Disabled!';
            //disabled show data table
            if ($contact !== true) {
                //redirection url to enable website config
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'Status'  => ($contact) ? $contact . ' <a href="'
                                . $url . '">enable</a>' : 'Enabled.'
                        )
                    );
                $passed = false;
            }
        }

        //if validation not passed
        if (! $passed) {
            $resultContent->setHowto(self::FAST_FIX_MESSAGE);
        }

        return $resultContent;
    }

    /**
     * Subscribers syncing status.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function subscribersSyncing()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Subscribers Sync : ')
            ->setMessage('Looks Great.');
        $contactModel = Mage::getModel('ddg_automation/contact');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteId   = $website->getId();
            $websiteName = $website->getName();
            $storeIds    = $website->getStoreIds();
            //total subscribers
            $countSubscribers = Mage::getModel('newsletter/subscriber')
                ->getCollection()
                ->useOnlySubscribed()
                ->addStoreFilter($storeIds)
                ->getSize();
            //skip if no subscriber
            if (! $countSubscribers) {
                continue;
            }

            //total contacts subscribed
            $countSubscribedContacts = $contactModel->getNumberSubscribers(
                $websiteId
            );
            //total contacts subscribed imported
            $countSubscribersImported
                = $contactModel->getNumberSubscribersSynced($websiteId);

            //number of address
            $countAddressbookContacts = $this->_getAddressBookContacts(
                $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID
                ), $website
            );

            $tableData = array(
                'Website'                         => $websiteName,
                'Total Subscribers/Contacts'      => $countSubscribers . '/ '
                    . $countSubscribedContacts,
                'Subscriber AddressBook Contacts' => ($countAddressbookContacts)
                    ? $countAddressbookContacts->name . ' : '
                    . $countAddressbookContacts->contacts : 'Not Mapped.',
                'Imported'                        => $countSubscribersImported
            );

            $tableData['Status'] = '';

            //no imported contacts
            if (! $countSubscribersImported) {
                $tableData['Status'] = 'No Imported Subscribers Found.';
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setTitle(
                        'Subscriber Sync (ignore if you have reset subscribers for reimport) : '
                    )
                    ->setMessage('');
            }

            $resultContent->setTable($tableData);
        }

        return $resultContent;
    }

    //check the mapped programs are active
    public function automationActive()
    {
        $disableCustomer = $disableSubscriber = $disableOrder
            = $disableGuestOrder = $disableReviews = $disableWishlist = '';
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Automation Program Status :')
            ->setMessage('');

        foreach (Mage::app()->getWebsites() as $website) {
            $customerProgram   = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER
            );
            $subscriberProgram = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER
            );
            $orderProgram      = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER
            );
            $guestOrderProgram = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER
            );
            $reviewsProgram    = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW
            );
            $wishlistProgram   = $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST
            );

            //programs
            $cusProgram        = $this->_getWebisteProgram(
                $customerProgram, $website
            );
            $subProgram        = $this->_getWebisteProgram(
                $subscriberProgram, $website
            );
            $orderProgram      = $this->_getWebisteProgram(
                $orderProgram, $website
            );
            $guestOrderProgram = $this->_getWebisteProgram(
                $guestOrderProgram, $website
            );
            $reviewsProgram    = $this->_getWebisteProgram(
                $reviewsProgram, $website
            );
            $wishlistProgram   = $this->_getWebisteProgram(
                $wishlistProgram, $website
            );

            //check for wishlist program
            if ($wishlistProgram) {
                if ($wishlistProgram->status != 'Active') {
                    //set the status as failed
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage(
                            '  Consider to disable not active programs'
                        );

                    $disableWishlist = Mage::helper('adminhtml')->getUrl(
                        '*/connector/enablewebsiteconfiguration', array(
                            'path'    => 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST',
                            'value'   => '0',
                            'website' => $website->getId()
                        )
                    );
                    $disableWishlist = 'Deactivated! <a href="'
                        . $disableWishlist . '">click</a> to disable';
                } else {
                    $disableWishlist = $wishlistProgram->status;
                }
            }

            //check for order program
            if ($orderProgram) {
                if ($orderProgram->status != 'Active') {
                    //set the status as failed
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage(
                            '  Consider to disable not active programs'
                        );

                    $disableOrder = Mage::helper('adminhtml')->getUrl(
                        '*/connector/enablewebsiteconfiguration', array(
                            'path'    => 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER',
                            'value'   => '0',
                            'website' => $website->getId()
                        )
                    );
                    $disableOrder = 'Deactivated! <a href="' . $disableOrder
                        . '">click</a> to disable';
                } else {
                    $disableOrder = $orderProgram->status;
                }
            }

            //check for review program
            if ($reviewsProgram) {
                if ($reviewsProgram->status != 'Active') {
                    //set the status as failed
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage(
                            '  Consider to disable not active programs'
                        );

                    $disableReviews = Mage::helper('adminhtml')->getUrl(
                        '*/connector/enablewebsiteconfiguration', array(
                            'path'    => 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW',
                            'value'   => '0',
                            'website' => $website->getId()
                        )
                    );
                    $disableReviews = 'Deactivated! <a href="' . $disableReviews
                        . '">click</a> to disable';
                } else {
                    $disableReviews = $reviewsProgram->status;
                }
            }

            //check for guest order program
            if ($guestOrderProgram) {
                if ($guestOrderProgram->status != 'Active') {
                    //set the status as failed
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage(
                            '  Consider to disable not active programs'
                        );

                    $disableGuestOrder = Mage::helper('adminhtml')->getUrl(
                        '*/connector/enablewebsiteconfiguration', array(
                            'path'    => 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER',
                            'value'   => '0',
                            'website' => $website->getId()
                        )
                    );
                    $disableGuestOrder = 'Deactivated! <a href="'
                        . $disableGuestOrder . '">click</a> to disable';
                } else {
                    $disableGuestOrder = $guestOrderProgram->status;
                }
            }

            //check for customer program
            if ($cusProgram) {
                if ($cusProgram->status != 'Active') {
                    //set the status as failed
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage(
                            '  Consider to disable not active programs'
                        );

                    $disableCustomer = Mage::helper('adminhtml')->getUrl(
                        '*/connector/enablewebsiteconfiguration', array(
                            'path'    => 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER',
                            'value'   => '0',
                            'website' => $website->getId()
                        )
                    );
                    $disableCustomer = 'Deactivated! <a href="'
                        . $disableCustomer . '">click</a> to disable';
                } else {
                    $disableCustomer = $cusProgram->status;
                }
            }

            //check for subscriber program
            if ($subProgram) {
                if ($subProgram->status != 'Active') {
                    // set the status failed
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage(
                            '  Consider to disable not active programs'
                        );

                    //link to disbale config
                    $disableSubscriber = Mage::helper('adminhtml')->getUrl(
                        '*/connector/enablewebsiteconfiguration', array(
                            'path'    => 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER',
                            'value'   => '0',
                            'website' => $website->getId()
                        )
                    );
                    $disableSubscriber = 'Deactivated <a href="'
                        . $disableSubscriber . '">click</a> to disable';
                } else {
                    $disableSubscriber = $subProgram->status;
                }
            }

            $tableData = array(
                'Website'             => $website->getName(),
                'Customer Program'    => (isset($cusProgram->name))
                    ? $cusProgram->name : 'Disabled',
                'Customer Status'     => (isset($cusProgram->status))
                    ? $disableCustomer : '',
                'Subscriber Program'  => (isset($subProgram->name))
                    ? $subProgram->name : 'Disabled',
                'Subscriber Status'   => (isset($subProgram->status))
                    ? $disableSubscriber : '',
                'Order Program'       => (isset($orderProgram->name))
                    ? $orderProgram->name : 'Disabled',
                'Order Status'        => (isset($orderProgram->status))
                    ? $disableOrder : '',
                'Guest Order Program' => (isset($guestOrderProgram->name))
                    ? $guestOrderProgram->name : 'Disabled',
                'Guest Order Status'  => (isset($guestOrderProgram->status))
                    ? $disableGuestOrder : '',
                'Review Program'      => (isset($reviewsProgram->name))
                    ? $reviewsProgram->name : 'Disabled',
                'Review Status'       => (isset($reviewsProgram->status))
                    ? $disableReviews : '',
                'Wishlist Program'    => (isset($wishlistProgram->name))
                    ? $wishlistProgram->name : 'Disabled',
                'Wishlist Status'     => (isset($wishlistProgram->status))
                    ? $disableWishlist : '',
            );

            //set the content with table data
            $resultContent->setTable($tableData);
        }

        return $resultContent;
    }

    /**
     * Abandoned carts status.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function abandonedCartsEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );

        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Abandoned Carts Status : ')
            ->setMessage('Looks Great.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName       = $website->getName();
            $abandonedCusomerOne = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_1
            ))
                ? true
                :
                'Disabled!';
            $abandonedCusomerTwo = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_2
            ))
                ? true
                :
                'Disabled!';
            $abandonedCusomerThree = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_3
            ))
                ? true
                :
                'Disabled!';
            $abandonedGuestOne   = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_1
            ))
                ? true
                :
                'Disabled!';
            $abandonedGuestTwo   = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_2
            ))
                ? true
                :
                'Disabled!';
            $abandonedGuestThree   = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_3
            ))
                ? true
                :
                'Disabled!';

            if ($abandonedCusomerOne !== true || $abandonedCusomerTwo !== true
                || $abandonedCusomerThree !== true
                || $abandonedGuestOne !== true
                || $abandonedGuestTwo !== true
                || $abandonedGuestThree !== true
            ) {
                //customer abandoned links to enable
                $customerOne = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_1',
                          'website' => $website->getId())
                );
                $customerTwo = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_2',
                          'website' => $website->getId())
                );
                $customerThree = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_3',
                          'website' => $website->getId())
                );
                //guests abandoned links to enable
                $guestOne = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_1',
                          'website' => $website->getId())
                );
                $guestTwo = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_2',
                          'website' => $website->getId())
                );
                $guestThree = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_3',
                          'website' => $website->getId())
                );

                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('Don\'t forget to map')
                    ->setTable(
                        array(
                            'Website'              => $websiteName,
                            'Customer Abandoned 1' => ($abandonedCusomerOne
                                !== true) ? $abandonedCusomerOne . ' <a href="'
                                . $customerOne . '">enable</a>' : 'Enabled',
                            'Customer Abandoned 2' => ($abandonedCusomerTwo
                                !== true) ? $abandonedCusomerTwo . ' <a href="'
                                . $customerTwo . '">enable</a>' : 'Enabled',
                            'Customer Abandoned 3' => ($abandonedCusomerThree
                                !== true) ? $abandonedCusomerThree . ' <a href="'
                                . $customerThree . '">enable</a>' : 'Enabled',
                            'Guest Abandoned 1'    => ($abandonedGuestOne
                                !== true) ? $abandonedGuestOne . ' <a href="'
                                . $guestOne . '">enable</a>' : 'Enabled',
                            'Guest Abandoned 2'    => ($abandonedGuestTwo
                                !== true) ? $abandonedGuestTwo . ' <a href="'
                                . $guestTwo . '">enable</a>' : 'Enabled',
                            'Guest Abandoned 3'    => ($abandonedGuestThree
                                !== true) ? $abandonedGuestThree . ' <a href="'
                                . $guestThree . '">enable</a>' : 'Enabled',
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Crazy mapping checking.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function dataFieldMapped()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );

        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Default Datafields Mapped Status : ')
            ->setMessage('All Datafields Are Mapped.');

        foreach (Mage::app()->getWebsites() as $website) {
            $passed    = true;
            $mapped    = 0;
            $tableData = array();
            //website name for table data
            $websiteName          = $website->getName();
            $tableData['Website'] = $websiteName;
            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ID
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_FIRSTNAME
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LASTNAME
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DOB
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_GENDER
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_CREATED_AT
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_LOGGED_DATE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_CUSTOMER_GROUP
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_REVIEW_COUNT
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_REVIEW_DATE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_ADDRESS_1
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_ADDRESS_2
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_CITY
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_STATE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_COUNTRY
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_POSTCODE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_TELEPHONE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_ADDRESS_1
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_ADDRESS_2
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_CITY
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_STATE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_COUNTRY
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_POSTCODE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_TELEPHONE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_TOTAL_NUMBER_ORDER
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_AOV
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_TOTAL_SPEND
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_TOTALREFUND
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (! $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_SUBSCRIBER_STATUS
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (!$website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_BILLING_COMPANY_NAME
            )
            ) {
                $passed = false;
                $mapped++;
            }

            if (!$website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_COMPANY_NAME
            )
            ) {
                $passed = false;
                $mapped++;
            }

            $tableData['Mapped Percentage'] = number_format(
                (1 - $mapped / 32) * 100, 2
            ) . ' %';
            //mapping not complete.
            if (! $passed) {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/system_config/edit/section/connector_data_mapping/website/'
                    . $website->getCode()
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage(
                        'Click <a href="' . $url
                        . '">here</a> to change mapping configuration.'
                    );
            }

            $resultContent->setTable($tableData);
        }

        return $resultContent;
    }


    /**
     * Validate API Credentials.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function validApiCredentials()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('API Credentials Status : ')
            ->setMessage('Valid.');
        $helper = Mage::helper('ddg');
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $websiteId   = $website->getId();

            $apiUsername = $helper->getApiUsername($websiteId);
            $apiPassword = $helper->getApiPassword($websiteId);

            $api = Mage::getModel('ddg_automation/apiconnector_test')
                ->ajaxvalidate($apiUsername, $apiPassword);

            if ($api != 'Credentials Valid.') {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/system_config/edit/section/connector_api_credentials/website/'
                    . $website->getCode()
                );

                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website'  => $websiteName,
                            'Status'   => $api,
                            'Fast Fix' => 'Click <a href="' . $url
                                . '">here</a> to enter new api credentials.'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Order sync enabled.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function orderEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Order Sync : ')
            ->setMessage('Enabled.');

        $passed = true;
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $order       = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED
            ))
                ? true
                :
                'Disabled!';

            if ($order !== true) {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'Status'  => $order . ' <a href="' . $url
                                . '">enable</a>'
                        )
                    );
                $passed = false;
            }
        }

        //validation failed
        if (! $passed) {
            $resultContent->setHowto(self::FAST_FIX_MESSAGE);
        }

        return $resultContent;
    }

    /**
     * check if any custom order attribute selected
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function customOrderAttributes()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Custom Order Attributes : ')
            ->setMessage('Selected.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName         = $website->getName();
            $customOrderAttibute = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES
            )) ? true : false;

            if ($customOrderAttibute !== true) {
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setTitle(
                        'Custom order attribute not selected (ignore if you do not want to import custom order attributes) :'
                    )
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'Status'  => 'No Custom Order Attribute Selected'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Check if any orders are imported.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function orderSyncing()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Order Syncing : ')
            ->setMessage('Looks Great.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $storeIds    = $website->getStoreIds();

            if (empty($storeIds)) {
                continue;
            }

            //numbser of orders marked as imported
            $numOrders = Mage::getModel('ddg_automation/order')->getCollection()
                ->addFieldToFilter('email_imported', 1)
                ->addFieldToFilter('store_id', array('in', $storeIds))->getSize(
                );

            if (! $numOrders) {
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setTitle(
                        'Order Syncing (ignore if you have reset orders for reimport) :'
                    )
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'Status'  => 'No Imported Orders Found'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Quote sync enabled.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function quoteEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Quote Sync : ')
            ->setMessage('Enabled.');

        $passed = true;
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $quote       = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED
            ))
                ? true
                :
                'Disabled!';

            if ($quote !== true) {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage('')
                    ->setTable(
                        array(
                            'Website' => $websiteName,
                            'Status'  => $quote . ' <a href="' . $url
                                . '">enable</a>'
                        )
                    );
                $passed = false;
            }
        }

        //validation failed
        if (! $passed) {
            $resultContent->setHowto(self::FAST_FIX_MESSAGE);
        }

        return $resultContent;
    }

    /**
     * Review sync enabled.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     * Display the transactional data for orders to be removed.
     */
    public function reviewEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Review Sync : ')
            ->setMessage('Enabled.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $review      = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED
            ))
                ? true
                :
                'Disabled';

            if ($review !== true) {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage(
                        'Don\'t forget to enable if you want to sync reviews.'
                    )
                    ->setTable(
                        array(
                            'Website'  => $websiteName,
                            'Status'   => $review,
                            'Fast Fix' => 'Click  <a href="' . $url
                                . '">here </a>to enable.'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Check if any reviews are imported.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function reviewSyncing()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Review Syncing : ')
            ->setMessage('Looks Great.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $storeIds    = $website->getStoreIds();

            if (empty($storeIds)) {
                continue;
            }

            //number of reviews marked as imported
            $numReview = Mage::getModel('ddg_automation/review')->getCollection(
            )
                ->addFieldToFilter('review_imported', 1)
                ->addFieldToFilter('store_id', array('in', $storeIds))
                ->getSize();

            //total reviews
            $totalReview = Mage::getModel('ddg_automation/review')
                ->getCollection()
                ->addFieldToFilter('store_id', array('in', $storeIds))
                ->getSize();

            $tableData = array(
                'Website'       => $websiteName,
                'Total Reviews' => $totalReview,
                'Imported'      => $numReview
            );

            $tableData['Status'] = 'Importing';

            if (! $numReview) {
                $tableData['Status'] = 'No Imported Review Found.';
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setTitle(
                        'Review Sync (ignore if you have reset wishlist) : '
                    )
                    ->setMessage('');
            }

            $resultContent->setTable($tableData);
        }

        return $resultContent;
    }

    /**
     * Get the last date for abandaned carts.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function lastAbandonedCartSentDay()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Last Abandoned Summary : ');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $client      = Mage::helper('ddg')->getWebsiteApiClient($website);

            if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
                //customer carts
                $customerCampaignOne = $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CAMPAIGN_1
                );
                $customerCampaignTwo = $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CAMPAIGN_2
                );
                $customerCampaignThree = $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CAMPAIGN_3
                );

                //guests carts
                $guestCampaignOne = $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ABANDONED_CAMPAIGN_1
                );
                $guestCampaignTwo = $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ABANDONED_CAMPAIGN_2
                );
                $guestCampaignThree = $website->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ABANDONED_CAMPAIGN_3
                );

                //date customer carts
                $cusDateSentOne = ($customerCampaignOne) ? $client->getCampaignSummary(
                    $customerCampaignOne
                ) : '';
                $cusDateSentTwo = ($customerCampaignTwo) ? $client->getCampaignSummary(
                    $customerCampaignTwo
                ) : '';
                $cusDateSentThree = ($customerCampaignThree) ? $client->getCampaignSummary(
                    $customerCampaignThree
                ) : '';

                //date guest carts
                $resGuestOne = ($guestCampaignOne) ? $client->getCampaignSummary(
                    $guestCampaignOne
                ) : '';
                $resGuestTwo = ($guestCampaignTwo) ? $client->getCampaignSummary(
                    $guestCampaignTwo
                ) : '';
                $resGuestThree = ($guestCampaignThree) ? $client->getCampaignSummary(
                    $guestCampaignThree
                ) : '';

                /**
                 * Customers.
                 */
                $customerCampaignOne = (isset($cusDateSentOne->dateSent)
                    ? $cusDateSentOne->dateSent : 'Not Sent/Selected');
                $customerCampaignTwo = (isset($cusDateSentTwo->dateSent)
                    ? $cusDateSentTwo->dateSent : 'Not Sent/Selected');
                $customerCampaignThree = (isset($cusDateSentThree->dateSent)
                    ? $cusDateSentThree->dateSent : 'Not Sent/Selected');

                /**
                 * Guests.
                 */
                $guestCampaignOne = (isset($resGuestOne->dateSent)
                    ? $resGuestOne->dateSent : 'Not Sent/Selected');
                $guestCampaignTwo = (isset($resGuestTwo->dateSent)
                    ? $resGuestTwo->dateSent : 'Not Sent/Selected');
                $guestCampaignThree = (isset($resGuestThree->dateSent)
                    ? $resGuestThree->dateSent : 'Not Sent/Selected');


                $resultContent->setTable(
                    array(
                        'Website' => $websiteName,
                        'Customer Campaign 1' => $customerCampaignOne,
                        'Customer Campaign 2' => $customerCampaignTwo,
                        'Customer Campaign 3' => $customerCampaignThree,
                        'Guest Campaign 1' => $guestCampaignOne,
                        'Guest Campaign 2' => $guestCampaignTwo,
                        'Guest Campaign 3' => $guestCampaignThree
                    )
                );
            }
        }

        return $resultContent;
    }

    /**
     * Conflict checker.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function conflictCheck()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Conflict Status : ')
            ->setMessage('Looks Great.');

        //check the module override and conflict
        $rewrites = Mage::helper('ddg/dashboard')->getRewrites();

        if ($rewrites === false) {
            $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
                ->setMessage('No Conflict Rewrites Found.');
        } else {
            $types = array('blocks', 'models', 'helpers');
            foreach ($types as $t) {
                if (! empty($rewrites[$t])) {
                    $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                        ->setMessage('Conflicting Rewrite Found : ');

                    foreach ($rewrites[$t] as $node => $rewriteInfo) {
                        $resultContent->setTable(
                            array(
                                'Type'         => $t,
                                'Class'        => implode(
                                    ', ', array_values($rewriteInfo['classes'])
                                ),
                                'Rewrites'     => '',
                                'Loaded Class' => ''
                            )
                        );
                    }
                }
            }

            $conflictCounter = 0;
            $tableData       = array();
            foreach ($rewrites as $type => $data) {
                //@codingStandardsIgnoreStart
                if (count($data) > 0 && is_array($data)) {
                    foreach ($data as $class => $rewriteClass) {
                        if (count($rewriteClass) > 1) {
                            if ($this->_isInheritanceConflict($rewriteClass)) {
                                $resultContent->setTable(
                                    array(
                                        'Type'         => $type,
                                        'Class'        => $class,
                                        'Rewrites'     => implode(
                                            ', ', $rewriteClass['classes']
                                        ),
                                        'Loaded Class' => $this->_getLoadedClass(
                                            $type, $class
                                        ),
                                    )
                                );

                                $conflictCounter++;
                            }
                        }
                    }
                }
                //@codingStandardsIgnoreEnd
            }

            if (! empty($tableData)) {
                $resultContent->setTable($tableData);
            }
        }

        return $resultContent;
    }

    /**
     * Returns loaded class by type like models or blocks
     *
     * @param string $type
     * @param string $class
     *
     * @return string
     */
    protected function _getLoadedClass($type, $class)
    {
        switch ($type) {
            case 'blocks':
                return Mage::getConfig()->getBlockClassName($class);

            case 'helpers':
                return Mage::getConfig()->getHelperClassName($class);

            default:
            case 'models':
                return Mage::getConfig()->getModelClassName($class);
        }
    }

    /**
     * Check if rewritten class has inherited the parent class.
     * If yes we have no conflict. The top class can extend every core class.
     * So we cannot check this.
     *
     * @var array $classes
     * @return bool
     */
    protected function _isInheritanceConflict($classes)
    {
        $classes   = array_reverse($classes);
        $numClases = count($classes);

        for ($i = 0; $i < $numClases - 1; $i++) {
            try {
                if (class_exists($classes[$i])
                    && class_exists($classes[$i + 1])
                ) {
                    if (! is_a($classes[$i], $classes[$i + 1], true)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                return true;
            }
        }

        return false;
    }

    /**
     * System information about the version used and the memory limits.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function systemInformation()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED);

        //compatibility with the old versions
        if (version_compare(Mage::getVersion(), '1.6.2.0', '>')) {
            $version = 'Magento ' . Mage::getEdition() . ' ' . Mage::getVersion(
            ) . 'V';
        } else {
            $version = 'Magento version : ' . Mage::getVersion() . 'V';
        }

        //@codingStandardsIgnoreStart
        $fh  = @fopen('/proc/meminfo', 'r');
        $mem = 0;
        if ($fh) {
            while ($line = fgets($fh)) {
                $pieces = array();
                if (preg_match('^MemTotal:\s+(\d+)\skB$^', $line, $pieces)) {
                    $mem = $pieces[1];
                    break;
                }
            }

            fclose($fh);
        }
        //@codingStandardsIgnoreEnd

        if ($mem > 0) {
            $mem = $mem / 1024 . 'M';
        } else {
            $mem = 'Not available';
        }

        //check for php version
        $resultContent->setHowTo('PHP version : V' . PHP_VERSION)
            ->setHowto('PHP Memory : ' . $mem)
            ->setHowto(
                'PHP Max Execution Time : ' . ini_get('max_execution_time')
                . ' sec'
            )
            ->setHowto($version)
            ->setHowto(
                'Connector version : V' . Mage::helper('ddg')->getConnectorVersion()
            );

        return $resultContent;
    }


    /**
     * Check if the mapped program is active.
     *
     * @param $program
     * @param $website
     * @return bool|null
     */
    protected function _getWebisteProgram($program, $website)
    {
        $client = Mage::helper('ddg')->getWebsiteApiClient($website);

        if (! $client || ! $program) {
            return false;
        }

        $data = $client->getProgramById($program);

        if (isset($data->message)) {
            return false;
        }


        return $data;
    }

    /**
     * Get the contacts address book.
     *
     * @param $addressBook
     * @param $webiste
     *
     * @return bool|null
     */
    protected function _getAddressBookContacts($addressBook, $webiste)
    {
        $client = Mage::helper('ddg')->getWebsiteApiClient($webiste);
        //check the client enabled and addressbook mapped
        if (! $client || ! $addressBook) {
            return false;
        }

        $response = $client->getAddressBookById($addressBook);

        if (isset($response->message)) {
            return false;
        }

        return $response;
    }

    /**
     * Get the method name.
     *
     * @param $name
     *
     * @return string
     */
    public function getFormatedMethodName($name)
    {
        //version that not support the lcfirst method
        if (function_exists('lcfirst') === false) {
            $method = strtolower(substr(uc_words($name, ''), 0, 1)) . substr(
                uc_words($name, ''), 1
            );
        } else {
            $method = lcfirst(uc_words($name, ''));
        }

        return $method;
    }

    /**
     * Disabled newsletter success enabled.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function disableNewsletterSuccessEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Disable Newsletter Success : ')
            ->setMessage('Enabled.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $enabled     = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS
            ))
                ? true
                :
                'Disabled';

            if ($enabled !== true) {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage(
                        'Don\'t forget to enable if you want to disable Magento newsletter success email.'
                    )
                    ->setTable(
                        array(
                            'Website'  => $websiteName,
                            'Status'   => $enabled,
                            'Fast Fix' => 'Click  <a href="' . $url
                                . '">here </a>to enable.'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Wishlist sync enabled.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function wishlistEnabled()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Wishlist Sync : ')
            ->setMessage('Enabled.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $wishlist    = ($website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED
            ))
                ? true
                :
                'Disabled';

            if ($wishlist !== true) {
                $url = Mage::helper('adminhtml')->getUrl(
                    '*/connector/enablewebsiteconfiguration',
                    array('path'    => 'XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED',
                          'website' => $website->getId())
                );
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setMessage(
                        'Don\'t forget to enable if you want to sync wishlist.'
                    )
                    ->setTable(
                        array(
                            'Website'  => $websiteName,
                            'Status'   => $wishlist,
                            'Fast Fix' => 'Click  <a href="' . $url
                                . '">here </a>to enable.'
                        )
                    );
            }
        }

        return $resultContent;
    }

    /**
     * Check if any wishlist are imported.
     *
     * @return Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
     */
    public function wishlistSyncing()
    {
        $resultContent = Mage::getModel(
            'ddg_automation/adminhtml_dashboard_content'
        );
        $resultContent->setStyle(self::CONNECTOR_DASHBOARD_PASSED)
            ->setTitle('Wishlist Syncing : ')
            ->setMessage('Looks Great.');

        foreach (Mage::app()->getWebsites() as $website) {
            $websiteName = $website->getName();
            $storeIds    = $website->getStoreIds();

            if (empty($storeIds)) {
                continue;
            }

            //number of wishlist marked as imported
            $numWishlist = Mage::getModel('ddg_automation/wishlist')
                ->getCollection()
                ->addFieldToFilter('wishlist_imported', 1)
                ->addFieldToFilter('store_id', array('in', $storeIds))
                ->getSize();

            //total wishlist
            $totalWishlist = Mage::getModel('ddg_automation/wishlist')
                ->getCollection()
                ->addFieldToFilter('store_id', array('in', $storeIds))
                ->getSize();

            $tableData = array(
                'Website'        => $websiteName,
                'Total Wishlist' => $totalWishlist,
                'Imported'       => $numWishlist
            );

            $tableData['Status'] = 'Importing';

            if (! $numWishlist) {
                $tableData['Status'] = 'No Imported Wishlist Found.';
                $resultContent->setStyle(self::CONNECTOR_DASHBOARD_FAILED)
                    ->setTitle(
                        'Wishlist Sync (ignore if you have reset wishlist) : '
                    )
                    ->setMessage('');
            }

            $resultContent->setTable($tableData);
        }

        return $resultContent;
    }
}