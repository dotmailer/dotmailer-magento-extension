<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS
    . 'ResponseController.php';

class Dotdigitalgroup_Email_EmailController
    extends Dotdigitalgroup_Email_ResponseController
{

    /**
     * @var $_quote Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * wishlist
     */
    public function wishlistAction()
    {
        //authenticate
        $this->authenticate();
        $this->loadLayout();
        $wishlist = $this->getLayout()->createBlock(
            'ddg_automation/wishlist', 'connector_wishlist', array(
                'template' => 'connector/wishlist.phtml'
            )
        );
        $this->getLayout()->getBlock('content')->append($wishlist);
        $this->renderLayout();
        $this->checkContentNotEmpty($wishlist->toHtml(), false);
    }

    /**
     * Generate coupon for a coupon code id.
     */
    public function couponAction()
    {
        $this->authenticate();
        $this->loadLayout();
        //page root template
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }

        //content template
        $coupon = $this->getLayout()->createBlock(
            'ddg_automation/coupon', 'connector_coupon', array(
                'template' => 'connector/coupon.phtml'
            )
        );
        $this->getLayout()->getBlock('content')->append($coupon);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput(), false);
    }

    /**
     * Basket page to display the user items with specific email.
     */
    public function basketAction()
    {
        //authenticate
        $this->authenticate();
        $this->loadLayout();
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }

        $basket = $this->getLayout()->createBlock(
            'ddg_automation/basket', 'connector_basket', array(
                'template' => 'connector/basket.phtml'
            )
        );
        $this->getLayout()->getBlock('content')->append($basket);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    public function reviewAction()
    {
        //authenticate
        $this->authenticate();

        $orderId = $this->getRequest()->getParam('order_id', false);
        //check for order_id param
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            //check if the order still exists
            if ($order->getId()) {
                Mage::register('current_order', $order);
            } else {
                Mage::helper('ddg')->log('order not found: ' . $orderId);
                $this->sendResponse();
                Mage::throwException(
                    Mage::helper('ddg')->__('Order not found')
                );
            }
        } else {
            Mage::helper('ddg')->log('order_id missing :' . $orderId);
            $this->sendResponse();
            Mage::throwException(
                Mage::helper('ddg')->__('Order id is missing')
            );
        }


        $this->loadLayout();
        $review = $this->getLayout()->createBlock(
            'ddg_automation/order', 'connector_review', array(
                'template' => 'connector/review.phtml'
            )
        );
        $this->getLayout()->getBlock('content')->append($review);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Callback action for the automation studio.
     */
    public function callbackAction()
    {
        $code = $this->getRequest()->getParam('code', false);
        $userId = $this->getRequest()->getParam('state');
        $adminUser = Mage::getModel('admin/user')->load($userId);


        if ($code && $adminUser->getId()) {
            $baseUrl = Mage::getBaseUrl(
                Mage_Core_Model_Store::URL_TYPE_WEB, true
            );
            //callback url
            $callback = $baseUrl . 'connector/email/callback';
            $data = 'client_id=' . Mage::getStoreConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID
                ) .
                '&client_secret=' . Mage::getStoreConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID
                ) .
                '&redirect_uri=' . $callback .
                '&grant_type=authorization_code' .
                '&code=' . $code;


            $url = Mage::helper('ddg/config')->getTokenUrl();
            //@codingStandardsIgnoreStart
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt(
                $ch, CURLOPT_HTTPHEADER,
                array('Content-Type: application/x-www-form-urlencoded')
            );


            $response = json_decode(curl_exec($ch));
            if ($response === false) {
                Mage::helper('ddg')->log("Error Number: " . curl_errno($ch));
            }

            //save the refresh token to the admin user
            $adminUser->setRefreshToken($response->refresh_token)->save();
            //@codingStandardsIgnoreEnd
        }

        //redirect to automation index page
        $this->_redirectReferer(
            Mage::helper('adminhtml')->getUrl('adminhtml/email_studio/index')
        );
    }

    /**
     * Quote process action.
     */
    public function getbasketAction()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        //no quote id redirect to base url
        if (!$quoteId) {
            $this->_redirectUrl(Mage::getBaseUrl());
        }

        $quoteModel = Mage::getModel('sales/quote')->load($quoteId);

        //no quote id redirect to base url
        if (!$quoteModel->getId()) {
            $this->_redirectUrl(Mage::getBaseUrl());
        }

        //set quoteModel to _quote property for later use
        $this->_quote = $quoteModel;

        if ($quoteModel->getCustomerId()) {
            $this->_handleCustomerBasket();
        } else {
            $this->_handleGuestBasket();
        }
    }

    /**
     * Process customer basket.
     */
    protected function _handleCustomerBasket()
    {
        $customerSession = Mage::getSingleton('customer/session');
        $configCartUrl = $this->_quote->getStore()->getWebsite()->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_CART_URL
        );

        //if customer is logged in then redirect to cart
        if ($customerSession->isLoggedIn() &&
            $customerSession->getCustomerId() == $this->_quote->getCustomerId()
        ) {
            //check session quote for missing items and add
            $this->_checkMissingAndAdd();

            if ($configCartUrl) {
                $url = $configCartUrl;
            } else {
                $url = $this->_quote->getStore()->getUrl('checkout/cart');
            }

            $this->_redirectUrl($url);
        } else {
            // customer will be redirected to cart after successful login
            if ($configCartUrl) {
                $cartUrl = $configCartUrl;
            } else {
                $cartUrl = 'checkout/cart';
            }

            $customerSession->setAfterAuthUrl(
                $this->_quote->getStore()->getUrl($cartUrl)
            );

            //send customer to login page
            $configLoginUrl = $this->_quote->getStore()->getWebsite()
                ->getConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_LOGIN_URL
                );
            if ($configLoginUrl) {
                $loginUrl = $configLoginUrl;
            } else {
                $loginUrl = 'customer/account/login';
            }

            $this->_redirectUrl($this->_quote->getStore()->getUrl($loginUrl));
        }
    }

    /**
     * Process guest.
     */
    protected function _handleGuestBasket()
    {
        $configCartUrl = $this->_quote->getStore()->getWebsite()->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_CART_URL
        );

        if ($configCartUrl) {
            $url = $configCartUrl;
        } else {
            $url = 'checkout/cart';
        }

        $this->_redirectUrl($this->_quote->getStore()->getUrl($url));
    }

    /**
     * Check missing items from current quote and add.
     */
    protected function _checkMissingAndAdd()
    {
        $currentQuote = Mage::getSingleton('checkout/session')->getQuote();
        $currentItemIds = array();

        if ($currentQuote->hasItems()) {
            $currentSessionItems = $currentQuote->getAllItems();
            foreach ($currentSessionItems as $currentSessionItem) {
                $currentItemIds[] = $currentSessionItem->getId();
            }

            foreach ($this->_quote->getAllItems() as $item) {
                if (!in_array($item->getId(), $currentItemIds)) {
                    $currentQuote->addItem($item);
                }
            }

            $currentQuote->collectTotals()->save();
        }
    }
}