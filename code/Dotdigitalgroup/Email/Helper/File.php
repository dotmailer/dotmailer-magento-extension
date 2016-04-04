<?php

class Dotdigitalgroup_Email_Helper_File
    extends Dotdigitalgroup_Email_Helper_Data
{

    const FILE_FULL_ACCESS_PERMISSION = '777';

    /**
     * Location of files we are building
     */

    protected $_outputFolder; // set in _construct
    protected $_outputArchiveFolder; // set in _construct

    public $delimiter; // set in _construct
    public $enclosure; // set in _construct

    public function __construct()
    {
        $this->_outputFolder        = Mage::getBaseDir('var') . DS . 'export'
            . DS . 'email';
        $this->_outputArchiveFolder = $this->_outputFolder . DS . 'archive';

        $this->delimiter = ','; // tab character
        $this->enclosure = '"';
    } // end


    public function getOutputFolder()
    {
        $this->pathExists($this->_outputFolder);

        return $this->_outputFolder;
    } // end

    public function getArchiveFolder()
    {
        $this->pathExists($this->_outputArchiveFolder);

        return $this->_outputArchiveFolder;
    } // end

    /* Return the full filepath */
    public function getFilePath($filename)
    {
        return $this->getOutputFolder() . DS . $filename;
    }

    public function archiveCSV($filename)
    {
        $this->moveFile(
            $this->getOutputFolder(), $this->getArchiveFolder(), $filename
        );
    }

    /**
     * Moves the output file from one folder to the next
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

        // rename the file
        rename($sourceFilepath, $destFilepath);

    } // end


    /**
     * Output an array to the output file FORCING Quotes around all fields
     *
     * @param $filepath
     * @param $csv
     */
    public function outputForceQuotesCSV($filepath, $csv)
    {
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

    } // end


    /**
     * Output an array to the output file
     *
     * @param $filepath
     * @param $csv
     */
    public function outputCSV($filepath, $csv)
    {
        // Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
        $handle = fopen($filepath, "a");

        // for some reason passing the preset delimiter/enclosure variables results in error
        //$this->delimiter $this->enclosure
        if (fputcsv($handle, $csv, ',', '"') == 0) {
            Mage::throwException(
                Mage::helper('ddg')->__('Problem writing CSV file')
            );
        }

        fclose($handle);

    } // end


    /**
     * If the path does not exist then create it
     *
     * @param string $path
     */
    public function pathExists($path)
    {
        if ( ! is_dir($path)) {
            mkdir($path, 0775, true);
        } // end
    }


    protected function arrayToCsv(array &$fields, $delimiter, $enclosure,
        $encloseAll = false, $nullToMysqlNull = false
    ) {
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
                || preg_match(
                    "/(?:${delimiterEsc}|${enclosureEsc}|\s)/", $field
                )
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
     * Delete file or directory
     *
     * @param $path
     *
     * @return bool
     */
    public function deleteDir($path)
    {
        $class_func = array(__CLASS__, __FUNCTION__);

        return is_file($path)
            ?
            unlink($path)
            :
            array_map($class_func, glob($path . '/*')) == rmdir($path);
    }


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

            $enterpriseMapping = Mage::helper('ddg')->getEnterpriseAttributes(
                $website
            );
            if ($enterpriseMapping) {
                $mappedData = array_merge($mappedData, $enterpriseMapping);
            }
        }

        $mappedRewardData = $this->getWebsiteCustomerRewardMappingDatafields(
            $website
        );
        if ($mappedRewardData) {
            $mappedData = array_merge($mappedData, $mappedRewardData);
        }

        foreach ($mappedData as $key => $value) {
            if ( ! $value) {
                unset($mappedData[$key]);
            }
        }

        return $mappedData;
    }

    public function getWebsiteCustomerRewardMappingDatafields($website)
    {
        $helper = Mage::helper('ddg');
        if ($helper->isSweetToothToGo($website)) {
            $store      = $website->getDefaultStore();
            $mappedData = Mage::getStoreConfig(
                'connector_data_mapping/sweet_tooth', $store
            );
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

        //check for directory created before looking into permission
        if (is_dir($path)) {
            clearstatcache(null, $path);

            return decoct(fileperms($path) & 0777);
        }

        //the file is not created and return the passing value
        return 755;
    }
}
