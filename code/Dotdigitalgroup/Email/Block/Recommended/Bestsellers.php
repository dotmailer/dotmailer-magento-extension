<?php

class Dotdigitalgroup_Email_Block_Recommended_Bestsellers extends Mage_Core_Block_Template
{

    /**
	 * Prepare layout.
	 * @return Mage_Core_Block_Abstract|void
	 */
	protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

	/**
	 * Get product collection.
	 * @return array
	 * @throws Exception
	 */
	public function getLoadedProductCollection()
    {
        $mode = $this->getRequest()->getActionName();
        $limit  = Mage::helper('connector/recommended')->getDisplayLimitByMode($mode);
        $from  =  Mage::helper('connector/recommended')->getTimeFromConfig($mode);
	    $to = Zend_Date::now()->toString(Zend_Date::ISO_8601);

	    $productCollection = Mage::getResourceModel('reports/product_collection')
		    ->addAttributeToSelect('*')
		    ->addOrderedQty($from, $to)
            ->setOrder('ordered_qty', 'desc')
	        ->setPageSize($limit);

	    Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
	    $productCollection->addAttributeToFilter('is_saleable', TRUE);

        //filter collection by category
        if($cat_id = Mage::app()->getRequest()->getParam('category_id')){
            $category = Mage::getModel('catalog/category')->load($cat_id);
            if($category->getId()){
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $cat_id);
            }else{
                Mage::helper('connector')->log('Best seller. Category id '. $cat_id . ' is invalid. It does not exist.');
            }
        }
	    return $productCollection;
    }

	/**
	 * Display type mode.
	 *
	 * @return mixed|string
	 */
	public function getMode()
    {
        return Mage::helper('connector/recommended')->getDisplayType();

    }

	/**
	 * Price html.
	 * @param $product
	 *
	 * @return string
	 */
	public function getPriceHtml($product)
    {
        $this->setTemplate('connector/product/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }

}