<?php

class Dotdigitalgroup_Email_Model_Cron
{
    /**
     * CRON FOR CONTACTS SYNC
     */
    public function contactSync()
    {
        // send customers
        $result = Mage::getModel('email_connector/apiconnector_contact')->sync();
	    return $result;
    }

    /**
     * CRON FOR LOST BASKET
     */
    public function lostBaskets()
    {
	    //don't execute if the cron is running from shell
	    if (! Mage::getStoreConfigFlag(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ABANDONED_CART_SHELL)) {
		    // send lost basket
		    Mage::getModel( 'email_connector/sales_quote' )->proccessLostBaskets();
	    }
    }

    /**
     * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS
     */
    public function reviewsAndWishlist()
    {
        //sync reviews
        $this->reviewSync();
        //sync wishlist
        Mage::getModel('email_connector/wishlist')->sync();
    }

    /**
     * review sync
     */
    public function reviewSync()
    {
        //find orders to review and register campaign
        Mage::getModel('email_connector/sales_order')->createReviewCampaigns();
        //sync reviews
        $result = Mage::getModel('email_connector/review')->sync();
        return $result;
    }

    /**
     * order sync
     *
     * @return mixed
     */
    public function orderSync()
    {
        // send order
        $orderResult = Mage::getModel('email_connector/sales_order')->sync();
        return $orderResult;
    }

    /**
     * quote sync
     *
     * @return mixed
     */
    public function quoteSync()
    {
        //send quote
        $quoteResult = Mage::getModel('email_connector/quote')->sync();

        return $quoteResult;
    }

    /**
     * CRON FOR ORDER & QUOTE TRANSACTIONAL DATA
     */
    public function orderAndOrderSync()
    {
        // send order
        $orderResult = $this->orderSync();

        //send quote
        $quoteResult = $this->quoteSync();

        return $orderResult['message'] . '  ' .$quoteResult['message'];
    }

    /**
     * CRON FOR SUBSCRIBERS AND GUEST CONTACTS
     */
    public function subscribersAndGuestSync()
    {
        //sync subscribers
	    $subscriberModel = Mage::getModel('email_connector/newsletter_subscriber');
        $result = $subscriberModel->sync();

	    //unsubscribe suppressed contacts
	    $subscriberModel->unsubscribe();

        //sync guests
        Mage::getModel('email_connector/customer_guest')->sync();
	    return $result;
    }

    /**
     * CRON FOR EMAILS SENDING
     */
    public function sendMappedEmails()
    {
        Mage::getModel('email_connector/campaign')->sendCampaigns();

        return $this;
    }

    /**
     * CLEAN ARHIVED FOLDERS
     */
    public function cleaning()
    {
        $helper = Mage::helper('connector/file');
	    $archivedFolder = $helper->getArchiveFolder();
	    $result = $helper->deleteDir($archivedFolder);
	    $message = 'Cleaning cronjob result : ' . $result;
	    $helper->log($message);
	    Mage::helper('connector')->rayLog('10', $message, 'model/cron.php');
        return $result;
    }


	/**
	 * Last customer sync date.
	 * @return bool|string
	 */
	public function getLastCustomerSync(){

		$schedules = Mage::getModel('cron/schedule')->getCollection();
		$schedules->getSelect()->limit(1)->order('executed_at DESC');
		$schedules->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS);
		$schedules->addFieldToFilter('job_code', 'connector_email_customer_sync');
		$schedules->load();

		if (count($schedules) == 0) {
			return false;
		}
		$executedAt = $schedules->getFirstItem()->getExecutedAt();
		return Mage::getModel('core/date')->date(NULL, $executedAt);
	}

}