<?php

class Dotdigitalgroup_Email_Model_Resource_Segment
    extends Enterprise_CustomerSegment_Model_Resource_Segment
{

    /**
     * @param $segment
     * @param $websiteId
     * @param $select
     * @return $this
     * @throws Exception
     */
    public function saveCustomersFromSelect($segment, $websiteId, $select)
    {
        $customerTable = $this->getTable('enterprise_customersegment/customer');
        $adapter       = $this->_getWriteAdapter();
        $segmentId     = $segment->getId();
        //@codingStandardsIgnoreStart
        $now           = $this->formatDate(time());
        //@codingStandardsIgnoreEnd

        $data  = array();
        $count = 0;
        $stmt  = $adapter->query($select);
        $adapter->beginTransaction();

        try {
            while ($row = $stmt->fetch()) {
                $data[] = array(
                    'segment_id'   => $segmentId,
                    'customer_id'  => $row['entity_id'],
                    'website_id'   => $row['website_id'],
                    'added_date'   => $now,
                    'updated_date' => $now,
                );

                /**
                 * trigger the contact segment ids to update
                 */
                $this->updateContactSegmentIds(
                    $row['entity_id'], $segmentId, $websiteId
                );
                $count++;
                if (($count % 1000) == 0) {
                    $adapter->insertMultiple($customerTable, $data);
                    $data = array();
                }
            }

            if (!empty($data)) {
                $adapter->insertMultiple($customerTable, $data);
            }
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        $adapter->commit();

        return $this;
    }

    /**
     * In case for old version that is still using this method.
     * @param $segment
     * @param $select
     * @return $this
     */
    public function saveSegmentCustomersFromSelect($segment, $select)
    {
        $table     = $this->getTable('enterprise_customersegment/customer');
        $adapter   = $this->_getWriteAdapter();
        $segmentId = $segment->getId();
        //@codingStandardsIgnoreStart
        $now       = $this->formatDate(time());
        //@codingStandardsIgnoreEnd

        $adapter->delete($table, $adapter->quoteInto('segment_id=?', $segmentId));

        $data  = array();
        $count = 0;
        $stmt  = $adapter->query($select);
        while ($row = $stmt->fetch()) {
            $data[] = array(
                'segment_id'   => $segmentId,
                'customer_id'  => $row['entity_id'],
                'added_date'   => $now,
                'updated_date' => $now,
            );
            /**
             * trigger the contact segment ids to update
             */
            $this->updateContactSegmentIds($row['entity_id'], $segmentId);
            $count++;
            if ($count > 1000) {
                $count = 0;
                $adapter->insertMultiple($table, $data);
                $data = array();
            }
        }

        if ($count > 0) {
            $adapter->insertMultiple($table, $data);
        }

        return $this;
    }

    /**
     * @param $customerId
     * @param $segmentId
     * @param int $websiteId
     */
    protected function updateContactSegmentIds($customerId, $segmentId, $websiteId = 0)
    {
        $collection = Mage::getModel('ddg_automation/contact')->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
        if ($websiteId) {
            $collection->addFieldToFilter('website_id', $websiteId);
        }

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit(1);
        $contact  = $collection->getFirstItem();
        //@codingStandardsIgnoreEnd

        $existing = $contact->getSegmentIds();

        $existing = explode(',', $existing);
        //no segments found set current segment
        if (!in_array($segmentId, $existing)) {
            //for the existing
            if (count($existing) == 1) {
                $existing = $segmentId;
            } else {
                $existing[] = $segmentId;
                $existing   = implode(',', $existing);
            }
        }

        //update new segmets and mark for import
        $contact->setSegmentIds($existing)
            ->setEmailImported()
            ->save();
    }

    /**
     * @param $segment
     * @return $this
     */
    public function deleteSegmentCustomers($segment)
    {
        $this->_getWriteAdapter()->delete(
            $this->getTable('enterprise_customersegment/customer'),
            array('segment_id=?' => $segment->getId())
        );

        /**
         * Trigger the delete for contact segment ids.
         */
        $this->deleteSegmentContacts($segment->getId());

        return $this;
    }

    /**
     * @param $segment
     */
    protected function deleteSegmentContacts($segment)
    {
        $contacts = Mage::getModel('ddg_automation/contact')->getCollection()
            ->addFieldToFilter(
                'segment_ids', array('finset' => array($segment))
            );
        foreach ($contacts as $contact) {
            //segments found for contact
            $segments = explode(',', $contact->getSegmentIds());
            foreach ($segments as $key => $one) {
                if ($segment == $one) {
                    unset($segments[$key]);
                }
            }

            //@codingStandardsIgnoreStart
            //save the comma separated values
            if (count($segments) == 1) {
                $segments = $segments[0];
            } else {
                $segments = implode(',', $segments);
            }

            //save updated segments for contact
            $contact->setSegmentIds($segments)
                ->setEmailImported()
                ->save();
            //@codingStandardsIgnoreEnd
        }
    }
}
