<?php
class Dotdigitalgroup_Email_Test_Block_Cache extends EcomDev_PHPUnit_Test_Case
{
    protected $blockAlias = 'ddg_automation/cache';
    /**
     * @var IntegerNet_DevDashboard_Block_Cache
     */
    protected $block;

    /**
     * @singleton core/session
     * @singleton adminhtml/session
     */
    public function testBlock()
    {
        $this->assertInstanceOf(IntegerNet_DevDashboard_Block_Cache::class, $this->block);
    }

    /**
     * @depends testBlock
     */
    public function testThatGridBlockIsReplaced()
    {
        $this->assertInstanceOf(IntegerNet_DevDashboard_Block_Cache_Grid::class, $this->block->getChild('grid'));
    }

    /**
     * @depends testBlock
     */
    public function testModuleNameForTranslations()
    {
        $this->assertEquals('Mage_Adminhtml', $this->block->getModuleName(), 'Module name for translations');
    }

}