<?php

/**
 * Class Dotdigitalgroup_Email_Block_Adminhtml_Automation_Grid
 */
class Dotdigitalgroup_Email_Block_Adminhtml_Automation_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Dotdigitalgroup_Email_Block_Adminhtml_Automation_Grid constructor.
     */
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
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddg_automation/automation')
            ->getCollection();
        $this->setCollection($collection);
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('DESC');

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', array(
                'header' => Mage::helper('ddg')->__('ID'),
                'index'  => 'id',
                'type'   => 'number',
                'escape' => true,
            )
        )->addColumn(
            'program_id', array(
                'header' => Mage::helper('ddg')->__('Program ID'),
                'align'  => 'center',
                'index'  => 'program_id',
                'type'   => 'number',
                'escape' => true,
            )
        )->addColumn(
            'automation_type', array(
                'header' => Mage::helper('ddg')->__('Automation Type'),
                'align'  => 'right',
                'index'  => 'automation_type',
                'type'   => 'text',
                'escape' => true
            )
        )->addColumn(
            'enrolment_status', array(
                'header'  => Mage::helper('ddg')->__('Enrollment Status'),
                'align'   => 'left',
                'index'   => 'enrolment_status',
                'type'    => 'options',
                'options' => array(
                    'pending'                   => 'Pending',
                    'suppressed'                => 'Suppressed',
                    'Active'                    => 'Active',
                    'Draft'                     => 'Draft',
                    'Deactivated'               => 'Deactivated',
                    'ReadOnly'                  => 'ReadOnly',
                    'NotAvailableInThisVersion' => 'NotAvailableInThisVersion',
                    'Failed'                    => 'Failed'
                ),
                'escape'  => true
            )
        )->addColumn(
            'email', array(
                'header' => Mage::helper('ddg')->__('Email'),
                'align'  => 'right',
                'index'  => 'email',
                'type'   => 'text',
                'escape' => true,
            )
        )->addColumn(
            'type_id', array(
                'header' => Mage::helper('ddg')->__('Type ID'),
                'align'  => 'center',
                'index'  => 'type_id',
                'type'   => 'number',
                'escape' => true,
            )
        )->addColumn(
            'message', array(
                'header' => Mage::helper('ddg')->__('Message'),
                'align'  => 'right',
                'index'  => 'message',
                'type'   => 'text',
                'escape' => true
            )
        )->addColumn(
            'created_at', array(
                'header' => Mage::helper('ddg')->__('Created at'),
                'align'  => 'center',
                'index'  => 'created_at',
                'escape' => true,
                'type'   => 'datetime'

            )
        )->addColumn(
            'updated_at', array(
                'header' => Mage::helper('ddg')->__('Updated at'),
                'align'  => 'center',
                'index'  => 'updated_at',
                'escape' => true,
                'type'   => 'datetime'
            )
        );
        if (! Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'website_id', array(
                    'header'  => Mage::helper('customer')->__('Website'),
                    'align'   => 'center',
                    'type'    => 'options',
                    'options' => Mage::getSingleton('adminhtml/system_store')
                        ->getWebsiteOptionHash(true),
                    'index'   => 'website_id',
                )
            );
        }

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
     * Prepare the grid massaction.
     *
     * @return $this|Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('automation');
        $this->getMassactionBlock()->addItem(
            'resend', array(
                'label' => Mage::helper('ddg')->__('Resend'),
                'url'   => $this->getUrl('*/*/massResend'),

            )
        );
        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label'   => Mage::helper('ddg')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
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