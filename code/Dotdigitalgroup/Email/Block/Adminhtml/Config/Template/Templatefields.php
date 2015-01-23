<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Config_Template_Templatefields  extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	/**
	 * Templates
	 *
	 */
	protected $_getTemplateRenderer;

	/**
	 * Datafields
	 */
	protected $_getDatafieldRenderer;

	/**
	 * Send Type
	 */
	protected $_getSendtypeRenderer;

    /**
     * From address
     */
    protected $_getFromadddressRenderer;

	/**
	 * Construct.
	 */
	public function __construct()
	{
		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('adminhtml')->__('Map Template');
		parent::__construct();

	}

	protected function _prepareToRender()
	{
		$this->_getDatafieldRenderer = null;
		$this->_getTemplateRenderer = null;
		$this->_getSendtypeRenderer = null;
		$this->addColumn('template',
			array(
				'label' => Mage::helper('adminhtml')->__('Magento Template'),
				'style' => 'width:120px',
			)
		);
		$this->addColumn('sendtype', array(
				'label' => Mage::helper('adminhtml')->__('Send Type'),
				'style' => 'width:120px',
			)
		);
		$this->addColumn('datafield', array(
				'label' => Mage::helper('adminhtml')->__("Campaign Template <br> (Need For Design + Send)"),
				'style' => 'width:120px',
			)
		);
        $this->addColumn('fromaddress', array(
                'label' => Mage::helper('adminhtml')->__("From Address"),
                'style' => 'width:120px',
            )
        );
        $this->addColumn('attachmentid', array(
                'label' => Mage::helper('adminhtml')->__("Attachment Id"),
                'style' => 'width:120px',
            )
        );
	}

	protected function _renderCellTemplate($columnName)
	{
		$inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
		if ($columnName=="template") {
			return $this->_getTemplateRenderer()
			            ->setName($inputName)
			            ->setTitle($columnName)
			            ->setExtraParams('style="width:160px"')
			            ->setOptions(
				            $this->getElement()->getValues()
			            )
			            ->toHtml();
		} elseif ($columnName == "sendtype") {
			return $this->_getSendtypeRenderer()
			            ->setName($inputName)
			            ->setTitle($columnName)
			            ->setExtraParams('style="width:160px"')
			            ->setOptions(Mage::getModel('email_connector/adminhtml_source_transactional_sendtype')->toOptionArray())
			            ->toHtml();
		} elseif ($columnName == "datafield") {
			return $this->_getDatafieldRenderer()
			            ->setName($inputName)
			            ->setTitle($columnName)
			            ->setExtraParams('style="width:160px"')
			            ->setOptions(Mage::getModel('email_connector/adminhtml_source_transactional_campaigns')->toOptionArray())
			            ->toHtml();
		} elseif ($columnName == "fromaddress") {
            return $this->_getFromadddressRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(Mage::getModel('email_connector/adminhtml_source_transactional_fromaddress')->toOptionArray())
                ->toHtml();
        }

		return parent::_renderCellTemplate($columnName);
	}

	/**
	 * Assign extra parameters to row
	 *
	 * @param Varien_Object $row
	 */
	protected function _prepareArrayRow(Varien_Object $row)
	{

		$row->setData(
			'option_extra_attr_' . $this->_getTemplateRenderer()->calcOptionHash($row->getData('template')),
			'selected="selected"'
		);

		$row->setData(
			'option_extra_attr_' . $this->_getSendtypeRenderer()->calcOptionHash($row->getData('sendtype')),
			'selected="selected"'
		);

		$row->setData(
			'option_extra_attr_' . $this->_getDatafieldRenderer()->calcOptionHash($row->getData('datafield')),
			'selected="selected"'
		);

        $row->setData(
            'option_extra_attr_' . $this->_getFromadddressRenderer()->calcOptionHash($row->getData('fromaddress')),
            'selected="selected"'
        );
	}

	protected function _getTemplateRenderer()
	{
		if (!$this->_getTemplateRenderer) {
			$this->_getTemplateRenderer = $this->getLayout()
			                                   ->createBlock('email_connector/adminhtml_config_select')
			                                   ->setIsRenderToJsTemplate(true);
		}
		return $this->_getTemplateRenderer;
	}

	protected function _getDatafieldRenderer()
	{
		if (!$this->_getDatafieldRenderer) {
			$this->_getDatafieldRenderer = $this->getLayout()
			                                    ->createBlock('email_connector/adminhtml_config_select')
			                                    ->setIsRenderToJsTemplate(true);
		}
		return $this->_getDatafieldRenderer;
	}

	protected function _getSendtypeRenderer()
	{
		if (!$this->_getSendtypeRenderer) {
			$this->_getSendtypeRenderer = $this->getLayout()
			                                   ->createBlock('email_connector/adminhtml_config_select')
			                                   ->setIsRenderToJsTemplate(true);
		}
		return $this->_getSendtypeRenderer;
	}

    protected function _getFromadddressRenderer()
    {
        if (!$this->_getFromadddressRenderer) {
            $this->_getFromadddressRenderer = $this->getLayout()
                ->createBlock('email_connector/adminhtml_config_select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getFromadddressRenderer;
    }

	public function _toHtml()
	{
		if(count($this->getElement()->getValues())){
			return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml();
		}
		else {
			return "<p class='notice'>". Mage::helper('adminhtml')->__("There are no email templates to map.") . "</p>";
		}

	}

}
