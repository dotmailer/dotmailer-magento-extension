<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_EmailController extends Dotdigitalgroup_Email_ResponseController
{
    /**
     * Generate coupon for a coupon code id.
     */
    public function couponAction()
    {
        //authenticate
        Mage::helper('connector')->auth($this->getRequest()->getParam('code'));

        $this->loadLayout();
        //page root template
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
        //content template
        $coupon = $this->getLayout()->createBlock('email_connector/coupon', 'connector_coupon', array(
            'template' => 'connector/coupon.phtml'
        ));
        $this->checkContentNotEmpty($coupon->toHtml(), false);
        $this->getLayout()->getBlock('content')->append($coupon);
        $this->renderLayout();
    }

    /**
     * Basket page to display the user items with specific email.
     */
    public function basketAction()
    {
        //authenticate
        Mage::helper('connector')->auth($this->getRequest()->getParam('code'));
        $this->loadLayout();
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
        $basket = $this->getLayout()->createBlock('email_connector/basket', 'connector_basket', array(
            'template' => 'connector/basket.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($basket);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    public function reviewAction()
    {
        //authenticate
        Mage::helper('connector')->auth($this->getRequest()->getParam('code'));
        $this->loadLayout();
        $review = $this->getLayout()->createBlock('email_connector/order', 'connector_review', array(
            'template' => 'connector/review.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($review);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Access Log files.
     */
    public function logAction()
    {
        //file name param
        $fileName = $filePath = '';
        $file = $this->getRequest()->getParam('file', false);
        if ($file) {
            $fileName = $file . '.log';
            $filePath = Mage::getBaseDir( 'log' ) . DIRECTORY_SEPARATOR . $fileName;
        } elseif ($csv = $this->getRequest()->getParam('archive', false)){
            $fileName = $csv . '.csv';
            $filePath = Mage::getBaseDir('export') . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . $fileName;
        }

        $this->_prepareDownloadResponse($fileName, array(
            'type'  => 'filename',
            'value' => $filePath
        ));
		return;
    }

    public function showAction() {
        $list = array();

        if ($handle = opendir(Mage::getBaseDir('export') . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR . 'archive')) {


            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {

                    $list[] = $entry;
                }
            }

            closedir($handle);
        }

        foreach ( $list as $one ) {

            echo $one . '</br>';
        }
        return;
    }

    /**
     * Callback action for the automation studio.
     */
    public function callbackAction()
    {
        $code = $this->getRequest()->getParam('code', false);
        $userId = $this->getRequest()->getParam('state');
        $adminUser = Mage::getModel('admin/user')->load($userId);
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);

        //callback url
        $callback    = $baseUrl . 'connector/email/callback';

        if ($code) {
            $data = 'client_id='    . Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID) .
                '&client_secret='   . Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID) .
                '&redirect_uri='    . $callback .
                '&grant_type=authorization_code' .
                '&code='            . $code;


            $url = Dotdigitalgroup_Email_Helper_Config::API_CONNECTOR_URL_TOKEN;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded'));


            $response = json_decode(curl_exec($ch));
            if ($response === false) {
                Mage::helper('connector')->rayLog('100', 'Automaion studio number not found : ' . serialize($response));
                Mage::helper('connector')->log("Error Number: " . curl_errno($ch));
            }

            //save the refresh token to the admin user
            $adminUser->setRefreshToken($response->refresh_token)->save();
        }
        //redirect to automation index page
        $this->_redirectReferer(Mage::helper('adminhtml')->getUrl('adminhtml/email_automation/index'));
    }
}