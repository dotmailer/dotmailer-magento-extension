<?php
class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Ssl
{
	public function toOptionArray()
	{
		return array(
			'no' => Mage::helper('ddg')->__('No SSL'),
			'tls' => Mage::helper('ddg')->__('TLS')
		);

	}
}