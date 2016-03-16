<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Client
    extends Dotdigitalgroup_Email_Model_Abstract_Rest
{

    const APICONNECTOR_VERSION = 'V2';

    const REST_WAIT_UPLOAD_TIME = 5;
    //rest api data
    const REST_ACCOUNT_INFO = 'https://r1-api.dotmailer.com/v2/account-info';
    const REST_CONTACTS = '/v2/contacts/';
    const REST_CONTACTS_IMPORT = '/v2/contacts/import/';
    const REST_ADDRESS_BOOKS = '/v2/address-books/';
    const REST_DATA_FILEDS = '/v2/data-fields';
    const REST_TRANSACTIONAL_DATA_IMPORT = '/v2/contacts/transactional-data/import/';
    const REST_TRANSACTIONAL_DATA = '/v2/contacts/transactional-data/';
    const REST_CAMPAIGN_SEND = '/v2/campaigns/send';
    const REST_CONTACTS_SUPPRESSED_SINCE = '/v2/contacts/suppressed-since/';
    const REST_DATA_FIELDS_CAMPAIGNS = '/v2/campaigns';
    const REST_SMS_MESSAGE_SEND_TO = '/v2/sms-messages/send-to/';
    const REST_CONTACTS_RESUBSCRIBE = '/v2/contacts/resubscribe';
    const REST_CAMPAIGN_FROM_ADDRESS_LIST = '/v2/custom-from-addresses';
    const REST_CREATE_CAMPAIGN = '/v2/campaigns';
    const REST_PROGRAM = '/v2/programs/';
    const REST_PROGRAM_ENROLMENTS = '/v2/programs/enrolments';
    const REST_TEMPLATES = '/v2/templates';

    //rest error responces
    const API_ERROR_API_EXCEEDED = 'Your account has generated excess API activity and is being temporarily capped. Please contact support. ERROR_APIUSAGE_EXCEEDED';
    const API_ERROR_TRANS_ALLOWANCE = 'TransactionalDataAllowanceInMegabytes';
    const API_ERROR_EMAIL_NOT_VALID = 'Email is not a valid email address. ERROR_PARAMETER_INVALID';
    const API_ERROR_FEATURENOTACTIVE = 'Error: ERROR_FEATURENOTACTIVE';
    const API_ERROR_REPORT_NOT_FOUND = 'Import is not processed yet or completed with error. ERROR_IMPORT_REPORT_NOT_FOUND';
    const API_ERROR_TRANS_NOT_EXISTS = 'Error: ERROR_TRANSACTIONAL_DATA_DOES_NOT_EXIST';
    const API_ERROR_DATAFIELD_EXISTS = 'Field already exists. ERROR_NON_UNIQUE_DATAFIELD';
    const API_ERROR_CONTACT_NOT_FOUND = 'Error: ERROR_CONTACT_NOT_FOUND';
    const API_ERROR_PROGRAM_NOT_ACTIVE = 'Error: ERROR_PROGRAM_NOT_ACTIVE';
    const API_ERROR_ENROLMENT_EXCEEDED = 'Error: ERROR_ENROLMENT_ALLOWANCE_EXCEEDED ';
    const API_ERROR_SEND_NOT_PERMITTED = 'Send not permitted at this time. ERROR_CAMPAIGN_SENDNOTPERMITTED';
    const API_ERROR_CONTACT_SUPPRESSED = 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED';
    const API_ERROR_AUTHORIZATION_DENIED = 'Authorization has been denied for this request.';
    const API_ERROR_ADDRESSBOOK_NOT_FOUND = 'Error: ERROR_ADDRESSBOOK_NOT_FOUND';


    protected $_limit = 10;
    public $fileHelper;
    protected $_filename;
    public $result = array('error' => false, 'message' => '');
    protected $_apiEndpoint;


    /**
     * constructor.
     */
    public function __construct()
    {
        $this->_checkApiEndPoint();
        parent::__construct();
    }

    protected function _checkApiEndPoint()
    {
        $apiEndpoint = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::PATH_FOR_API_ENDPOINT
        );
        if ( ! $apiEndpoint) {
            $helper = Mage::helper('ddg');
            $this->setApiUsername($helper->getApiUsername())
                ->setApiPassword($helper->getApiPassword());

            $accountInfo = $this->getAccountInfo();
            //check if returned value is an object
            if (is_object($accountInfo) && ! isset($accountInfo->message)) {
                //save endpoint for account
                foreach ($accountInfo->properties as $property) {
                    if ($property->name == 'ApiEndpoint'
                        && strlen(
                            $property->value
                        )
                    ) {
                        $apiEndpoint = $property->value;
                        $config      = Mage::getConfig();
                        $config->saveConfig(
                            Dotdigitalgroup_Email_Helper_Config::PATH_FOR_API_ENDPOINT,
                            $property->value
                        );
                        $config->cleanCache();
                        break;
                    }
                }
            }

            //check api endpoint again
            if ( ! $apiEndpoint) {
                throw new Exception(
                    'API endpoint cannot be empty. Re-save api credentials to retrieve API endpoint.'
                );
            }
        }

        $this->_apiEndpoint = $apiEndpoint;
    }

    /**
     * Excluded api response that we don't want to send.
     *
     * @var array
     */
    public $exludeMessages
        = array(
            self::API_ERROR_FEATURENOTACTIVE,
            self::API_ERROR_PROGRAM_NOT_ACTIVE,
            self::API_ERROR_CONTACT_SUPPRESSED,
            self::API_ERROR_DATAFIELD_EXISTS,
            self::API_ERROR_AUTHORIZATION_DENIED,
            self::API_ERROR_ENROLMENT_EXCEEDED,
            self::API_ERROR_SEND_NOT_PERMITTED,
            self::API_ERROR_TRANS_NOT_EXISTS,
            self::API_ERROR_ADDRESSBOOK_NOT_FOUND
        );

    /**
     * @param $apiUsername
     * @param $apiPassword
     *
     * @return bool|mixed
     */
    public function validate($apiUsername, $apiPassword)
    {
        if ($apiUsername && $apiPassword) {
            $this->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
            $accountInfo = $this->getAccountInfo();

            if (isset($accountInfo->message)) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $accountInfo->message
                );
                $message = 'VALIDATION ERROR :  ' . $accountInfo->message;
                Mage::helper('ddg')->log($message);

                return false;
            }

            return $accountInfo;
        }

        return false;
    }

    /**
     * Gets a contact by ID. Unsubscribed or suppressed contacts will not be retrieved.
     *
     * @param $id
     *
     * @return null
     */
    public function getContactById($id)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS . $id;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'GET CONTACT INFO ID ' . $url . ', '
                . $response->message;
            Mage::helper('ddg')->log($message);
        }

        return $response;
    }

    /**
     * Bulk creates, or bulk updates, contacts. Import format can either be CSV or Excel.
     * Must include one column called "Email". Any other columns will attempt to map to your custom data fields.
     * The ID of returned object can be used to query import progress.
     *
     * @param $filename
     * @param $addressBookId
     *
     * @return mixed
     */

    public function postAddressBookContactsImport($filename, $addressBookId)
    {
        $url    = $this->_apiEndpoint
            . "/v2/address-books/{$addressBookId}/contacts/import";
        $helper = Mage::helper('ddg');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt(
            $ch, CURLOPT_USERPWD,
            $this->getApiUsername() . ':' . $this->getApiPassword()
        );

        //case the deprication of @filename for uploading
        if (function_exists('curl_file_create')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
            $args['file'] = curl_file_create(
                Mage::helper('ddg/file')->getFilePath($filename), 'text/csv'
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

        } else {
            //standart use of curl file
            curl_setopt(
                $ch, CURLOPT_POSTFIELDS, array(
                    'file' => '@' . Mage::helper('ddg/file')->getFilePath(
                        $filename
                    )
                )
            );
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: multipart/form-data')
        );

        // send contacts to address book
        $result = curl_exec($ch);

        //if curl error found
        if (curl_errno($ch)) {
            //save the error
            $this->curlError = curl_error($ch);
        }

        $result = json_decode($result);
        if (isset($result->message)) {
            $message = 'POST ADDRESS BOOK ' . $addressBookId
                . ', CONTACT IMPORT : ' . ' filename ' . $filename
                . ' Username ' . $this->getApiUsername() . $result->message;
            $helper->log($message);
            Mage::helper('ddg')->log($result)
                ->rayLog($result->message);
        }

        return $result;
    }

    /**
     * Adds a contact to a given address book.
     *
     * @param $addressBookId
     * @param $apiContact
     *
     * @return mixed|null
     */
    public function postAddressBookContacts($addressBookId, $apiContact)
    {
        $url = $this->_apiEndpoint . self::REST_ADDRESS_BOOKS . $addressBookId
            . '/contacts';
        $this->setUrl($url)
            ->setVerb("POST")
            ->buildPostBody($apiContact);

        $response = $this->execute();

        //log the error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'POST ADDRESS BOOK CONTACTS ' . $url . ', '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Deletes all contacts from a given address book.
     *
     * @param $addressBookId
     * @param $contactId
     *
     * @return null
     */
    public function deleteAddressBookContact($addressBookId, $contactId)
    {

        $url = $this->_apiEndpoint . self::REST_ADDRESS_BOOKS . $addressBookId
            . '/contacts/' . $contactId;
        $this->setUrl($url)
            ->setVerb('DELETE');

        return $this->execute();
    }

    /**
     * Gets a report with statistics about what was successfully imported, and what was unable to be imported.
     *
     * @param $importId
     *
     * @return mixed
     */
    public function getContactsImportReport($importId)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS_IMPORT . $importId
            . "/report";
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CONTACTS IMPORT REPORT  . ' . $url . ' message : '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets a contact by email address.
     *
     * @param $email
     *
     * @return mixed
     */
    public function getContactByEmail($email)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS . $email;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CONTACT BY email : ' . $email . ' '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Get all address books.
     *
     * @return null
     */
    public function getAddressBooks()
    {
        $url = $this->_apiEndpoint . self::REST_ADDRESS_BOOKS;
        $this->setUrl($url)
            ->setVerb("GET");

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET ALL ADDRESS BOOKS : ' . $url . ', '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets an address book by ID.
     *
     * @param $id
     *
     * @return null
     * @throws Exception
     */
    public function getAddressBookById($id)
    {
        $url = $this->_apiEndpoint . self::REST_ADDRESS_BOOKS . $id;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            Mage::helper('ddg')->log(
                'GET ADDRESS BOOK BY ID ' . $id . ', ' . $response->message
            );
        }

        return $response;
    }

    /**
     *  Creates an address book.
     *
     * @param $name
     *
     * @return null
     */
    public function postAddressBooks($name, $visibility = 'Public')
    {
        $data = array(
            'Name'       => $name,
            'Visibility' => $visibility
        );
        $url  = $this->_apiEndpoint . self::REST_ADDRESS_BOOKS;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'Postaddressbooks ' . $response->message . ', url :'
                . $url;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Get list of all campaigns.
     *
     * @return mixed
     */
    public function getCampaigns()
    {
        $url = $this->_apiEndpoint . self::REST_DATA_FIELDS_CAMPAIGNS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CAMPAIGNS ' . $response->message . ' api user : '
                . $this->getApiUsername();
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Creates a data field within the account.
     *
     * @param        $data       string/array
     * @param string $type       string, numeric, date, boolean
     * @param string $visibility public, private
     * @param bool   $defaultValue
     *
     * @return mixed
     */
    public function postDataFields($data, $type = 'String',
        $visibility = 'public', $defaultValue = false
    ) 
    {
        $url = $this->_apiEndpoint . self::REST_DATA_FILEDS;
        //set default value for the numeric datatype
        if ($type == 'numeric' && ! $defaultValue) {
            $defaultValue = 0;
        }
        //set data for the string datatype
        if (is_string($data)) {
            $data = array(
                'Name'       => $data,
                'Type'       => $type,
                'Visibility' => $visibility
            );
            //default value
            if ($defaultValue) {
                $data['DefaultValue'] = $defaultValue;
            }
        }
        $this->setUrl($url)
            ->buildPostBody($data)
            ->setVerb('POST');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'POST CREATE DATAFIELDS ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($data)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Deletes a data field within the account.
     *
     * @param $name
     *
     * @return mixed
     */
    public function deleteDataField($name)
    {
        $url = $this->_apiEndpoint . self::REST_DATA_FILEDS . '/' . $name;
        $this->setUrl($url)
            ->setVerb('DELETE');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'DELETE DATA FIELD :' . $name . ' ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Lists the data fields within the account.
     *
     * @return mixed
     */
    public function getDataFields()
    {
        $url = $this->_apiEndpoint . self::REST_DATA_FILEDS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET ALL DATAFIELDS ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Updates a contact.
     *
     * @param $contactId
     * @param $data
     *
     * @return object
     */
    public function updateContact($contactId, $data)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS . $contactId;
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'ERROR : UPDATE SINGLE CONTACT : ' . $url . ' message : '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($data)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Deletes a contact.
     *
     * @param $contactId
     *
     * @return null
     */
    public function deleteContact($contactId)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS . $contactId;
        $this->setUrl($url)
            ->setVerb('DELETE');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'DELETE CONTACT : ' . $url . ', ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Update contact datafields by email.
     *
     * @param $email
     * @param $dataFields
     *
     * @return null
     * @throws Exception
     */
    public function updateContactDatafieldsByEmail($email, $dataFields)
    {
        $apiContact = $this->postContacts($email);
        //do not create for non contact id set
        if ( ! isset($apiContact->id)) {
            return $apiContact;
        } else {
            //get the contact id for this email
            $contactId = $apiContact->id;
        }
        $data               = array(
            'Email'     => $email,
            'EmailType' => 'Html');
        $data['DataFields'] = $dataFields;
        $url                = $this->_apiEndpoint . self::REST_CONTACTS
            . $contactId;
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'ERROR: UPDATE CONTACT DATAFIELD ' . $url . ' message : '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($data)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Sends a specified campaign to one or more address books, segments or contacts at a specified time.
     * Leave the address book array empty to send to All Contacts.
     *
     * @param $campaignId
     * @param $contacts
     *
     * @return mixed
     */
    public function postCampaignsSend($campaignId, $contacts)
    {
        $data = array(
            'username'   => $this->getApiUsername(),
            'password'   => $this->getApiPassword(),
            "campaignId" => $campaignId,
            "ContactIds" => $contacts
        );
        $this->setUrl($this->_apiEndpoint . self::REST_CAMPAIGN_SEND)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'SENDING CAMPAIGN ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($data)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Creates a contact.
     *
     * @param $email
     *
     * @return mixed
     */
    public function postContacts($email)
    {
        //validate email before creating a contact
        $valid = preg_match(
            "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",
            $email
        );
        if ( ! $valid) {
            $object = new stdClass();

            return $object->message = 'Not valid email :' . $email;
        }

        $url  = $this->_apiEndpoint . self::REST_CONTACTS;
        $data = array(
            'Email'     => $email,
            'EmailType' => 'Html',
        );
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'CREATE A NEW CONTACT : ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }


    /**
     * Gets a list of suppressed contacts after a given date along with the reason for suppression.
     *
     * @param $dateString
     * @param $select
     * @param $skip
     *
     * @return object
     */
    public function getContactsSuppressedSinceDate($dateString, $select = 1000,
        $skip = 0
    ) 
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS_SUPPRESSED_SINCE
            . $dateString . '?select=' . $select . '&skip=' . $skip;
        $this->setUrl($url)
            ->setVerb("GET");

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CONTACTS SUPPRESSED SINSE : ' . $dateString
                . ' select ' . $select . ' skip : ' . $skip . '   response : '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Adds multiple pieces of transactional data to contacts asynchronously, returning an identifier that can be used to check for import progress.
     *
     * @param $collectionName
     * @param $transactionalData
     *
     * @return object
     */
    public function postContactsTransactionalDataImport($transactionalData,
        $collectionName = 'Orders'
    ) 
    {
        $orders = array();
        foreach ($transactionalData as $one) {
            if (isset($one->email)) {
                $orders[] = array(
                    'Key'               => $one->id,
                    'ContactIdentifier' => $one->email,
                    'Json'              => json_encode($one)
                );
            }
        }
        $url = $this->_apiEndpoint . self::REST_TRANSACTIONAL_DATA_IMPORT
            . $collectionName;
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($orders);

        $response = $this->execute();
        // log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = ' SEND MULTI TRANSACTIONAL DATA ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     *  Adds a single piece of transactional data to a contact.
     *
     * @param        $data
     * @param string $collectionName
     *
     * @return null
     * @throws Exception
     */
    public function postContactsTransactionalData($data,
        $collectionName = 'Orders'
    ) 
    {
        $order = $this->getContactsTransactionalDataByKey(
            $collectionName, $data->id
        );
        if (isset($order->message)
            && $order->message == self::API_ERROR_TRANS_NOT_EXISTS
        ) {
            $url = $this->_apiEndpoint . self::REST_TRANSACTIONAL_DATA
                . $collectionName;
        } else {
            $url = $this->_apiEndpoint . self::REST_TRANSACTIONAL_DATA
                . $collectionName . '/' . $order->key;
        }
        $apiData = array(
            'Key'               => $data->id,
            'ContactIdentifier' => $data->email,
            'Json'              => json_encode($data->expose())
        );

        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($apiData);
        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'POST CONTACTS TRANSACTIONAL DATA  '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($apiData)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets a piece of transactional data by key.
     *
     * @param $name
     * @param $key
     *
     * @return null
     */
    public function getContactsTransactionalDataByKey($name, $key)
    {
        $url = $this->_apiEndpoint . self::REST_TRANSACTIONAL_DATA . $name . '/'
            . $key;
        $this->setUrl($url)
            ->setVerb('GET');

        return $this->execute();
    }

    /**
     * Deletes all transactional data for a contact.
     *
     * @param        $email
     * @param string $collectionName
     *
     * @return object
     */
    public function deleteContactTransactionalData($email,
        $collectionName = 'Orders'
    ) 
    {
        $url = $this->_apiEndpoint . '/v2/contacts/' . $email
            . '/transactional-data/' . $collectionName;
        $this->setUrl($url)
            ->setVerb('DELETE');

        return $this->execute();
    }

    /**
     * Gets a summary of information about the current status of the account.
     *
     * @return mixed
     */
    public function getAccountInfo()
    {
        $url = self::REST_ACCOUNT_INFO;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET ACCOUNT INFO for api user : '
                . $this->getApiUsername() . ' ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Send a single SMS message.
     *
     * @param $telephoneNumber
     * @param $message
     *
     * @return object
     */
    public function postSmsMessagesSendTo($telephoneNumber, $message)
    {
        $data = array('Message' => $message);
        $url  = $this->_apiEndpoint . self::REST_SMS_MESSAGE_SEND_TO
            . $telephoneNumber;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'POST SMS MESSAGE SEND to ' . $telephoneNumber
                . ' message: ' . $message . ' error: ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }


    /**
     * Deletes multiple contacts from an address book.
     *
     * @param $addressBookId
     * @param $contactIds
     *
     * @return object
     */
    public function deleteAddressBookContactsInbulk($addressBookId, $contactIds)
    {
        $url  = $this->_apiEndpoint . '/v2/address-books/' . $addressBookId
            . '/contacts/inbulk';
        $data = array('ContactIds' => array($contactIds[0]));
        $this->setUrl($url)
            ->setVerb('DELETE')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'DELETE BULK ADDRESS BOOK CONTACTS ' . $response->message
                . ' address book ' . $addressBookId;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Resubscribes a previously unsubscribed contact.
     *
     * @param $apiContact
     *
     * @return null
     * @throws Exception
     */
    public function postContactsResubscribe($apiContact)
    {
        $url  = $this->_apiEndpoint . self::REST_CONTACTS_RESUBSCRIBE;
        $data = array(
            'UnsubscribedContact' => $apiContact
        );
        $this->setUrl($url)
            ->setVerb("POST")
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'Resubscribe : ' . $url . ', message :'
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($data)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets all custom from addresses which can be used in a campaign.
     *
     * @return null
     * @throws Exception
     */

    public function getCustomFromAddresses()
    {
        $url = $this->_apiEndpoint . self::REST_CAMPAIGN_FROM_ADDRESS_LIST;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CampaignFromAddressList ' . $response->message
                . ' api user : ' . $this->getApiUsername();
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Creates a campaign.
     *
     * @param $data
     *
     * @return null
     * @throws Exception
     */
    public function postCampaign($data)
    {
        $url = $this->_apiEndpoint . self::REST_CREATE_CAMPAIGN;
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = ' CREATE CAMPAIGN ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets all programs.
     * https://apiconnector.com/v2/programs?select={select}&skip={skip}
     */
    public function getPrograms()
    {
        $url = $this->_apiEndpoint . self::REST_PROGRAM;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'Get programmes : ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Creates an enrolment.
     *
     * @param $data
     *
     * @return null
     * @throws Exception
     */
    public function postProgramsEnrolments($data)
    {
        $url = $this->_apiEndpoint . self::REST_PROGRAM_ENROLMENTS;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'Post programs enrolments : ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->log($data)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets a program by id.
     *
     * @param $id
     *
     * @return null
     * @throws Exception
     */
    public function getProgramById($id)
    {
        $url = $this->_apiEndpoint . self::REST_PROGRAM . $id;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'Get program by id  ' . $id . ', ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets a summary of reporting information for a specified campaign.
     *
     * @param $campaignId
     *
     * @return null
     * @throws Exception
     */
    public function getCampaignSummary($campaignId)
    {
        $url = $this->_apiEndpoint . '/v2/campaigns/' . $campaignId
            . '/summary';

        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'Get Campaign Summary ' . $response->message
                . '  ,url : ' . $url;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Deletes a piece of transactional data by key.
     *
     * @param        $key
     * @param string $collectionName
     *
     * @return object
     */
    public function deleteContactsTransactionalData($key,
        $collectionName = 'Orders'
    ) 
    {
        $url = $this->_apiEndpoint . '/v2/contacts/transactional-data/'
            . $collectionName . '/' . $key;
        $this->setUrl($url)
            ->setVerb('DELETE');
        $response = $this->execute();

        if (isset($response->message)) {
            Mage::helper('ddg')->log(
                'DELETE CONTACTS TRANSACTIONAL DATA : ' . $url . ' '
                . $response->message
            );
        }

        return $response;
    }

    /**
     * Adds a document to a campaign as an attachment.
     *
     * @param $campaignId
     * @param $data
     *
     * @return object
     * @throws Exception
     */
    public function postCampaignAttachments($campaignId, $data)
    {
        $url = $this->_apiEndpoint . self::REST_CREATE_CAMPAIGN
            . "/$campaignId/attachments";
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($data);
        $result = $this->execute();

        if (isset($result->message)) {
            Mage::helper('ddg')->log(
                ' CAMPAIGN ATTACHMENT ' . $result->message
            );
        }

        return $result;
    }


    public function getNostoProducts($slotName, $email)
    {
        $recommended = Dotdigitalgroup_Email_Helper_Config::API_ENDPOINT
            . '/recommendations/email';
        $token       = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_NOSTO
        );

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
            Mage::helper('ddg')->log(
                "Nosto recommendation slot name : $slotName , email : $email"
            );
        }

        return $result;
    }

    /**
     * get contact address books
     *
     * @param $contactId
     *
     * @return object
     * @throws Exception
     */
    public function getContactAddressBooks($contactId)
    {
        $url = $this->_apiEndpoint . '/v2/contacts/' . $contactId
            . '/address-books';
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CONTACTS ADDRESS BOOKS contact: ' . $contactId
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
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
        $url = $this->_apiEndpoint . self::REST_TEMPLATES;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET API CONTACT LIST ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets a template by ID.
     *
     * @param $templateId
     *
     * @return object
     * @throws Exception
     */
    public function getApiTemplate($templateId)
    {
        $url = $this->_apiEndpoint . self::REST_TEMPLATES . '/' . $templateId;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET API CONTACT LIST ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Adds multiple pieces of transactional data to account asynchronously, returning an identifier that can be used to check for import progress.
     *
     * @param $collectionName
     * @param $transactionalData
     *
     * @return object
     */
    public function postAccountTransactionalDataImport($transactionalData,
        $collectionName = 'Catalog_Default'
    ) 
    {
        $orders = array();
        foreach ($transactionalData as $one) {
            if (isset($one->id)) {
                $orders[] = array(
                    'Key'               => $one->id,
                    'ContactIdentifier' => 'account',
                    'Json'              => json_encode($one->expose())
                );
            }
        }
        $url = $this->_apiEndpoint . self::REST_TRANSACTIONAL_DATA_IMPORT
            . $collectionName;
        $this->setURl($url)
            ->setVerb('POST')
            ->buildPostBody($orders);

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = ' SEND MULTI TRANSACTIONAL DATA TO ACCOUNT'
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    public function getCampaignsWithActivitySinceDate($dateTime)
    {
        $url = $this->_apiEndpoint . self::REST_DATA_FIELDS_CAMPAIGNS
            . '/with-activity-since/' . $dateTime;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CAMPAIGNS WITH ACTIVITY SINCE DATE '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    public function getCampaignActivityByContactId($campaignId, $contactId)
    {
        $url = $this->_apiEndpoint . self::REST_DATA_FIELDS_CAMPAIGNS . '/'
            . $campaignId . '/activities/' . $contactId;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CAMPAIGN ACTIVITY BY CONTACT ID '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets the import status of a previously started contact import.
     *
     * @param $importId
     *
     * @return object
     */
    public function getContactsImportByImportId($importId)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS_IMPORT . $importId;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CONTACTS IMPORT BY IMPORT ID ' . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * Gets the import status of a previously started transactional import.
     *
     * @param $importId
     *
     * @return object
     */
    public function getContactsTransactionalDataImportByImportId($importId)
    {
        $url = $this->_apiEndpoint . self::REST_TRANSACTIONAL_DATA_IMPORT
            . $importId;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        //log error
        if (isset($response->message)
            && ! in_array(
                $response->message, $this->exludeMessages
            )
        ) {
            $message = 'GET CONTACTS TRANSACTIONAL DATA IMPORT BY IMPORT ID '
                . $response->message;
            Mage::helper('ddg')->log($message)
                ->rayLog($response->message);
        }

        return $response;
    }

    /**
     * get contact import report faults
     *
     * @param $id
     *
     * @return bool|null
     * @throws Exception
     */
    public function getContactImportReportFaults($id)
    {
        $url = $this->_apiEndpoint . self::REST_CONTACTS_IMPORT . $id
            . '/report-faults';
        $this->setUrl($url)
            ->setVerb('GET');

        $this->setIsNotJsonTrue();
        $response = $this->execute();

        //if string is JSON than there is a error message
        if (json_decode($response)) {
            //log error
            if (isset($response->message)
                && ! in_array(
                    $response->message, $this->exludeMessages
                )
            ) {
                $message = 'GET CONTACT IMPORT REPORT FAULTS: '
                    . $response->message;
                Mage::helper('ddg')->log($message)
                    ->rayLog($response->message);
            }

            return false;
        }

        return $response;
    }
}