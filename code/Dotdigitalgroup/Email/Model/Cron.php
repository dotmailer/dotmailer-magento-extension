<?php

class Dotdigitalgroup_Email_Model_Cron
{
    /**
     * CRON FOR EMAIL IMPORTER PROCESSOR
     */
    public function emailImporter()
    {
        return Mage::getModel('ddg_automation/importer')->processQueue();
    }

    /**
     * CRON FOR CATALOG SYNC
     */
    public function catalogSync()
    {
        // send customers
        $result = Mage::getModel('ddg_automation/catalog')->sync();
        return $result;
    }

    /**
     * CRON FOR CONTACTS SYNC
     */
    public function contactSync()
    {
        // send customers
        $result = Mage::getModel('ddg_automation/apiconnector_contact')->sync();
        $subscriberResult = $this->subscribersAndGuestSync();
        if(isset($subscriberResult['message']) && isset($result['message']))
            $result['message'] = $result['message'] . ' - ' . $subscriberResult['message'];
	    return $result;
    }

    /**
     * CRON FOR ABANDONED CARTS
     */
    public function abandonedCarts()
    {

		Mage::getModel( 'ddg_automation/sales_quote' )->proccessAbandonedCarts();

    }

    /**
     * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS
     */
    public function reviewsAndWishlist()
    {
        //sync reviews
        $this->reviewSync();
        //sync wishlist
        Mage::getModel('ddg_automation/wishlist')->sync();
    }

    /**
     * review sync
     */
    public function reviewSync()
    {
        //find orders to review and register campaign
        Mage::getModel('ddg_automation/sales_order')->createReviewCampaigns();
        //sync reviews
        $result = Mage::getModel('ddg_automation/review')->sync();
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
        $orderResult = Mage::getModel('ddg_automation/sales_order')->sync();
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
        $quoteResult = Mage::getModel('ddg_automation/quote')->sync();

        return $quoteResult;
    }

    /**
     * CRON FOR ORDER & QUOTE TRANSACTIONAL DATA
     */
    public function orderAndQuoteSync()
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
	    $subscriberModel = Mage::getModel('ddg_automation/newsletter_subscriber');
        $result = $subscriberModel->sync();

	    //unsubscribe suppressed contacts
	    $subscriberModel->unsubscribe();

        //sync guests
        Mage::getModel('ddg_automation/customer_guest')->sync();
	    return $result;
    }

    /**
     * CRON FOR EMAILS SENDING
     */
    public function sendEmails()
    {
        Mage::getModel('ddg_automation/campaign')->sendCampaigns();

        return $this;
    }

    /**
     * CLEAN ARHIVED FOLDERS
     */
    public function cleaning()
    {
        $helper = Mage::helper('ddg/file');
	    $archivedFolder = $helper->getArchiveFolder();
	    $result = $helper->deleteDir($archivedFolder);
	    $message = 'Cleaning cronjob result : ' . $result;
	    $helper->log($message);
	    Mage::helper('ddg')->rayLog('10', $message, 'model/cron.php');
        return $result;
    }


	/**
	 * Last customer sync date.
	 * @return bool|string
	 */
	public function getLastCustomerSync(){

		$schedules = Mage::getModel('cron/schedule')->getCollection();
		$schedules->getSelect()->limit(1)->order('executed_at DESC');
		$schedules->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS)
            ->addFieldToFilter('job_code', 'ddg_automation_customer_subscriber_guest_sync')
			->getSize();


		if ($schedules->getSize() == 0) {
			return false;
		}
		$executedAt = $schedules->getFirstItem()->getExecutedAt();
		return Mage::getModel('core/date')->date(NULL, $executedAt);
	}

	public function automationStatus()
	{
		Mage::getModel('ddg_automation/automation')->enrollment();

	}
}