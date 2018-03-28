<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Email_Grid extends Mage_Adminhtml_Block_System_Email_Template_Grid
{
    /**
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('template_id',
            array(
                  'header'=>Mage::helper('adminhtml')->__('ID'),
                  'index'=>'template_id'
            )
        );

        $this->addColumn('code',
            array(
                'header'=>Mage::helper('adminhtml')->__('Template Name'),
                'index'=>'template_code'
        ));

        $this->addColumn('added_at',
            array(
                'header'=>Mage::helper('adminhtml')->__('Date Added'),
                'index'=>'added_at',
                'gmtoffset' => true,
                'type'=>'datetime'
        ));

        $this->addColumn('modified_at',
            array(
                'header'=>Mage::helper('adminhtml')->__('Date Updated'),
                'index'=>'modified_at',
                'gmtoffset' => true,
                'type'=>'datetime'
        ));

        $this->addColumn('subject',
            array(
                'header'=>Mage::helper('adminhtml')->__('Subject'),
                'index'=>'template_subject',
                'renderer' => 'ddg_automation/adminhtml_column_renderer_template',
        ));
        /*
        $this->addColumn('sender',
            array(
                'header'=>Mage::helper('adminhtml')->__('Sender'),
                'index'=>'template_sender_email',
                'renderer' => 'adminhtml/system_email_template_grid_renderer_sender'
        ));
        */
        $this->addColumn('type',
            array(
                'header'=>Mage::helper('adminhtml')->__('Template Type'),
                'index'=>'template_type',
                'filter' => 'adminhtml/system_email_template_grid_filter_type',
                'renderer' => 'adminhtml/system_email_template_grid_renderer_type'
        ));

        $this->addColumn('action',
            array(
                'header'	=> Mage::helper('adminhtml')->__('Action'),
                'index'		=> 'template_id',
                'sortable'  => false,
                'filter' 	=> false,
                'width'		=> '100px',
                'renderer'  => 'adminhtml/system_email_template_grid_renderer_action'
        ));

        return $this;
    }
}

