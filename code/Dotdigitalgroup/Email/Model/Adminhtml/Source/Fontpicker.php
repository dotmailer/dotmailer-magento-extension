<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Fontpicker
{
    /**
     * Options getter. web safe fonts
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => "Arial, Helvetica, sans-serif",
                'label' => Mage::helper('connector')->__("Arial, Helvetica")),
            array('value' => "'Arial Black', Gadget, sans-serif",
                'label' => Mage::helper('connector')->__("Arial Black, Gadget")),
            array('value' => "'Courier New', Courier, monospace",
                'label' => Mage::helper('connector')->__("Courier New, Courier")),
            array('value' => "Georgia, serif",
                'label' => Mage::helper('connector')->__("Georgia")),
            array('value' => "'MS Sans Serif', Geneva, sans-serif",
                'label' => Mage::helper('connector')->__("MS Sans Serif, Geneva")),
            array('value' => "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
                'label' => Mage::helper('connector')->__("Palatino Linotype, Book Antiqua")),
            array('value' => "Tahoma, Geneva, sans-serif",
                'label' => Mage::helper('connector')->__("Tahoma, Geneva")),
            array('value' => "'Times New Roman', Times, serif",
                'label' => Mage::helper('connector')->__("Times New Roman, Times")),
            array('value' => "'Trebuchet MS', Helvetica, sans-serif",
                'label' => Mage::helper('connector')->__("Trebuchet MS, Helvetica")),
            array('value' => "Verdana, Geneva, sans-serif",
                'label' => Mage::helper('connector')->__("Verdana, Geneva")),
        );
    }
}