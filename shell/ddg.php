<?php

require_once 'abstract.php';

class Mage_Shell_Ddg extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        $_SESSION = array();
        /** @var Dotdigitalgroup_Email_Helper_Setup $setupHelper */
        $setupHelper = Mage::helper('ddg/setup');

        if ($this->getArg('enable-skip-migrate-data-flag')) {
            $setupHelper->setSkipMigrateDataFlag(1);
            echo 'Done';
        } elseif ($this->getArg('disable-skip-migrate-data-flag')) {
            $setupHelper->setSkipMigrateDataFlag(0);
            echo 'Done';
        } elseif ($this->getArg('status-skip-migrate-data-flag')) {
            echo $this->getSkipMigrationDataValueAsString($setupHelper);
        } elseif ($this->getArg('set-query-batch-size')) {
            $size = $this->getArg('set-query-batch-size');
            if ($size > 1) {
                $setupHelper->setBatchSize($size);
                echo "Done";
            } else {
                echo "Please specify a number greater than 1";
            }
        } elseif ($this->getArg('show-query-batch-size')) {
            echo $setupHelper->getBatchSize();
        } elseif ($size = $this->getArg('migrate-data')) {
            $batchSize = 0;
            if ($size > 1) {
                $setupHelper->setBatchSize($size);
                $batchSize = $size;
            }
            $setupHelper->populateAllEmailTables($batchSize)
                ->saveConfigurationsInConfigTable()
                ->encryptApiPasswordAndUserToken()
                ->saveAllowNonSubscriberConfig();
            echo 'Done';
        } else {
            echo $this->usageHelp();
        }
        echo "\n";
    }

    /**
     * @param Dotdigitalgroup_Email_Helper_Setup $setupHelper
     * @return string
     */
    private function getSkipMigrationDataValueAsString($setupHelper)
    {
        $value =  $setupHelper->skipMigrateData() ? 'Enable' : 'Disable';
        return "'skip migrate data flag' is $value";
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ddg.php -- [options]

  --enable-skip-migrate-data-flag       Enable 'skip migrate data flag'
  --disable-skip-migrate-data-flag      Disable 'skip migrate data flag'
  --status-skip-migrate-data-flag       Show 'skip migrate data flag' status
  --set-query-batch-size <batchSize>    Set query batch size for migrating data.
  --show-query-batch-size               Show query batch size for migrating data.
  --migrate-data <batchSize>            Migrate install data to all tables. <batchSize> is optional here.
  help                                  This help
  
  <batchSize> Specify batch size as number greater than 1

USAGE;
    }
}

$shell = new Mage_Shell_Ddg();
$shell->run();
