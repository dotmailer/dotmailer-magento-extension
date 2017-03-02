<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Addressbooks
{

    /**
     * Returns the address books options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = array();
        // Add a "Do Not Map" Option
        $fields[] = array('value' => 0, 'label' => Mage::helper('ddg')->__(
            '-- Please Select --'
        ));
        $website  = Mage::app()->getRequest()->getParam('website');

        $enabled = Mage::helper('ddg')->isEnabled($website);

        //get address books options
        if ($enabled) {
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);

            $savedAddressBooks = Mage::registry('addressbooks');
            $addressBooks = array();
            //get saved address books from registry
            if ($savedAddressBooks) {
                $addressBooks = $savedAddressBooks;
            } else {
                // api all address books
                if ($client) {
                    $addressBooks = $client->getAddressBooks();
                    Mage::register('addressbooks', $addressBooks);
                }
            }

            //set up fields with book id and label
            foreach ($addressBooks as $book) {
                //check for address book id before displaying, IMPORTANT :Test address book cannot be used through api
                if (isset($book->id) && $book->name != 'Test') {
                    $fields[] = array('value' => $book->id,
                                      'label' => $book->name);
                }
            }
        }

        return $fields;
    }

}