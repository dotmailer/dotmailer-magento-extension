<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Backend_Template extends Mage_Core_Model_Config_Data
{
    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeDelete()
    {
        $dotTemplate = Mage::getModel('ddg_automation/template');
        //remove the mapped config for the template;
        Mage::getConfig()->deleteConfig(
            $dotTemplate->templateConfigMapping[$this->getField()],
            $this->getScope(),
            $this->getScopeId()
        );
        return parent::_beforeDelete();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        if (! $this->isValueChanged()) {
            return parent::_beforeSave();
        }

        $dotTemplate = Mage::getModel('ddg_automation/template');

        //email template is mapped
        if ($this->getValue()) {
            $templateConfigPath = $dotTemplate->templateConfigMapping[$this->getField()];

            $template = $dotTemplate->saveTemplateWithConfigPath(
                $this->getField(),
                $this->getValue(),
                $this->getScope(),
                $this->getScopeId()
            );
            //save created new email template with the default config value for template
            if ($template->getId()) {
                Mage::getConfig()->saveConfig(
                    $templateConfigPath,
                    $template->getId(),
                    $this->getScope(),
                    $this->getScopeId()
                );
            }

        } else {
            //reset core to default email template
            Mage::getConfig()->deleteConfig(
                $dotTemplate->templateConfigMapping[$this->getField()],
                $this->getScope(),
                $this->getScopeId()
            );
            //remove the config for dotmailer template
            Mage::getConfig()->deleteConfig(
                $dotTemplate->templateConfigMapping[$this->getField()],
                $this->getScope(),
                $this->getScopeId()
            );
        }

        return parent::_beforeSave();
    }
}