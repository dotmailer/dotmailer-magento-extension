<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Addressbookspref
{

    /**
     * @return Mage_Core_Model_Website
     */
    protected function getWebsite()
    {
        $website      = Mage::app()->getWebsite();
        $websiteParam = Mage::app()->getRequest()->getParam('website');
        if ($websiteParam) {
            $website = Mage::app()->getWebsite($websiteParam);
        }

        return $website;
    }

    /**
     * Get address books.
     *
     * @return null
     */
    protected function getAddressBooks()
    {
        $website = $this->getWebsite();
        $client = Mage::helper('ddg')->getWebsiteApiClient($website);

        $savedAddressBooks = Mage::registry('addressbooks');
        $addressBooks = false;
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

        return $addressBooks;
    }

    /**
     * Addressbook options.
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function toOptionArray()
    {
        $fields  = array();
        $website = $this->getWebsite();
        $enabled = Mage::helper('ddg')->isEnabled($website);

        //get address books options
        if ($enabled) {
            $addressBooks = $this->getAddressBooks();
            //set the error message to the select option
            if (isset($addressBooks->message)) {
                $fields[] = array(
                    'value' => 0,
                    'label' => Mage::helper('ddg')->__($addressBooks->message)
                );
            }

            $subscriberAddressBook = Mage::helper('ddg')
                ->getSubscriberAddressBook(Mage::app()->getWebsite());

            //set up fields with book id and label
            if ($addressBooks) {
                foreach ($addressBooks as $book) {
                    if (isset($book->id) && $book->visibility == 'Public'
                        && $book->id != $subscriberAddressBook
                    ) {
                        $fields[] = array(
                            'value' => $book->id,
                            'label' => $book->name
                        );
                    }
                }
            }
        }

        return $fields;
    }
}
