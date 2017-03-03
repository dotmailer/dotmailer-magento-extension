<?php

class Dotdigitalgroup_Email_ResponseController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * @throws Exception
     */
    protected function authenticate()
    {
        //authenticate ip address
        $authIp = Mage::helper('ddg')->authIpAddress();

        if (! $authIp) {
            $message = 'You are not authorised to view content of this page.';
            Mage::throwException($message);
        }

        //authenticate
        $auth = Mage::helper('ddg')->auth(
            $this->getRequest()->getParam('code')
        );

        if (! $auth) {
            $this->sendResponse();
            Mage::throwException(
                Mage::helper('ddg')->__('Authentication failed!')
            );
        }
    }

    /**
     * @param      $output
     * @param bool $flag
     *
     * @throws Exception
     */
    protected function checkContentNotEmpty($output, $flag = true)
    {
        try {
            if (strlen($output) < 3 && $flag == false) {
                $this->sendResponse();
            } elseif ($flag && strpos($output, '<table') === false) {
                $this->sendResponse();
            }
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    protected function sendResponse()
    {
        try {
            $this->getResponse()
                ->setHttpResponseCode(204)
                ->setHeader('Pragma', 'public', true)
                ->setHeader(
                    'Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0', true
                )
                ->setHeader('Content-type', 'text/html; charset=UTF-8', true);
            $this->getResponse()->sendHeaders();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }
}
