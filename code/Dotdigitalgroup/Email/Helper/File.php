<?php

class Dotdigitalgroup_Email_Helper_File
    extends Dotdigitalgroup_Email_Helper_Data
{

    const FILE_FULL_ACCESS_PERMISSION = '777';

    /**
     * @var string
     */
    public $outputFolder;
    /**
     * @var string
     */
    public $outputArchiveFolder;
    /**
     * @var string
     */
    public $delimiter;
    /**
     * @var string
     */
    public $enclosure;

    /**
     * Dotdigitalgroup_Email_Helper_File constructor.
     */
    public function __construct()
    {
        $this->outputFolder = Mage::getBaseDir('var') . DS . 'export' . DS . 'email';
        $this->outputArchiveFolder = $this->outputFolder . DS . 'archive';
        $this->delimiter = ',';
        $this->enclosure = '"';
    }

    /**
     * @return string
     */
    public function getOutputFolder()
    {
        $this->pathExists($this->outputFolder);

        return $this->outputFolder;
    }

    /**
     * @return string
     */
    public function getArchiveFolder()
    {
        $this->pathExists($this->outputArchiveFolder);

        return $this->outputArchiveFolder;
    }

    /**
     * Return the full filepath.
     *
     * @param $filename
     * @return string
     */
    public function getFilePath($filename)
    {
        return $this->getOutputFolder() . DS . $filename;
    }

    /**
     * @param $filename
     */
    public function archiveCSV($filename)
    {
        $this->moveFile(
            $this->getOutputFolder(), $this->getArchiveFolder(), $filename
        );
    }

    /**
     * Moves the output file from one folder to the next.
     *
     * @param $sourceFolder
     * @param $destFolder
     * @param $filename
     */
    public function moveFile($sourceFolder, $destFolder, $filename)
    {
        // generate the full file paths
        $sourceFilepath = $sourceFolder . DS . $filename;
        $destFilepath   = $destFolder . DS . $filename;

        //@codingStandardsIgnoreStart
        // rename the file
        rename($sourceFilepath, $destFilepath);
        //@codingStandardsIgnoreEnd
    }

    /**
     * Output an array to the output file FORCING Quotes around all fields.
     *
     * @param $filepath
     * @param $csv
     */
    public function outputForceQuotesCSV($filepath, $csv)
    {
        //@codingStandardsIgnoreStart
        $fqCsv = $this->arrayToCsv($csv, chr(9), '"', true, false);
        // Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
        $fp = fopen($filepath, "a");

        // for some reason passing the preset delimiter/enclosure variables results in error
        if (fwrite($fp, $fqCsv) == 0) {
            Mage::throwException(
                Mage::helper('ddg')->__('Problem writing CSV file')
            );
        }

        fclose($fp);
        //@codingStandardsIgnoreEnd
    }

    /**
     * Output an array to the output file.
     *
     * @param $filepath
     * @param $csv
     */
    public function outputCSV($filepath, $csv)
    {
        //@codingStandardsIgnoreStart
        // Open for writing only; place the file pointer at the end of the file.
        // If the file does not exist, attempt to create it.
        $handle = fopen($filepath, "a");
        // for some reason passing the preset delimiter/enclosure variables results in error
        if (fputcsv($handle, $csv, ',', '"') == 0) {
            Mage::throwException(Mage::helper('ddg')->__('Problem writing CSV file'));
        }

        fclose($handle);
        //@codingStandardsIgnoreEnd
    }


    /**
     * If the path does not exist then create it.
     *
     * @param string $path
     */
    public function pathExists($path)
    {
        //@codingStandardsIgnoreStart
        if ( ! is_dir($path)) {
            mkdir($path, 0775, true);
        }
        //@codingStandardsIgnoreEnd
    }


    /**
     * @param array $fields
     * @param $delimiter
     * @param $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     * @return string
     */
    protected function arrayToCsv(array &$fields, $delimiter, $enclosure, $encloseAll = false, $nullToMysqlNull = false)
    {
        $delimiterEsc = preg_quote($delimiter, '/');
        $enclosureEsc = preg_quote($enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll
                || preg_match("/(?:${delimiterEsc}|${enclosureEsc}|\s)/", $field)
            ) {
                $output[] = $enclosure . str_replace(
                        $enclosure, $enclosure . $enclosure, $field
                    ) . $enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output) . "\n";
    }

    /**
     * Delete file or directory.
     *
     * @param $path Path to delete. Must contain var.
     *
     * @return bool
     */
    public function deleteDir($path)
    {
        if (strpos($path, 'var') === false) {
            return sprintf("Failed to delete directory - '%s'", $path);
        }

        $classFunc = array(__CLASS__, __FUNCTION__);

        //@codingStandardsIgnoreStart
        return is_file($path) ? unlink($path) : array_map($classFunc, glob($path . '/*')) == rmdir($path);
        //@codingStandardsIgnoreEnd
    }

    /**
     * Get website datafields for subscriber.
     *
     * @param $website
     * @return array|mixed
     */
    public function getWebsiteSalesDataFields($website)
    {
        $subscriberDataFileds = array(
            'website_name' => '',
            'store_name' => '',
            'number_of_orders' => '',
            'average_order_value' => '',
            'total_spend' => '',
            'last_order_date' => '',
            'last_increment_id' => '',
            'most_pur_category' => '',
            'most_pur_brand' => '',
            'most_freq_pur_day' => '',
            'most_freq_pur_mon' => '',
            'first_category_pur' => '',
            'last_category_pur' => '',
            'first_brand_pur' => '',
            'last_brand_pur' => ''
        );
        $store = $website->getDefaultStore();
        $mappedData = Mage::getStoreConfig(
            'connector_data_mapping/customer_data', $store
        );

        $mappedData = array_intersect_key($mappedData, $subscriberDataFileds);

        foreach ($mappedData as $key => $value) {
            if (!$value) {
                unset($mappedData[$key]);
            }
        }

        return $mappedData;
    }

    /**
     * @param $website
     * @return array|mixed
     */
    public function getWebsiteCustomerMappingDatafields($website)
    {
        $store      = $website->getDefaultStore();
        $mappedData = Mage::getStoreConfig(
            'connector_data_mapping/customer_data', $store
        );
        unset($mappedData['custom_attributes']);
        unset($mappedData['abandoned_prod_name']);

        //enterprise datafields
        if (Mage::helper('ddg')->isEnterprise()) {
            $enterpriseMapping = Mage::helper('ddg')->getEnterpriseAttributes($website);
            if ($enterpriseMapping) {
                $mappedData = array_merge($mappedData, $enterpriseMapping);
            }
        }

        $mappedRewardData = $this->getWebsiteCustomerRewardMappingDatafields($website);
        if ($mappedRewardData) {
            $mappedData = array_merge($mappedData, $mappedRewardData);
        }

        foreach ($mappedData as $key => $value) {
            if (!$value) {
                unset($mappedData[$key]);
            }
        }

        return $mappedData;
    }

    /**
     * @param $website
     * @return bool|mixed
     */
    public function getWebsiteCustomerRewardMappingDatafields($website)
    {
        $helper = Mage::helper('ddg');
        if ($helper->isSweetToothToGo($website)) {
            $store      = $website->getDefaultStore();
            $mappedData = Mage::getStoreConfig('connector_data_mapping/sweet_tooth', $store);
            unset($mappedData['active']);

            return $mappedData;
        }

        return false;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public function getPathPermission($path)
    {
        //@codingStandardsIgnoreStart
        //check for directory created before looking into permission
        if (is_dir($path)) {
            clearstatcache(null, $path);

            return decoct(fileperms($path) & 0777);
        }
        //@codingStandardsIgnoreEnd

        //the file is not created and return the passing value
        return 755;
    }
}
