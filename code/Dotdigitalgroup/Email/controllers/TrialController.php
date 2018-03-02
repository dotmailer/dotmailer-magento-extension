<?php

class Dotdigitalgroup_Email_TrialController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Trial account call back action.
     */
    public function accountcallbackAction()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params['apiUser']) or empty($params['pass'])) {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        } else {
            $helper = Mage::helper('ddg');

            //Save api details
            $apiConfigStatus = $helper->saveApiCreds($params['apiUser'], $params['pass']);

            //Setup data fields
            $dataFieldsStatus = $helper->setupDataFields();

            //Setup create address book
            $addressBookStatus = $helper->createAddressBooks();

            //enable syncs
            $syncStatus = $helper->enableSyncForTrial();

            //if apiEndpoint then save it
            if (isset($params['apiEndpoint'])) {
                $helper->saveApiEndPoint($params['apiEndpoint']);
            }

            //if all true send success response
            if ($apiConfigStatus && $dataFieldsStatus && $addressBookStatus && $syncStatus) {
                $this->sendAjaxResponse(false, $this->_getSuccessHtml());
            } else {
                $this->sendAjaxResponse(true, $this->_getErrorHtml());
            }
        }
    }

    /**
     * @param $error
     * @param $msg
     */
    public function sendAjaxResponse($error, $msg)
    {
        $message = array(
            'err' => $error,
            'message' => $msg
        );
        $this->getResponse()->setBody(
            $this->getRequest()->getParam('callback') . "(" . Mage::helper('core')->jsonEncode($message) . ")"
        );
    }

    /**
     * Success html for response.
     *
     * @return string
     */
    protected function _getSuccessHtml()
    {
        return
            "<div class='modal-page'>
                <div class='success'></div>
                <h2 class='center'>Congratulations your dotmailer account is now ready, 
                time to make your marketing awesome</h2>
                <div class='center'>
                    <input type='submit' class='center' value='Start making money' />
                </div>
            </div>";
    }

    /**
     * Error html for response.
     *
     * @return string
     */
    protected function _getErrorHtml()
    {
        return
            "<div class='modal-page'>
                <div class='fail'></div>
                <h2 class='center'>Sorry, something went wrong whilst trying to create your new dotmailer account</h2>
                <div class='center'>
                    <a class='submit secondary center' 
                    href='mailto:support@dotmailer.com'>Contact support@dotmailer.com</a>
                </div>
            </div>";
    }
}