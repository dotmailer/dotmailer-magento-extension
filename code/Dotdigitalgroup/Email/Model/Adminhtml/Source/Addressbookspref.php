<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Addressbookspref
{
	private function getWebsite()
	{
		$website = Mage::app()->getWebsite();
		$websiteParam = Mage::app()->getRequest()->getParam('website');
		if($websiteParam)
			$website = Mage::app()->getWebsite($websiteParam);
		return $website;
	}

	/**
	 * get address books
	 *
	 * @return null
	 */
	private function getAddressBooks()
	{
		$website = $this->getWebsite();
		$client = Mage::getModel( 'email_connector/apiconnector_client' );
		$client->setApiUsername( Mage::helper( 'connector' )->getApiUsername( $website ) )
			->setApiPassword( Mage::helper( 'connector' )->getApiPassword( $website ) );

		$savedAddressBooks = Mage::registry( 'addressbooks' );
		//get saved address books from registry
		if ( $savedAddressBooks ) {
			$addressBooks = $savedAddressBooks;
		} else {
			// api all address books
			$addressBooks = $client->getAddressBooks();
			Mage::register( 'addressbooks', $addressBooks );
		}
		return $addressBooks;
	}

	/**
	 * addressbook options
	 *
	 * @return array
	 * @throws Mage_Core_Exception
	 */
	public function toOptionArray()
	{
		$fields = array();
		$website = $this->getWebsite();

		$enabled = Mage::helper('connector')->isEnabled($website);

		//get address books options
		if ($enabled) {
			$addressBooks = $this->getAddressBooks();
			//set the error message to the select option
			if ( isset( $addressBooks->message ) ) {
				$fields[] = array( 'value' => 0, 'label' => Mage::helper( 'connector' )->__( $addressBooks->message) );
			}

			$subscriberAddressBook = Mage::helper('connector')->getSubscriberAddressBook(Mage::app()->getWebsite());

			//set up fields with book id and label
			foreach ( $addressBooks as $book ) {
				if (isset($book->id) &&  $book->visibility == 'Public' &&  $book->id != $subscriberAddressBook) {
					$fields[] = array( 'value' => $book->id, 'label' => $book->name );
				}
			}
		}

		return $fields;
	}
}
