<?php

trait Dotdigitalgroup_Email_Block_ProductMetadataTrait
{
    /**
     * Trait method for Block classes which fetches a product (or parent product) image
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Helper_Image
     */
    public function getProductImage($product)
    {
        $helper = Mage::helper('ddg');
        if ($helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_PRODUCT_IMAGE)
            && $product->getTypeId() == "simple"
        ) {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                /** @var Mage_Catalog_Model_Product $parentProduct */
                $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
                return $this->helper('catalog/image')->init($parentProduct, 'small_image')->resize(135);
            }
        }

        return $this->helper('catalog/image')->init($product, 'small_image')->resize(135);
    }

    /**
     * Trait method for Block classes which fetches a product (or parent product) URL
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductUrl($product)
    {
        return Mage::getSingleton('ddg_automation/catalog_urlfinder')
            ->fetchFor(
                $product
            );
    }

    /**
     * Trait method for Block classes which fetches a product (or parent product) name
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Helper_Image
     */
    public function getProductName($product)
    {
        if ($product->getTypeId() == "simple") {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                /** @var Mage_Catalog_Model_Product $parentProduct */
                $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
                return $parentProduct->getName();
            }
        }
        return $product->getName();
    }
}
