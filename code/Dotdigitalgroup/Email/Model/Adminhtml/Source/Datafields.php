<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Datafields
{

    /**
     *  Datafields option.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = array();
        $helper = Mage::helper('ddg');
        //default data option
        $fields[] = array(
            'value' => 0,
            'label' => Mage::helper('ddg')->__('-- Please Select --')
        );

        $website = Mage::app()->getRequest()->getParam('website', 0);
        $client  = $helper->getWebsiteApiClient($website);

        //get datafields options
        if ($helper->isEnabled($website) && $client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            $savedDatafields = Mage::registry('datafields');

            //get saved datafileds from registry
            if ($savedDatafields) {
                $datafields = $savedDatafields;
            } else {
                //grab the datafields request and save to register
                $datafields = $client->getDataFields();
                Mage::register('datafields', $datafields);
            }

            //set the api error message for the first option
            if (isset($datafields->message)) {
                //message
                $fields[] = array(
                    'value' => 0,
                    'label' => Mage::helper('ddg')->__($datafields->message)
                );
            } elseif (is_array($datafields)) {
                //loop for all datafields option
                foreach ($datafields as $datafield) {
                    if (isset($datafield->name)) {
                        $fields[] = array(
                            'value' => $datafield->name,
                            'label' => Mage::helper('ddg')->__($datafield->name)
                        );
                    }
                }
            }
        }

        return $fields;
    }
}