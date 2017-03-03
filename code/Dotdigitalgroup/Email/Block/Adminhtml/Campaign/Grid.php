<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Campaign_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('id');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     *  Prepare grid collection object.
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddg_automation/campaign')->getCollection(
        );
        $this->setCollection($collection);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', array(
                'header'   => Mage::helper('ddg')->__('Campaign ID'),
                'width'    => '20px',
                'index'    => 'campaign_id',
                'type'     => 'number',
                'truncate' => 50,
                'escape'   => true
            )
        )->addColumn(
            'customer_id', array(
                'header' => Mage::helper('ddg')->__('Customer ID'),
                'align'  => 'left',
                'width'  => '50px',
                'index'  => 'customer_id',
                'type'   => 'number',
                'escape' => true
            )
        )->addColumn(
            'email', array(
                'header' => Mage::helper('ddg')->__('Email'),
                'align'  => 'left',
                'width'  => '50px',
                'index'  => 'email',
                'type'   => 'text',
                'escape' => true
            )
        )->addColumn(
            'send_status', array(
                'header'                    => Mage::helper('ddg')->__(
                    'Send Status'
                ),
                'align'                     => 'center',
                'width'                     => '20px',
                'index' => 'send_status',
                'escape'                    => true,
                'type'                      => 'options',
                'options'                   => array(
                    Dotdigitalgroup_Email_Model_Campaign::PENDING => 'Pending',
                    Dotdigitalgroup_Email_Model_Campaign::PROCESSING => 'Processing',
                    Dotdigitalgroup_Email_Model_Campaign::SENT => 'Sent',
                    Dotdigitalgroup_Email_Model_Campaign::FAILED => 'Failed'
                )
            )
        )->addColumn(
            'send_id', array(
                'header' => Mage::helper('ddg')->__('Send Id'),
                'align' => 'left',
                'width' => '300px',
                'index' => 'send_id',
                'type' => 'text',
                'escape' => true
            )
        )->addColumn(
            'message', array(
                'header' => Mage::helper('ddg')->__('Send Message'),
                'align'  => 'left',
                'width'  => '300px',
                'index'  => 'message',
                'type'   => 'text',
                'escape' => true
            )
        )->addColumn(
            'event_name', array(
                'header' => Mage::helper('ddg')->__('Event Name'),
                'align'  => 'left',
                'index'  => 'event_name',
                'width'  => '100px',
                'type'   => 'string',
                'escape' => true
            )
        )->addColumn(
            'quote_id', array(
                'header' => Mage::helper('ddg')->__('Quote Id'),
                'align'  => 'left',
                'width'  => '50px',
                'index'  => 'quote_id',
                'type'   => 'number',
                'escape' => true
            )
        )->addColumn(
            'sent_at', array(
                'header' => Mage::helper('ddg')->__('Sent At'),
                'align'  => 'center',
                'width'  => '100px',
                'index'  => 'sent_at',
                'type'   => 'datetime',
                'escape' => true
            )
        )->addColumn(
            'created_at', array(
                'header' => Mage::helper('ddg')->__('Created At'),
                'align'  => 'center',
                'width'  => '100px',
                'index'  => 'created_at',
                'type'   => 'datetime',
                'escape' => true
            )
        )->addColumn(
            'updated_at', array(
                'header' => Mage::helper('ddg')->__('Updated At'),
                'align'  => 'center',
                'width'  => '100px',
                'index'  => 'updated_at',
                'type'   => 'datetime',
                'escape' => true
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id', array(
                    'header'  => Mage::helper('customer')->__('Store'),
                    'align'   => 'center',
                    'width'   => '80px',
                    'type'    => 'options',
                    'options' => Mage::getSingleton('adminhtml/system_store')
                        ->getStoreOptionHash(true),
                    'index'   => 'store_id'
                )
            );
        }

        $this->addExportType('*/*/exportCsv', Mage::helper('ddg')->__('CSV'));

        return parent::_prepareColumns();
    }

    /**
     * Get the store selected.
     *
     * @return Mage_Core_Model_Store
     * @throws Exception
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);

        return Mage::app()->getStore($storeId);
    }


    /**
     * @return $this|Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('campaign');
        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label'   => Mage::helper('ddg')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('ddg')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'resend', array('label' => Mage::helper('ddg')->__('Resend'),
                            'url'   => $this->getUrl('*/*/massResend'))
        );

        return $this;
    }

    /**
     * Grid selected url.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Custom callback action for the campaign.
     *
     * @param $collection
     * @param $column
     */
    public function filterCallbackContact($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex()
            : $column->getIndex();
        $value = $column->getFilter()->getValue();

        if ($value == 'null') {
            $collection->addFieldToFilter($field, array('null' => true));
        } else {
            $collection->addFieldToFilter($field, array('notnull' => true));
        }
    }

}