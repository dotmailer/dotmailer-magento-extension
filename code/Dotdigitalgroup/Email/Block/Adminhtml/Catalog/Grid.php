<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Catalog_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

        // Set some defaults for our grid
        $this->setDefaultSort('id');
        $this->setId('id');
        $this->setDefaultDir('asc');
    }

    /**
     * Collection class;
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        // This is the model we are using for the grid
        return 'ddg_automation/catalog_collection';
    }

    /**
     * Prepare the grid collection.
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        // Get and set our collection for the grid
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare the grid columns.
     *
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_id', array(
                'header' => Mage::helper('ddg')->__('Product ID'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'product_id',
                'type' => 'number',
                'escape' => true
            )
        )->addColumn(
            'processed', array(
                'header' => Mage::helper('ddg')->__(
                    'Processed'
                ),
                'align' => 'center',
                'width' => '50px',
                'index' => 'processed',
                'type' => 'options',
                'escape' => true,
                'renderer' => 'ddg_automation/adminhtml_column_renderer_imported',
                'options' => Mage::getModel(
                    'ddg_automation/adminhtml_source_catalog_processed'
                )->getOptions(),
                'filter_condition_callback' => array($this,
                    'filterCallbackBoolean')
            )
        )->addColumn(
            'last_imported_at', array(
                'header' => Mage::helper('ddg')->__(
                    'Last Imported At'
                ),
                'align' => 'center',
                'width' => '50px',
                'index' => 'last_imported_at',
                'type' => 'datetime',
                'escape' => true
            )
        )->addColumn(
            'created_at', array(
                'header' => Mage::helper('ddg')->__('Created At'),
                'width' => '50px',
                'align' => 'center',
                'index' => 'created_at',
                'type' => 'datetime',
                'escape' => true,
            )
        )->addColumn(
            'updated_at', array(
                'header' => Mage::helper('ddg')->__('Updated At'),
                'width' => '50px',
                'align' => 'center',
                'index' => 'updated_at',
                'type' => 'datetime',
                'escape' => true,
            )
        );

        return parent::_prepareColumns();
    }
    
    /**
     * Callback action for non-nullable boolean fields.
     *
     * @param $collection
     * @param $column
     */
    public function filterCallbackBoolean($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex()
            : $column->getIndex();
        $value = $column->getFilter()->getValue();
        if ($value == 0) {
            $collection->addFieldToFilter($field, 0);
        } else {
            $collection->addFieldToFilter($field, 1);
        }
    }
}