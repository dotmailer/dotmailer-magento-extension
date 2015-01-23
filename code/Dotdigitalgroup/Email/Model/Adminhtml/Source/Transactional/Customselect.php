<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Customselect
{

    /**
     * custom email templates - admin transactional emails
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = Mage::getResourceModel('core/email_template_collection')
            ->load();
        $options = $collection->toOptionArray();

        return $options;
    }
}