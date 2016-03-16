<?php

class Dotdigitalgroup_Email_Model_Resource_Setup
    extends Mage_Eav_Model_Entity_Setup
{

    /**
     * This method returns true if the attribute exists.
     *
     * @param string|int $entityTypeId
     * @param string|int $attributeId
     *
     * @return bool
     */
    public function attributeExists($entityTypeId, $attributeId)
    {
        try {
            $entityTypeId = $this->getEntityTypeId($entityTypeId);
            $attributeId  = $this->getAttributeId($entityTypeId, $attributeId);

            return ! empty($attributeId);
        } catch (Exception $e) {
            return false;
        }
    }

}