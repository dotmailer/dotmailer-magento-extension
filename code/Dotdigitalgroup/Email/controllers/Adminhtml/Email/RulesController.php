<?php

class Dotdigitalgroup_Email_Adminhtml_Email_RulesController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Constructor - set the used module name.
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Dotdigitalgroup_Email');
    }

    /**
     * Init action.
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('email_connector')
            ->_addBreadcrumb(
                Mage::helper('ddg')->__('Exclusion Rules'),
                Mage::helper('ddg')->__('Exclusion Rules')
            );

        return $this;
    }

    /**
     * Index action.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->getLayout()->getBlock('head')->setTitle(
            'Connector Exclusion Rule(s)'
        );
        $this->_addBreadcrumb(
            Mage::helper('ddg')->__('Exclusion Rules'),
            Mage::helper('ddg')->__('Exclusion Rules')
        );
        $this->renderLayout();
    }

    /**
     * Action for new rule page.
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Action for edit rule page.
     */
    public function editAction()
    {
        $id         = $this->getRequest()->getParam('id');
        $emailRules = Mage::getModel('ddg_automation/rules');

        if ($id) {
            $emailRules->load($id);

            if (!$emailRules->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('adminhtml')->__('This rule no longer exists.')
                );
                $this->_redirect('*/*');

                return;
            }
        }

        $this->_title(
            $emailRules->getId()
                ? $emailRules->getName()
                : $this->__(
                    'New Rule'
                )
        );

        // set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getPageData(true);
        if (!empty($data)) {
            $emailRules->addData($data);
        }


        Mage::register('current_ddg_rule', $emailRules);

        $this->_initAction()->getLayout()->getBlock('ddg_rule_edit')
            ->setData('action', $this->getUrl('*/*/save'));

        $this->_addBreadcrumb(
            $id ? Mage::helper('ddg')->__('Edit Rule')
                : Mage::helper('ddg')->__('New Rule'),
            $id ? Mage::helper('ddg')->__('Edit Rule')
                : Mage::helper('ddg')->__('New Rule')
        )
            ->renderLayout();

    }

    /**
     * Action for save rule data.
     * @codingStandardsIgnoreStart
     */
    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            try {
                $model = Mage::getModel('ddg_automation/rules');
                $data  = $this->getRequest()->getPost();
                $id    = $this->getRequest()->getParam('id');

                if ($data['website_ids']) {
                    foreach ($data['website_ids'] as $websiteId) {
                        $result = $model->checkWebsiteBeforeSave(
                            $websiteId, $data['type'], $id
                        );
                        if (!$result) {
                            $websiteName = Mage::app()->getWebsite($websiteId)
                                ->getName();
                            $this->_getSession()->addError(
                                Mage::helper('adminhtml')->__(
                                    'Rule already exist for website '
                                    . $websiteName
                                    . '. You can only have one rule per website.'
                                )
                            );
                            $this->_redirect(
                                '*/*/edit',
                                array('id' => $this->getRequest()->getParam(
                                    'id'
                                ))
                            );

                            return;
                        }
                    }
                }

                $model->load($id);
                if ($id != $model->getId()) {
                    Mage::throwException(
                        Mage::helper('ddg')->__('Wrong rule specified.')
                    );
                }


                foreach ($data as $key => $value) {
                    if ($key != 'form_key') {
                        if ($key == 'condition') {
                            if (is_array($value)) {
                                unset($value['__empty']);
                            }
                        }

                        $model->setData($key, $value);
                    }
                }

                $this->_getSession()->setPageData($model->getData());

                $model->save();
                $this->_getSession()->addSuccess(
                    Mage::helper('adminhtml')->__('The rule has been saved.')
                );
                $this->_getSession()->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect(
                        '*/*/edit', array('id' => $model->getId())
                    );

                    return;
                }

                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('*/*/edit', array('id' => $id));
                } else {
                    $this->_redirect('*/*/new');
                }

                return;
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('adminhtml')->__(
                        'An error occurred while saving the rule data. Please review the log and try again.'
                    )
                );
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                $this->_redirect(
                    '*/*/edit',
                    array('id' => $this->getRequest()->getParam('id'))
                );

                return;
            }
        }
        //@codingStandardsIgnoreEnd
        $this->_redirect('*/*/');
    }

    /**
     * Action for delete button.
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('ddg_automation/rules');
                $model->setId($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('The rule has been deleted.')
                );
                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('adminhtml')->__(
                        'An error occurred while deleting the rule. Please review the log and try again.'
                    )
                );
                Mage::logException($e);
                $this->_redirect(
                    '*/*/edit',
                    array('id' => $this->getRequest()->getParam('id'))
                );

                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('adminhtml')->__('Unable to find a rule to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * Main page/grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'email_connector/automation_rules'
        );
    }

}
