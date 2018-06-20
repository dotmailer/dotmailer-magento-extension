<?php

class Dotdigitalgroup_Email_TrialController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Trial account call back action.
     */
    public function accountcallbackAction()
    {
        $helper = Mage::helper('ddg');
        $params = $this->getRequest()->getParams();
        if (empty($params['apiUser']) ||
            empty($params['pass']) ||
            empty($params['code']) ||
            ! Mage::helper('ddg/trial')->authenticateTrialPasscode($params['code'])
        ) {
            $this->sendAjaxResponse(true);
        } else {
	        Mage::helper('ddg/trial')->clearTrialPasscode();

            //if apiEndpoint then save it
            if (isset($params['apiEndpoint'])) {
                $helper->saveApiEndPoint($params['apiEndpoint']);
            }

            //Save api details
            $apiConfigStatus = $helper->saveApiCreds($params['apiUser'], $params['pass']);

            //Setup data fields
            $dataFieldsStatus = $helper->setupDataFields();

            //Setup create address book
            $addressBookStatus = $helper->createAddressBooks();

            //enable syncs
            $syncStatus = $helper->enableSyncForTrial();

            //if all true send success response
            if ($apiConfigStatus && $dataFieldsStatus && $addressBookStatus && $syncStatus) {
                $this->sendAjaxResponse(false);
            } else {
                $this->sendAjaxResponse(true);
            }
        }
    }

    /**
     * @param $error
     * @param $msg
     */
    public function sendAjaxResponse($error)
    {
        $message = array(
            'err' => $error
        );
        $this->getResponse()->setBody(
            "signupCallback(" . Mage::helper('core')->jsonEncode($message) . ")"
        );
    }
}