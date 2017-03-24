<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Widget_Chooser_Product
    extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Dotdigitalgroup_Email_Block_Adminhtml_Widget_Chooser_Product constructor.
     * @param array $arguments
     */
    public function __construct($arguments = array())
    {
        parent::__construct($arguments);

        if ($this->getRequest()->getParam('current_grid_id')) {
            $this->setId($this->getRequest()->getParam('current_grid_id'));
        } else {
            $this->setId('skuChooserGrid_' . $this->getId());
        }

        //change to 10
        $this->setDefaultLimit(10);
        $form = $this->getJsFormObject();
        $this->setRowClickCallback("$form.chooserGridRowClick.bind($form)");
        $this->setCheckboxCheckCallback(
            "$form.chooserGridCheckboxCheck.bind($form)"
        );
        $this->setRowInitCallback("$form.chooserGridRowInit.bind($form)");
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Retrieve quote store object
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * @param $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $selected = $this->_getSelectedProducts();
            if (empty($selected)) {
                $selected = '';
            }

            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter(
                    'entity_id', array('in' => $selected)
                );
            } else {
                $this->getCollection()->addFieldToFilter(
                    'entity_id', array('nin' => $selected)
                );
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Prepare Catalog Product Collection for attribute SKU in Promo Conditions SKU chooser
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(0)
            ->addAttributeToSelect('name', 'type_id', 'attribute_set_id');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Define Cooser Grid Columns and filters
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_products', array(
                'header_css_class' => 'a-center',
                'type'             => 'checkbox',
                'name'             => 'in_products',
                'values'           => $this->_getSelectedProducts(),
                'align'            => 'center',
                'index'            => 'entity_id',
                'use_index'        => true,
            )
        );

        $this->addColumn(
            'entity_id', array(
                'header'   => Mage::helper('sales')->__('ID'),
                'sortable' => true,
                'width'    => '60px',
                'index'    => 'entity_id'
            )
        );

        $this->addColumn(
            'type',
            array(
                'header'  => Mage::helper('catalog')->__('Type'),
                'width'   => '60px',
                'index'   => 'type_id',
                'type'    => 'options',
                'options' => Mage::getSingleton('catalog/product_type')
                    ->getOptionArray(),
            )
        );

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(
                Mage::getModel('catalog/product')->getResource()->getTypeId()
            )
            ->load()
            ->toOptionHash();

        $this->addColumn(
            'set_name',
            array(
                'header'  => Mage::helper('catalog')->__('Attrib. Set Name'),
                'width'   => '100px',
                'index'   => 'attribute_set_id',
                'type'    => 'options',
                'options' => $sets,
            )
        );

        $this->addColumn(
            'chooser_sku', array(
                'header' => Mage::helper('sales')->__('SKU'),
                'name'   => 'chooser_sku',
                'width'  => '80px',
                'index'  => 'sku'
            )
        );
        $this->addColumn(
            'chooser_name', array(
                'header' => Mage::helper('sales')->__('Product Name'),
                'name'   => 'chooser_name',
                'index'  => 'name'
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            '*/*/product', array(
                '_current'        => true,
                'current_grid_id' => $this->getId(),
                'collapse'        => null
            )
        );
    }

    /**
     * @return mixed
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('selected', array());

        return $products;
    }

    /**
     * Set default limit.
     *
     * @param $limit
     *
     * @return $this
     */
    public function setDefaultLimit($limit)
    {
        $this->_defaultLimit = $limit;

        return $this;
    }
}

