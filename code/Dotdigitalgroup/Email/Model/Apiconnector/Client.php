<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Client extends Dotdigitalgroup_Email_Model_Abstract_Rest
{
    const APICONNECTOR_VERSION = 'V2';

    const REST_WAIT_UPLOAD_TIME = 5;
    //rest api data
    const REST_ACCOUNT_INFO                     = 'https://apiconnector.com/v2/account-info';
    const REST_CONTACTS                         = 'https://apiconnector.com/v2/contacts/';
    const REST_CONTACTS_IMPORT                  = 'https://apiconnector.com/v2/contacts/import/';
    const REST_ADDRESS_BOOKS                    = 'https://apiconnector.com/v2/address-books/';
    const REST_DATA_FILEDS                      = 'https://apiconnector.com/v2/data-fields';
    const REST_TRANSACTIONAL_DATA_IMPORT        = 'https://apiconnector.com/v2/contacts/transactional-data/import/';
    const REST_TRANSACTIONAL_DATA               = 'https://apiconnector.com/v2/contacts/transactional-data/';
    const REST_CAMPAIGN_SEND                    = 'https://apiconnector.com/v2/campaigns/send';
    const REST_CONTACTS_SUPPRESSED_SINCE        = 'https://apiconnector.com/v2/contacts/suppressed-since/';
    const REST_DATA_FIELDS_CAMPAIGNS            = 'https://apiconnector.com/v2/campaigns';
    const REST_SMS_MESSAGE_SEND_TO              = 'https://apiconnector.com/v2/sms-messages/send-to/';
    const REST_CONTACTS_RESUBSCRIBE             = 'https://apiconnector.com/v2/contacts/resubscribe';
    const REST_CAMPAIGN_FROM_ADDRESS_LIST       = 'https://apiconnector.com/v2/custom-from-addresses';
    const REST_CREATE_CAMPAIGN                  = 'https://apiconnector.com/v2/campaigns';
    const REST_PROGRAM                          = 'https://apiconnector.com/v2/programs/';
    const REST_PROGRAM_ENROLMENTS               = 'https://apiconnector.com/v2/programs/enrolments';
    const REST_TEMPLATES                        = 'https://apiconnector.com/v2/templates';

    //rest error responces
    const REST_CONTACT_NOT_FOUND                = 'Error: ERROR_CONTACT_NOT_FOUND';
    const REST_SEND_MULTI_TRANSACTIONAL_DATA    = 'Error: ERROR_FEATURENOTACTIVE';
    const REST_STATUS_IMPORT_REPORT_NOT_FOUND   = 'Import is not processed yet or completed with error. ERROR_IMPORT_REPORT_NOT_FOUND';
    const REST_STATUS_REPORT_NOTFINISHED        = 'NotFinished';
    const REST_TRANSACTIONAL_DATA_NOT_EXISTS    = 'Error: ERROR_TRANSACTIONAL_DATA_DOES_NOT_EXIST';
    const REST_API_USAGE_EXCEEDED               = 'Your account has generated excess API activity and is being temporarily capped. Please contact support. ERROR_APIUSAGE_EXCEEDED';
    const REST_API_EMAIL_NOT_VALID              = 'Email is not a valid email address. ERROR_PARAMETER_INVALID';
    const REST_API_DATAFILEDS_EXISTS            = 'Field already exists. ERROR_NON_UNIQUE_DATAFIELD';
    const REST_API_AUTHORIZATION_DENIED         = 'Authorization has been denied for this request.';
    const REST_API_TRANSACTIONAL_DATA_ALLOWANCE = 'TransactionalDataAllowanceInMegabytes';

    protected $_customers_file_slug   = 'customer_sync';
    protected $_subscribers_file_slug = 'subscriber_sync';
    protected $_api_helper;
    protected $_subscribers_address_book_id;
    protected $_customers_address_book_id;
    protected $_filename;
    protected $_subscribers_filename;
    protected $_customers_filename;
    protected $_limit = 10;
    protected $_address_book_id;
    public $fileHelper;    /** @var  Dotdigitalgroup_Email_Helper_File */
    public $result = array('error' => false, 'message' => '');


    public $apiCalls = array(
        'getContactById' => 'get_contact_by_id',
        'postAddressBookContactsImport' => 'post_address_book_contacts_import',
        'postAddressBookContacts' => 'post_address_book_contacts',
        'deleteAddressBookContact' => 'delete_address_book_contact',
        'getContactsImportReport' => 'get_contacts_import',
        'getContactByEmail' => 'get_contact_by_email',
        'getAddressBooks' => 'get_address_books',
        'postAddressBooks' => 'post_address_books',
	    'getAddressBookById' => 'get_address_book_by_id',
        'getCampaigns' => 'get_campaigns',
        'postDataFields' => 'post_data_fields',
        'deleteDataField' => 'delete_data_field',
        'getDataFields' => 'get_data_fields',
        'updateContact' => 'update_contact',
        'deleteContact' => 'delete_contact',
        'updateContactDatafieldsByEmail' => 'update_contact_datafields_by_email',
        'postCampaignsSend' => 'post_campaigns_send',
        'postContacts' => 'post_contacts',
        'getContactsSuppressedSinceDate' => 'get_contacts_suppressed_sinse_date',
        'postContactsTransactionalDataImport' => 'post_contacts_transactional_data_import',
        'postContactsTransactionalData' => 'post_contacts_transactional_data',
        'getContactsTransactionalDataByKey' => 'get_contacts_transactional_data_by_key',
        'deleteContactTransactionalData' => 'delete_contact_transactional_data',
        'getAccountInfo' => 'get_account_info',
        'postSmsMessagesSendTo' => 'post_sms_message_send_to',
        'deleteAddressBookContactsInbulk' => 'delete_addess_book_contacts_in_bulk',
        'postContactsResubscribe' => 'post_contacts_resubscribe',
        'getCustomFromAddresses' => 'get_custom_fromaddresses',
        'postCampaign' => 'post_campaign',
        'getPrograms' => 'get_programs',
        'postProgramsEnrolments' => 'post_programs_enrolments',
        'getProgramById' => 'get_program_by_id',
        'getCampaignSummary' => 'get_campaign_summary',
        'deleteContactsTransactionalData' => 'delete_contacts_transactional_data',
        'getContactAddressBooks' => 'get_contact_addressBooks',
        'getApiTemplateList'    => 'get_api_template_list',
        'getApiTemplate'   =>  'get_api_template'
    );



	/**
	 * constructor.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Excluded api response that we don't want to send.
	 * @var array
	 */
	public $exludeMessages = array(
		'ERROR_PROGRAM_NOT_ACTIVE', 'ERROR_CONTACT_SUPPRESSED', 'ERROR_NON_UNIQUE_DATAFIELD', 'Authorization has been denied for this request.',
		'ERROR_ENROLMENT_ALLOWANCE_EXCEEDED', 'ERROR_TRANSACTIONAL_DATA_STORAGE_ALLOWANCE_EXCEEDED', 'ERROR_CAMPAIGN_SENDNOTPERMITTED', 'ERROR_TRANSACTIONAL_DATA_DOES_NOT_EXIST',
		'ERROR_ADDRESSBOOK_NOT_FOUND', 'ERROR_INVALID_LOGIN'
	);

    /**
     * @param $apiUsername
     * @param $apiPassword
     * @return bool|mixed
     */
    public function validate($apiUsername, $apiPassword)
    {
        if ($apiUsername && $apiPassword) {
            $this->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
            $accountInfo = $this->getAccountInfo();
            if (isset($accountInfo->message)) {
                Mage::getSingleton('adminhtml/session')->addError($accountInfo->message);
                $message = 'VALIDATION ERROR :  ' . $accountInfo->message;
                Mage::helper('ddg')->log($message);
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
                return false;
            }
            return $accountInfo;
        }
        return false;
    }
    /**
     * Gets a contact by ID. Unsubscribed or suppressed contacts will not be retrieved.
     * @param $id
     * @return null
     */
    public function getContactById($id)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS . $id;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();

        if(isset($response->message)) {
            $message = 'GET CONTACT INFO ID ' . $url . ', ' . $response->message;
            Mage::helper( 'ddg' )->log( $message );
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Bulk creates, or bulk updates, contacts. Import format can either be CSV or Excel.
     * Must include one column called "Email". Any other columns will attempt to map to your custom data fields.
     * The ID of returned object can be used to query import progress.
     * @param $filename
     * @param $addressBookId
     * @return mixed
     */

    public function postAddressBookContactsImport($filename, $addressBookId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);
        $url = "https://apiconnector.com/v2/address-books/{$addressBookId}/contacts/import";
        $helper = Mage::helper('ddg');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_USERPWD, $this->getApiUsername() . ':' . $this->getApiPassword());

	    //case the deprication of @filename for uploading
	    if (function_exists('curl_file_create')) {
		    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
		    $args['file'] = curl_file_create(Mage::helper('ddg/file')->getFilePath($filename), 'text/csv');
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

	    } else {
		    //standart use of curl file
		    curl_setopt($ch, CURLOPT_POSTFIELDS, array (
			    'file' => '@'.Mage::helper('ddg/file')->getFilePath($filename)
		    ));
	    }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: multipart/form-data')
        );

        // send contacts to address book
        $result = curl_exec($ch);
        $result = json_decode($result);
        if (isset($result->message)) {
            $message = 'POST ADDRESS BOOK ' . $addressBookId . ', CONTACT IMPORT : ' . ' filename '  . $filename .  ' Username ' . $this->getApiUsername() . $result->message;
            $helper->log($message);
            Mage::helper('ddg')->log($result);
            Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $result;
    }

    /**
     * Adds a contact to a given address book.
     * @param $addressBookId
     * @param $apiContact
     * @return mixed|null
     */
    public function postAddressBookContacts($addressBookId, $apiContact)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_ADDRESS_BOOKS . $addressBookId . '/contacts';
        $this->setUrl($url)
            ->setVerb("POST")
            ->buildPostBody($apiContact);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'POST ADDRESS BOOK CONTACTS ' . $url . ', ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Deletes all contacts from a given address book.
     * @param $addressBookId
     * @param $contactId
     * @return null
     */
    public function deleteAddressBookContact($addressBookId, $contactId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_ADDRESS_BOOKS . $addressBookId . '/contacts/' . $contactId;
        $this->setUrl($url)
            ->setVerb('DELETE');
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'DELETE ADDRESS BOOK CONTACT ' . $url . ', ' . $response->message;
            Mage::helper( 'ddg' )->log( $message );
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Gets a report with statistics about what was successfully imported, and what was unable to be imported.
     * @param $importId
     * @return mixed
     */
    public function getContactsImportReport($importId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS_IMPORT . $importId . "/report";
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET CONTACTS IMPORT REPORT  . ' . $url . ' message : ' . $response->message;
            Mage::helper( 'ddg' )->log( $message );
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper( 'ddg' )->rayLog( '100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }

    /**
     * Gets a contact by email address.
     * @param $email
     * @return mixed
     */
    public function getContactByEmail($email)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS . $email;
        $this->setUrl($url)
            ->setVerb('GET');

        //should create new one if not exists?!?
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET CONTACT BY email : ' . $email . ' ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Get all address books.
     * @return null
     */
    public function getAddressBooks()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_ADDRESS_BOOKS;
        $this->setUrl($url)
            ->setVerb("GET");

        $response = $this->execute();
        if (isset($response->message)) {
            $message  = 'GET ALL ADDRESS BOOKS : '  . $url . ', ' .  $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }

	/**
	 * Gets an address book by ID.
	 * @param $id
	 *
	 * @return null
	 * @throws Exception
	 */
	public function getAddressBookById($id)
	{
		$this->_addApiCall($this->apiCalls[__FUNCTION__]);
		$url = self::REST_ADDRESS_BOOKS . $id;

		$this->setUrl($url)
			->setVerb('GET');

		$response = $this->execute();

		if (isset($response->message)) {
			$message = 'GET ADDRESS BOOK BY ID '. $id . ', ' . $response->message;
			Mage::helper('ddg')->log($message);
		}

		return $response;
	}

    /**
     *  Creates an address book.
     * @param $name
     * @return null
     */
    public function postAddressBooks($name, $visibility = 'Public')
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $data = array(
            'Name' => $name,
            'Visibility' => $visibility
        );
        $url = self::REST_ADDRESS_BOOKS;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'Postaddressbooks ' . $response->message . ', url :' . $url ;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
		        Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }

    /**
     * Get list of all campaigns.
     * @return mixed
     */
    public function getCampaigns()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_DATA_FIELDS_CAMPAIGNS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'GET CAMPAIGNS ' . $response->message . ' api user : '  . $this->getApiUsername();
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Creates a data field within the account.
     * @param $data  string/array
     * @param string $type string, numeric, date, boolean
     * @param string $visibility public, private
     * @param bool $defaultValue
     * @return mixed
     */
    public function postDataFields($data, $type = 'String', $visibility = 'Public', $defaultValue = false)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_DATA_FILEDS;
	    //set default value for the numeric datatype
        if($type == 'numeric' && !$defaultValue)
            $defaultValue = 0;
	    //set data for the string datatype
        if (is_string($data)) {
            $data = array(
                'Name' => $data,
                'Type' => $type,
                'Visibility' => $visibility
            );
	        //default value
            if($defaultValue)
                $data['DefaultValue'] = $defaultValue;
        }
        $this->setUrl($url)
            ->buildPostBody($data)
            ->setVerb('POST');

        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'POST CREATE DATAFIELDS ' . $response->message;
            Mage::helper('ddg')->log($message);
            Mage::helper('ddg')->log($data);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Deletes a data field within the account.
     * @param $name
     *
     * @return mixed
     */
    public function deleteDataField($name)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_DATA_FILEDS . '/' . $name;
        $request = Mage::helper('ddg/api_restrequest');
        $request->setUrl($url)
            ->setVerb('DELETE');

        $response = $request->execute();
        if (isset($response->message)) {
            $message = 'DELETE DATA FIELD :' . $name . ' '  . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $request->execute();
    }

    /**
     * Lists the data fields within the account.
     * @return mixed
     */
    public function getDataFields()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_DATA_FILEDS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET ALL DATAFIELDS ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Updates a contact.
     * @param $contactId
     * @param $data
     * @return object
     */
    public function updateContact($contactId, $data)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS . $contactId;
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'ERROR : UPDATE SINGLE CONTACT : ' . $url . ' message : ' . $response->message;
            Mage::helper('ddg')->log($message);
            Mage::helper('ddg')->log($data);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Deletes a contact.
     * @param $contactId
     * @return null
     */
    public function deleteContact($contactId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS . $contactId;
        $this->setUrl($url)
            ->setVerb('DELETE');

        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'DELETE CONTACT : ' . $url . ', ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }

    /**
     * Update contact datafields by email.
     * @param $email
     * @param $dataFields
     *
     * @return null
     * @throws Exception
     */
    public function updateContactDatafieldsByEmail($email, $dataFields)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $apiContact = $this->postContacts($email);
	    //do not create for non contact id set
	    if (! isset($apiContact->id)) {
		    return $apiContact;
	    } else {
		    //get the contact id for this email
		    $contactId = $apiContact->id;
	    }
	    $data = array(
            'Email' => $email,
            'EmailType' => 'Html');
        $data['DataFields'] = $dataFields;
        $url = self::REST_CONTACTS . $contactId;
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'ERROR: UPDATE CONTACT DATAFIELD ' . $url . ' message : ' . $response->message;
            Mage::helper('ddg')->log($message);
            Mage::helper('ddg')->log($data);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Sends a specified campaign to one or more address books, segments or contacts at a specified time.
     * Leave the address book array empty to send to All Contacts.
     * @param $campaignId
     * @param $contacts
     * @return mixed
     */
    public function postCampaignsSend($campaignId, $contacts)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $helper = Mage::helper('ddg');
        $data = array(
            'username' => $this->getApiUsername(),
            'password' => $this->getApiPassword(),
            "campaignId" => $campaignId,
            "ContactIds" => $contacts
        );
        $this->setUrl(self::REST_CAMPAIGN_SEND)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'SENDING CAMPAIGN ' .  $response->message;
            $helper->log($message);
            $helper->log($data);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Creates a contact.
     * @param $email
     * @return mixed
     */
    public function postContacts($email)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS;
        $data = array(
            'Email' => $email,
            'EmailType' => 'Html',
        );
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'CREATE A NEW CONTACT : ' . $email . ' , url ' . $url . ', ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * @param $testEmail
     * @param $contactId
     * @param $campaignId
     */
    public function sendIntallInfo($testEmail, $contactId, $campaignId)
    {
        $helper = Mage::helper('ddg');
        $productSize= Mage::getModel('catalog/product')->getCollection()->getSize();
        $customerSize = Mage::getModel('customer/customer')->getCollection()->getSize();

        $data = array(
            'Email' => $testEmail,
            'EmailType' => 'Html',
            'DataFields' => array(
                array(
                    'Key' => 'INSTALLCUSTOMERS',
                    'Value' => (string)$customerSize),
                array(
                    'Key' => 'INSTALLPRODUCTS',
                    'Value' => (string)$productSize),
                array(
                    'Key' => 'INSTALLURL',
                    'Value' => Mage::getBaseUrl('web')),
                array(
                    'Key' => 'INSTALLAPI',
                    'Value' => Mage::helper('ddg')->getStringWebsiteApiAccounts()),
                array(
                    'Key' => 'PHPMEMORY',
                    'Value' => ini_get('memory_limit') . ', Version = ' . $helper->getConnectorVersion()
                )
            )
        );
        $helper->log('SENDING INSTALL INFO DATA...', Zend_Log::INFO, 'api.log');
        $helper->log($data);
        Mage::helper('ddg')->rayLog('100', 'SENDING INSTALL INFO DATA...', 'apiconnector/client.php', __LINE__);
        /**
         * Update data fields for a contact
         */
        $this->updateContact($contactId, $data);
        /**
         * Send Install info campaign
         */
        $this->postCampaignsSend($campaignId, array($contactId));

        return;
    }


    /**
     * Gets a list of suppressed contacts after a given date along with the reason for suppression.
     * @param $dateString
     * @param $select
     * @param $skip
     * @return object
     */
    public function getContactsSuppressedSinceDate($dateString, $select = 1000, $skip = 0)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS_SUPPRESSED_SINCE . $dateString . '?select=' . $select . '&skip=' . $skip;
        $this->setUrl($url)
            ->setVerb("GET");

        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'GET CONTACTS SUPPRESSED SINSE : ' . $dateString . ' select ' . $select . ' skip : ' . $skip . '   response : ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Adds multiple pieces of transactional data to contacts asynchronously, returning an identifier that can be used to check for import progress.
     * @param $collectionName
     * @param $transactionalData
     * @return object
     */
    public function postContactsTransactionalDataImport($transactionalData, $collectionName = 'Orders')
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $orders = array();
        foreach ($transactionalData as $one) {
            if (isset($one->email)) {
                $orders[] = array(
                    'Key' => $one->id,
                    'ContactIdentifier' => $one->email,
                    'Json' => json_encode($one->expose())
                );
            }
        }
        $url = self::REST_TRANSACTIONAL_DATA_IMPORT . $collectionName;
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($orders);

        $response = $this->execute();

        if (isset($response->message)) {
            $message = ' SEND MULTI TRANSACTIONAL DATA ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     *  Adds a single piece of transactional data to a contact.
     *
     * @param $data
     * @param string $name
     * @return null
     */
    public function postContactsTransactionalData($data, $name = 'Orders')
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $getData = $this->getContactsTransactionalDataByKey($name, $data->id);
        if(isset($getData->message) && $getData->message == self::REST_TRANSACTIONAL_DATA_NOT_EXISTS){
            $url  = self::REST_TRANSACTIONAL_DATA . $name;
        }else{
            $url = self::REST_TRANSACTIONAL_DATA . $name . '/' . $getData->key ;
        }
        $apiData = array(
            'Key' => $data->id,
            'Json' => json_encode($data->expose())
        );

        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($apiData);
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'POST CONTACTS TRANSACTIONAL DATA  ' . $response->message;
            Mage::helper('ddg')->log($message);
            Mage::helper('ddg')->log($apiData);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Gets a piece of transactional data by key.
     * @param $name
     * @param $key
     * @return null
     */
    public function getContactsTransactionalDataByKey($name, $key)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_TRANSACTIONAL_DATA . $name . '/' . $key;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET CONTACTS TRANSACTIONAL DATA  name: ' . $name . ' key: ' . $key . ' ' .  $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Deletes all transactional data for a contact.
     * @param $email
     * @param string $collectionName
     * @return object
     */
    public function deleteContactTransactionalData($email, $collectionName = 'Orders')
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url =  'https://apiconnector.com/v2/contacts/' . $email . '/transactional-data/' . $collectionName ;
        $this->setUrl($url)
            ->setVerb('DELETE');
        $response = $this->execute();
        if (isset($response->message)) {

            $message = 'DELETE CONTACT TRANSACTIONAL DATA : ' . $url . ' ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Gets a summary of information about the current status of the account.
     * @return mixed
     */
    public function getAccountInfo()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);
        $url = self::REST_ACCOUNT_INFO;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET ACCOUNT INFO for api user : ' . $this->getApiUsername() . ' ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Send a single SMS message.
     * @param $telephoneNumber
     * @param $message
     * @return object
     */
    public function postSmsMessagesSendTo($telephoneNumber, $message)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $data = array('Message' => $message);
        $url = self::REST_SMS_MESSAGE_SEND_TO . $telephoneNumber;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'POST SMS MESSAGE SEND to ' . $telephoneNumber . ' message: ' . $message . ' error: ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }


    /**
     * Deletes multiple contacts from an address book.
     * @param $addressBookId
     * @param $contactIds
     * @return object
     */
    public function deleteAddressBookContactsInbulk($addressBookId, $contactIds)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = 'https://apiconnector.com/v2/address-books/' . $addressBookId . '/contacts/inbulk';
        $data = array('ContactIds' => array($contactIds[0]));
        $this->setUrl($url)
            ->setVerb('DELETE')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'DELETE BULK ADDRESS BOOK CONTACTS ' . $response->message . ' address book ' . $addressBookId;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }

    /**
     * Resubscribes a previously unsubscribed contact.
     *
     * @param $apiContact
     */
    public function postContactsResubscribe($apiContact)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CONTACTS_RESUBSCRIBE;
        $data = array(
            'UnsubscribedContact' => $apiContact
        );
        $this->setUrl($url)
            ->setVerb("POST")
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'Resubscribe : ' . $url . ', message :' .  $response->message;
            Mage::helper('ddg')->log($message);
            Mage::helper('ddg')->log($data);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
    }

	/**
	 * Gets all custom from addresses which can be used in a campaign.
	 *
	 * @return null
	 * @throws Exception
	 */

    public function getCustomFromAddresses()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CAMPAIGN_FROM_ADDRESS_LIST;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {

            $message = 'GET CampaignFromAddressList ' . $response->message . ' api user : '  . $this->getApiUsername();
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Creates a campaign.
     * @param $data
     *
     * @return null
     * @throws Exception
     */
    public function postCampaign($data)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_CREATE_CAMPAIGN;
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->message)) {
            $message = ' CREATE CAMPAIGN ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Gets all programs.
     * https://apiconnector.com/v2/programs?select={select}&skip={skip}
     */
    public function getPrograms()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_PROGRAM;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'Get programmes : ' . $response->message ;
            Mage::helper( 'ddg' )->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Creates an enrolment.
     * @param $data
     *
     * @return null
     * @throws Exception
     */
    public function postProgramsEnrolments($data)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = self::REST_PROGRAM_ENROLMENTS;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'Post programs enrolments : ' . $response->message;
            Mage::helper('ddg')->log($message);
            Mage::helper('ddg')->log($data);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Gets a program by id.
     * @param $id
     *
     * @return null
     * @throws Exception
     */
    public function getProgramById( $id )
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url =  self::REST_PROGRAM . $id;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'Get program by id  ' . $id . ', ' . $response->message;
            Mage::helper('ddg')->log($message);
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper("ddg")->rayLog(100, $message, 'apiconnector/client.php', __LINE__);

        }

        return $response;
    }

    /**
     * Gets a summary of reporting information for a specified campaign.
     * @param $campaignId
     *
     * @return null
     * @throws Exception
     */
    public function getCampaignSummary($campaignId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url = 'https://apiconnector.com/v2/campaigns/' . $campaignId . '/summary';

        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'Get Campaign Summary ' . $response->message . '  ,url : ' . $url;
            Mage::helper('ddg')->log( $message );
	        if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog(100, $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    private function _addApiCall($path, $scope = 'default'){
	    //log all api calls
	    if (Mage::getStoreConfigFlag(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DEBUG_API_CALLS))
		    Mage::helper('ddg')->log('Api call was made with path : ' . $path);

        $configModel = Mage::getModel('ddg_automation/config');
        $configLastRun = $configModel->getHourTrigger();

        try {

            // check for last hour reset
            if ($configLastRun) {

                //last run found let's check the difference
                $configLastRun  = Mage::app()->getLocale()->date($configLastRun);
                $now            = Mage::app()->getLocale()->date();

                // reset the time and calls for the last hour or older by date
                if ($now->compareHour($configLastRun) == 1 || $now->compareDay($configLastRun) == 1){

                    // update the hour trigger
                    $hourTriggerModel = Mage::getModel('ddg_automation/config')->getCollection()
                        ->addFieldToFilter('path', Dotdigitalgroup_Email_Helper_Config::CONNECTOR_EMAIL_CONFIG_HOUR_TRIGGER)
                        ->getFirstItem();
                    //reset trigger
                    $hourTriggerModel->setValue($now->toString(Zend_Date::ISO_8601))
                        ->setScope($scope)
                        ->save();

                    //reseting the api calls
                    $this->_resetConfigApiCallsForLastHour();
                } else {

                    // increment the number for api call
                    $apiCount = $configModel->getValueByPath($path);
                    $value = $apiCount->getValue();
                    $apiCount->setScope($scope)
                        ->setIsApi('1')
                        ->setValue(++$value)
                        ->save();
                }

            } else {
                // save the current date
                $date = Mage::getModel('core/date')->date(Zend_Date::ISO_8601);
                $configModel->setPath(Dotdigitalgroup_Email_Helper_Config::CONNECTOR_EMAIL_CONFIG_HOUR_TRIGGER)
                    ->setScope($scope)
                    ->setValue($date)
                    ->save();
            }
        }catch (Exception $e){
            Mage::logException($e);
        }

    }

    private function _resetConfigApiCallsForLastHour() {
        foreach ( $this->apiCalls as $key => $path ) {
            $config = Mage::getModel('ddg_automation/config')->getCollection()
                ->addFieldToFilter('is_api', true)
                ->addFieldToFilter('path', $path)
                ->getFirstItem()
            ;

            $config->setPath($path)
                ->setValue(0)
                ->save();
        }
    }

    /**
     * Deletes a piece of transactional data by key.
     * @param $key
     * @param string $collectionName
     * @return object
     */
    public function deleteContactsTransactionalData($key, $collectionName = 'Orders')
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url =  'https://apiconnector.com/v2/contacts/transactional-data/' . $collectionName  .'/' . $key ;
        $this->setUrl($url)
            ->setVerb('DELETE');
        $response = $this->execute();
        if (isset($response->message))
            Mage::helper('ddg')->log('DELETE CONTACTS TRANSACTIONAL DATA : ' . $url . ' ' . $response->message);

        return $response;
    }

	/**
	 * Adds a document to a campaign as an attachment.
	 * @param $campaignId
	 * @param $data
	 *
	 * @return object
	 * @throws Exception
	 */
    public function postCampaignAttachments($campaignId, $data)
    {
        $url = self::REST_CREATE_CAMPAIGN . "/$campaignId/attachments";
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($data);
        $result = $this->execute();
        if (isset($result->message)) {
            Mage::helper('ddg')->log(' CAMPAIGN ATTACHMENT ' . $result->message);
        }
        return $result;
    }


	public function getNostoProducts($slotName, $email)
	{
		$recommended = Dotdigitalgroup_Email_Helper_Config::API_ENDPOINT . '/recommendations/email';
		$token = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_NOSTO);

		//check for strin length
		if (strlen($slotName) > 1 && strlen($email) > 1) {

			$recommended .= '?elements=' . $slotName;
			$recommended .= '&emails=' . $email;
		}

		$this->setApiUsername('')
			->setApiPassword($token)
			->setUrl($recommended)
			->setVerb('GET');

		$result = $this->execute();

		if (isset($result->message)) {
			$message = $result->message;
			Mage::helper('ddg')->log($message);
			Mage::helper('ddg')->log("Nosto recommendation slot name : $slotName , email : $email");
		}

		return $result;
	}

    /**
     * get contact address books
     *
     * @param $contactId
     * @return object
     * @throws Exception
     */
    public function getContactAddressBooks($contactId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url =  'https://apiconnector.com/v2/contacts/' . $contactId . '/address-books' ;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET CONTACTS ADDRESS BOOKS contact: ' . $contactId .  $response->message;
            Mage::helper('ddg')->log($message);
            if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }

        return $response;
    }

    /**
     * Gets list of all templates.
     *
     * @return object
     * @throws Exception
     */
    public function getApiTemplateList()
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url =  self::REST_TEMPLATES;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET API CONTACT LIST ' .  $response->message;
            Mage::helper('ddg')->log($message);
            if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }

    /**
     * Gets a template by ID.
     *
     * @param $templateId
     * @return object
     * @throws Exception
     */
    public function getApiTemplate($templateId)
    {
        $this->_addApiCall($this->apiCalls[__FUNCTION__]);

        $url =  self::REST_TEMPLATES . '/' . $templateId;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        if (isset($response->message)) {
            $message = 'GET API CONTACT LIST ' .  $response->message;
            Mage::helper('ddg')->log($message);
            if (! in_array($response->message, $this->exludeMessages))
                Mage::helper('ddg')->rayLog('100', $message, 'apiconnector/client.php', __LINE__);
        }
        return $response;
    }
}
