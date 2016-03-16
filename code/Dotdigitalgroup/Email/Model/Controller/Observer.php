<?php

class Dotdigitalgroup_Email_Model_Controller_Observer
{

    /**
     * @param $observer
     *
     * @return $this
     */
    public function controllerActionPostdispatch($observer)
    {
        //event data
        $event = $observer->getEvent();

        //check for module name is a match for current request
        if ($event->getControllerAction()->getRequest()->getModuleName()
            == 'connector'
        ) {

            //check if the geoip module is installed
            $modules = Mage::getConfig()->getNode('modules')->children();
            $modulesArray = (array)$modules;

            //module installed make sure don't redirect
            if (isset($modulesArray['Sandfox_GeoIP'])) {

                //order id param
                $orderId = $event->getControllerAction()->getRequest()
                    ->getParam('order_id', false);

                //order id param is set
                if ($orderId) {
                    $order = Mage::getModel('sales/order')->load($orderId);
                    $store = Mage::app()->getStore($order->getStore());
                    //order still exits and store name is different than the order
                    if ($order->getId()
                        && $store->getName() != Mage::app()->getStore()
                            ->getName()
                    ) {

                        //redirect to original store
                        $event->getControllerAction()->getResponse()
                            ->setRedirect($store->getCurrentUrl(false));
                    }
                }
            }
        }

        return $this;
    }
}
