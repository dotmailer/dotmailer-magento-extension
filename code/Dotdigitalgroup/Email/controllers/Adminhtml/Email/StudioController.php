<?php

class Dotdigitalgroup_Email_Adminhtml_Email_StudioController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Main page for automation studio. Must be authinticated.
     */
    public function indexAction()
    {
        $this->_title($this->__('Automation Studio'));
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');

        // authorize or create token.
        $token        = $this->generatetokenAction();
        $baseUrl      = Mage::helper('ddg/config')->getLogUserUrl();
        $loginuserUrl = $baseUrl . $token . '&suppressfooter=true';

        $this->getLayout()->getBlock('connector_iframe')
            ->setText(
                "<iframe src=" . $loginuserUrl .
                " width=100% height=1650 frameborder='0' scrolling='no' style='margin:0;padding:0;display:block;'>
                </iframe>"
            );


        $this->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'ddg_automation/automation_studio'
        );
    }

    /**
     * Generate new token and connect from the admin.
     *
     */
    public function generatetokenAction()
    {
        //check for secure url
        $adminUser    = Mage::getSingleton('admin/session')->getUser();
        $refreshToken = Mage::getSingleton('admin/user')->load(
            $adminUser->getId()
        )->getRefreshToken();

        if ($refreshToken) {
            $code   = Mage::helper('ddg')->getCode();
            $params = 'client_id=' . Mage::getStoreConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID
                ) .
                '&client_secret=' . Mage::getStoreConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID
                ) .
                '&refresh_token=' . $refreshToken .
                '&grant_type=refresh_token';

            $url = Mage::helper('ddg/config')->getTokenUrl();

            Mage::helper('ddg')->log(
                'token code : ' . $code . ', params : ' . $params
            );
            //@codingStandardsIgnoreStart
            /**
             * Refresh Token request.
             */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt(
                $ch, CURLOPT_HTTPHEADER,
                array('Content-Type: application/x-www-form-urlencoded')
            );

            $response = json_decode(curl_exec($ch));

            if (isset($response->error)) {
                Mage::helper('ddg')->log(
                    "Token Error Num`ber:" . curl_errno($ch) . "Error String:"
                    . curl_error($ch)
                );
            }

            curl_close($ch);

            $token = $response->access_token;
            //@codingStandardsIgnoreEnd

            return $token;
        } else {
            Mage::getSingleton('adminhtml/session')->addNotice(
                'Please Connect To Access The Page.'
            );
        }

        $this->_redirect(
            '*/system_config/edit',
            array('section' => 'connector_developer_settings')
        );
    }

    /**
     * Disconnect and remote the refresh token.
     */
    public function disconnectAction()
    {
        try {
            $adminUser = Mage::getSingleton('admin/session')->getUser();

            if ($adminUser->getRefreshToken()) {
                $adminUser->setRefreshToken()->save();
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(
                'Successfully disconnected'
            );
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirectReferer('*/*/*');
    }
}
