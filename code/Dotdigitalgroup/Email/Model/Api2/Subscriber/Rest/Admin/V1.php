<?php
class Dotdigitalgroup_Email_Model_Api2_Subscriber_Rest_Admin_V1 extends Mage_Api2_Model_Resource
{

	/**
	 * Create a subscriber
	 * @return array
	 */

	public function _create() {
		//Create Subscriber
		$requestData = $this->getRequest()->getBodyParams();

		$email = $requestData['subscriber_email'];
		//email is set
		if ($email) {
			try {
				$customerId = (isset($requestData['customer_id']))? $requestData['customer_id'] : 0;
				$storeId = (isset($requestData['store_id']))? $requestData['store_id'] : 1;
				//subscriber status 1- subscribed, 3  - unsubscribed
				$status = (isset($requestData['status']))? $requestData['status'] : 3;
				//additional data for subscriber
				$data = array(
					'subscriber_email' => $email,
					'customer_id' => $customerId,
					'subscriber_status' => $status,
					'store_id' => $storeId
				);

				//save subscriber
				Mage::getModel('newsletter/subscriber')->setData($data)
				    ->save();

			}catch (Mage_Api2_Exception $e){
				Mage::helper('ddg')->log($e->getMessage());
			}catch (Exception $e){
				Mage::logException($e);
			}

			$json = array('email' => $email);
			echo json_encode($json);
		}

	}

	/**
	 * Retrieve a subscriber name by email
	 * @return string
	 */

	public function _retrieve()
	{
		$email = $this->getRequest()->getParam('email', false);
		if (! $email) {
			Mage::helper('ddg')->log('Subscriber id is not set');
			return array();
		}
		try {

			$data = Mage::getModel('newsletter/subscriber')->loadByEmail($email)->getData();
			return json_encode($data);

		}catch (Mage_Api2_Exception $e){
			Mage::helper('ddg')->log($e->getMessage());
		}catch (Exception $e){
			Mage::logException($e);
		}
	}

	/**
	 * Update subscriber data.
	 * @throws Exception
	 */
	public function _update()
	{
		//Update Subscriber
		$requestData = $this->getRequest()->getBodyParams();

		//check for scubscriber email
		if ($email = $requestData['subscriber_email']) {
			try {
				$customerId = ( isset( $requestData['customer_id'] ) ) ? $requestData['customer_id'] : 0;
				$storeId    = ( isset( $requestData['store_id'] ) ) ? $requestData['store_id'] : 1;
				//subscriber status 1- subscribed, 3  - unsubscribed
				$status = ( isset( $requestData['status'] ) ) ? $requestData['status'] : 3;
				//additional data for subscriber
				$data = array(
					'customer_id'       => $customerId,
					'subscriber_status' => $status,
					'store_id'          => $storeId
				);
				//update subscriber
				$subscriber = Mage::getModel( 'newsletter/subscriber' )->loadByEmail( $email );
				if ( $subscriber->getId() ) {
					$subscriber->setCustomerId( $customerId )
					           ->setSubscriberStatus( $status )
					           ->setStoreId( $storeId )
					           ->save();
				} else {
					Mage::helper( 'ddg' )->log( "REST Subscriber not found : " . $email);
				}

				echo json_encode( $data );
			}catch (Mage_Api2_Exception $e){
				Mage::helper('ddg')->log($e->getMessage());
			}catch (Exception $e){
				Mage::logException($e);
			}
		}
	}
}