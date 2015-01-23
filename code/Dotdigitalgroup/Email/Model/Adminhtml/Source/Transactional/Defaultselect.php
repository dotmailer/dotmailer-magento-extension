<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Defaultselect
{

    /**
     * default email templates - all modules
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $idLabel = array();
        foreach (Mage_Core_Model_Email_Template::getDefaultTemplates() as $templateId => $row) {
            if (isset($row['@']) && isset($row['@']['module'])) {
                $module = $row['@']['module'];
            } else {
                $module = 'adminhtml';
            }
            $idLabel[$templateId] = Mage::helper($module)->__($row['label'] . " [module = $module]");
        }
        asort($idLabel);
        foreach ($idLabel as $templateId => $label) {
            $options[] = array('value' => $templateId, 'label' => $label);
        }

        return $options;
    }
}