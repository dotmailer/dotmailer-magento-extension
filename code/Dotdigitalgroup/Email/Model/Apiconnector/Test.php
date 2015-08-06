<?php
class Dotdigitalgroup_Email_Model_Apiconnector_Test extends Dotdigitalgroup_Email_Model_Apiconnector_Client
{
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
                Mage::helper('ddg')->log('VALIDATION ERROR :  ' . $accountInfo->message);
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
                Mage::helper('ddg')->log($message);
            }
            return $message;
        }
        return false;
    }
}