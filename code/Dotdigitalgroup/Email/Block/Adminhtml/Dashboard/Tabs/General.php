<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_General
    extends Mage_Adminhtml_Block_Dashboard_Bar
{

    /**
     * @var array
     */
    public $groups = array();

    /**
     * Set the template.
     */
    public function _construct()
    {
        $this->initiateGroupArray();
        parent::_construct();
        $this->setTemplate('connector/dashboard/tabs/general/index.phtml');
    }

    /**
     * Prepare the layout.
     *
     * @return Mage_Core_Block_Abstract|void
     * @throws Exception
     */
    protected function _prepareLayout()
    {
        $website = 0;
        if ($store = $this->getRequest()->getParam('store')) {
            $website = Mage::app()->getStore($store)->getWebsite();
        } elseif ($this->getRequest()->getParam('website')) {
            $website = $this->getRequest()->getParam('website');
        }
        $client = Mage::helper('ddg')->getWebsiteApiClient($website);
        if ($client) {
            $data = $client->getAccountInfo();

            if (isset($data->id)) {
                $this->prepareGroupArray($data);
            }
        }

        $this->_setChild();

        parent::_prepareLayout();
    }

    protected function _setChild()
    {
        foreach ($this->groups as $key => $data) {
            $this->setChild(
                $key,
                $this->getLayout()->createBlock(
                    'ddg_automation/adminhtml_dashboard_tabs_general_data', '',
                    $data
                )
            );
        }
    }

    protected function prepareGroupArray($data)
    {
        foreach ($data->properties as $one) {
            foreach ($this->groups as $key => $type) {
                if (array_key_exists($one->name, $type)) {
                    $this->groups[$key][$one->name] = $one->value;
                }
            }
        }
    }

    protected function initiateGroupArray()
    {
        $this->groups['account'] = array(
            'Title'                      => 'Account',
            'Name'                       => $this->__('Not Available'),
            'MainMobilePhoneNumber'      => $this->__('Not Available'),
            'MainEmail'                  => $this->__('Not Available'),
            'AvailableEmailSendsCredits' => $this->__('Not Available')
        );

        $this->groups['api'] = array(
            'Title'             => 'Api',
            'APILocale'         => $this->__('Not Available'),
            'ApiCallsRemaining' => $this->__('Not Available')
        );
    }

    /**
     * get Tab content title
     *
     * @return string
     */
    public function getTitle()
    {
        return Mage::helper('ddg')->__("Connector Account Information");
    }

    /**
     * get column width
     *
     * @return string
     */
    public function getColumnWidth()
    {
        return "400px;";
    }
}
