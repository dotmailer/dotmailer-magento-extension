<?php

class Dotdigitalgroup_Email_TrialController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * @var array
     */
    public $params;

    /**
     * @var array
     */
    public $ipRanges = array(
        '104.40.179.234',
        '104.40.159.161',
        '191.233.82.46',
        '104.46.48.100',
        '104.40.187.26'
    );

    /**
     *
     */
    public function preDispatch()
    {
        $this->params = $this->getRequest()->getParams();

        //if ip is not in range send error response
        if (! in_array(Mage::helper('core/http')->getRemoteAddr(), $this->ipRanges) or
            ! isset($params['accountId']) or ! isset($params['apiUser']) or ! isset($params['pass'])
        ) {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        }

        if (empty($params['accountId']) or empty($params['apiUser']) or empty($params['pass'])) {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        }

        parent::preDispatch();
    }

    /**
     * Trial account call back action.
     */
    public function accountcallbackAction()
    {
        $helper = Mage::helper('ddg');
        //Save api details
        $apiConfigStatus = $helper->saveApiCreds($this->params['apiUser'], $this->params['pass']);
        //Setup data fields
        $dataFieldsStatus = $helper->setupDataFields();
        //Setup create address book
        $addressBookStatus = $helper->createAddressBooks();
        //enable syncs
        $syncStatus = $helper->enableSyncForTrial();
        //if apiEndpoint then save it
        if (isset($this->params['apiEndpoint'])) {
            $helper->saveApiEndPoint($this->params['apiEndpoint']);
        }

        //if all true send success response
        if ($apiConfigStatus && $dataFieldsStatus && $addressBookStatus && $syncStatus) {
            $this->sendAjaxResponse(false, $this->_getSuccessHtml());
        } else {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
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
        )->sendResponse();
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