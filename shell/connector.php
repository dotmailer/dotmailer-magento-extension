<?php

require_once 'abstract.php';

class Dotdigitalgroup_Email_Shell_Connector extends Mage_Shell_Abstract
{

    /**
     * Run script execution
     *
     * @return void
     */
    public function run()
    {
        $action = $this->getArg('action');


	    if (empty($action)) {
            echo $this->usageHelp();
        } else {
            $actionMethodName = $action . 'Action';
            if (method_exists($this, $actionMethodName)) {
                $this->$actionMethodName();
            } else {
                echo "Action $action not found!\n";
                echo $this->usageHelp();
                exit(1);
            }
        }
    }


    /**
     * Run lost baskets with options :
     *  all, customers , guests
     *
     *
     * @return void
     */
    public function basketsAction()
    {
        $mode = $this->getArg('mode');
	    $available = array('all', 'customers', 'guests');

        if (empty($mode)) {

            echo "\nNo mode found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

	    if (in_array($mode, $available)) {
		    Mage::getModel('ddg_automation/sales_quote')->proccessAbandonedCarts($mode);

		    echo 'Done mode: ' . $mode;
	    } else {
		    echo 'Mode not found : ' . $mode  . "\n";
		    echo $this->usageHelp();
		    exit();
	    }


    }


	/**
	 * Retrieve Usage Help Message
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		$help = 'Available actions: ' . "\n";
		$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method, -6) == 'Action') {
				$help .= '    -action ' . substr($method, 0, -6);
				$helpMethod = $method . 'Help';

				if (method_exists($this, $helpMethod)) {
					$help .= $this->$helpMethod();
				}
				$help .= "\n";
			}
		}
		return $help;
	}

	public function basketsActionHelp()
	{
		return " -mode <mode> [-customers, guests, all]	Run options for the abandoned baskets.";

	}

}

$shell = new Dotdigitalgroup_Email_Shell_Connector();
$shell->run();