<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Abandoned_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Dotdigitalgroup_Email_Block_Adminhtml_Abandoned_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('id');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddg_automation/abandoned')
            ->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', array(
                'header' => Mage::helper('ddg')->__('ID'),
                'index' => 'id',
                'type' => 'number',
                'escape' => true,
            )
        )->addColumn(
            'quote_id', array(
                'header' => Mage::helper('ddg')->__('Quote ID'),
                'width' => '20px',
                'index' => 'quote_id',
                'type' => 'number',
                'escape' => true,
            )
        )->addColumn(
            'customer_id', array(
                'header' => Mage::helper('ddg')->__('Customer ID'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'customer_id',
                'type' => 'number',
                'escape' => true
            )
        )->addColumn(
            'email', array(
                'header' => Mage::helper('ddg')->__('Email'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'email',
                'type' => 'text',
                'escape' => true
            )
        )->addColumn(
            'is_active', array(
                'header' => Mage::helper('ddg')->__('Is Active'),
                'align' => 'left',
                'index' => 'is_active',
                'type' => 'number',
                'escape' => true
            )
        )->addColumn(
            'quote_updated_at', array(
                'header' => Mage::helper('ddg')->__('Quote updated at'),
                'align' => 'right',
                'index' => 'quote_updated_at',
                'type' => 'datetime'
            )
        )->addColumn(
            'abandoned_cart_number', array(
                'header' => Mage::helper('ddg')->__('Abandoned cart number'),
                'align' => 'left',
                'index' => 'abandoned_cart_number',
                'type' => 'number',
                'escape' => true
            )
        )->addColumn(
            'items_count', array(
                'header' => Mage::helper('ddg')->__('Items count'),
                'align' => 'left',
                'index' => 'items_count',
                'type' => 'number',
                'escape' => true
            )
        )->addColumn(
            'items_ids', array(
                'header' => Mage::helper('ddg')->__('Item ids'),
                'align' => 'left',
                'index' => 'items_ids',
                'type' => 'text',
                'escape' => true
            )
        )->addColumn(
            'created_at', array(
                'header' => Mage::helper('ddg')->__('Created At'),
                'align' => 'right',
                'index' => 'created_at',
                'type' => 'datetime'
            )

        )->addColumn(
            'updated_at', array(
                'header' => Mage::helper('ddg')->__('Updated at'),
                'align' => 'right',
                'index' => 'updated_at',
                'type' => 'datetime'
            )
        )->addColumn(
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

        $this->addExportType('*/*/exportCsv', Mage::helper('ddg')->__('CSV'));

        return parent::_prepareColumns();
    }

    /**
     * Get the store.
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
     * Prepare the grid mass action.
     *
     * @return $this|Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('abandoned');
        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label' => Mage::helper('ddg')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('ddg')->__('Are you sure?'))
        );

        return $this;
    }

    /**
     * Grid url.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

}