<?php

class Dotdigitalgroup_Email_Adminhtml_RulesController extends Mage_Adminhtml_Controller_Action
{
    /**
     * ajax action for actions and value update without selection
     */
    public function ajaxAction()
    {
        $attribute = $this->getRequest()->getParam('attribute');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');
        if($attribute && $conditionName && $valueName){
            $type = Mage::getModel('ddg_automation/adminhtml_source_rules_type')->getInputType($attribute);
            $conditionOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_condition')->getInputTypeOptions($type);
            $response['condition'] = $this->_getOptionHtml('conditions', $conditionName, $conditionOptions);

            $elmType = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueElementType($attribute);
            if($elmType == 'select'){
                $valueOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueSelectOptions($attribute);
                $response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
            }elseif($elmType == 'text'){
                $html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName />";
                $response['cvalue'] = $html;
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
        }
    }

    /**
     * create options array from block
     *
     * @param $title
     * @param $name
     * @param $options
     * @return string
     */
    protected function _getOptionHtml($title, $name, $options)
    {
        $block = $this->getLayout()->createBlock('core/html_select');
        $block->setOptions($options)
            ->setId('')
            ->setClass('')
            ->setTitle($title)
            ->setName($name)
            ->setExtraParams('style="width:160px"');
        return $block->toHtml();
    }

    /**
     * ajax action for actions and value update with selection
     */
    public function selectedAction()
    {
        $id = $this->getRequest()->getParam('ruleid');
        $attribute = $this->getRequest()->getParam('attribute');
        $arrayKey = $this->getRequest()->getParam('arraykey');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');

	    if ($arrayKey && $id && $attribute && $conditionName && $valueName) {

            $rule = Mage::getModel('ddg_automation/rules')->load($id);
		    //rule not found
		    if (! $rule->getId()) {
			    return $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody('Rule not found!');
		    }
            $conditions = $rule->getCondition();
            $condition = $conditions[$arrayKey];
            $selectedConditions = $condition['conditions'];
            $selectedValues = $condition['cvalue'];
            $type = Mage::getModel('ddg_automation/adminhtml_source_rules_type')->getInputType($attribute);
            $conditionOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_condition')->getInputTypeOptions($type);

            $response['condition'] =
                str_replace(
                    'value="'.$selectedConditions.'"',
                    'value="'.$selectedConditions.'"'.'selected="selected"',
                    $this->_getOptionHtml('conditions', $conditionName, $conditionOptions)
                );

            $elmType = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueElementType($attribute);
            if ($elmType == 'select' or $selectedConditions == 'null') {
                $is_empty = false;

	            if ($selectedConditions == 'null')
                    $is_empty = true;
                $valueOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueSelectOptions($attribute, $is_empty);
                $response['cvalue'] =
                    str_replace(
                        'value="'.$selectedValues.'"',
                        'value="'.$selectedValues.'"'.'selected="selected"',
                        $this->_getOptionHtml('cvalue', $valueName, $valueOptions)
                    );
            } elseif ($elmType == 'text') {
                $html = "<input style='width:160px' title='cvalue' class='' id='' name='$valueName' value='$selectedValues' />";
                $response['cvalue'] = $html;
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
        }
    }

    /**
     * ajax action for value update without selection
     */
    public function valueAction()
    {
        $valueName = $this->getRequest()->getParam('value');
        $conditionValue = $this->getRequest()->getParam('condValue');;
        $attributeValue = $this->getRequest()->getParam('attributeValue');

        if($valueName && $attributeValue && $conditionValue){
            if($conditionValue == 'null'){
                $valueOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueSelectOptions($attributeValue, true);
                $response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
            }
            else{
                $elmType = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueElementType($attributeValue);
                if($elmType == 'select'){
                    $valueOptions = Mage::getModel('ddg_automation/adminhtml_source_rules_value')->getValueSelectOptions($attributeValue);
                    $response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
                }elseif($elmType == 'text'){
                    $html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName />";
                    $response['cvalue'] = $html;
                }
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('email_connector/automation_rules');
    }
}