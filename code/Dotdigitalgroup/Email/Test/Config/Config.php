<?php
class IntegerNet_DevDashboard_Test_Config_Config extends EcomDev_PHPUnit_Test_Case_Config
{
    const MODULE_NAME = 'Dotdigitalgroup_Email';

    const LAYOUT_FILENAME = 'email.xml';

    public function testThatModuleIsEnabled()
    {
        $this->assertModuleIsActive(self::MODULE_NAME, self::MODULE_NAME);
    }
    public function testThatRouteIsDefined()
    {
        $this->assertRouteModule('adminhtml', self::MODULE_NAME . '_Adminhtml', EcomDev_PHPUnit_Model_App::AREA_ADMIN);
    }
    public function testThatLayoutIsDefined()
    {
        $this->assertLayoutFileExists(EcomDev_PHPUnit_Model_App::AREA_ADMINHTML, self::LAYOUT_FILENAME);
    }
}