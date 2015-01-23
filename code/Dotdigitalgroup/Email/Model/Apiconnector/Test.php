<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Test extends Dotdigitalgroup_Email_Model_Apiconnector_Client
{
    const TEST_API_USERNAME = 'apiuser-8e3b8f244ec9@apiconnector.com';
    const TEST_API_PASSWORD = 'TWFnZW50bzIwMTM=';
    const TEST_API_CAMPAIGN = '2643928';
    const TEST_CONTACT_ID   = '13';
    const TEST_CONTACT_EMAIL = 'ben.staveley@dotmailer.co.uk';


    /**
	 * Validate apiuser on save.
	 *
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
                Mage::getSingleton('adminhtml/session')->addError($accountInfo->message);
                Mage::helper('connector')->log('VALIDATION ERROR :  ' . $accountInfo->message);
                return false;
            }
            return $accountInfo;
        }
        return false;
    }

    /**
	 * Ajax validate api user.
	 *
	 * @param $apiUsername
	 * @param $apiPassword
	 *
	 * @return bool|string
	 */
    public function ajaxvalidate($apiUsername, $apiPassword)
    {
        if ($apiUsername && $apiPassword) {
            $message = 'Credentials Valid.';
            $this->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
            $response = $this->getAccountInfo();
            if (isset($response->message)) {
                $message = 'API Username And Password Do Not Match!';
                Mage::helper('connector')->log($message);
            }
            return $message;
        }
        return false;
    }

    /**
	 * Confirm the installation.
	 */
    public function sendInstallConfirmation()
    {
        // set test credentials
        $this->setApiUsername(self::TEST_API_USERNAME)
            ->setApiPassword(
	            base64_decode(self::TEST_API_PASSWORD));
        $testEmail         = self::TEST_CONTACT_EMAIL;
        $contactId         = self::TEST_CONTACT_ID;
        $campaignId        = self::TEST_API_CAMPAIGN;

        /**
         * send initial info
         */
        $this->sendIntallInfo($testEmail, $contactId, $campaignId);
    }
}
