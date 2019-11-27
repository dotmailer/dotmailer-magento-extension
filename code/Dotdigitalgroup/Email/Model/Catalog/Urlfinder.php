<?php

class Dotdigitalgroup_Email_Model_Catalog_Urlfinder
{
    /**
     * Fetch a URL for a product depending on its visibility and type.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int|string|null $storeId
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function fetchFor($product, $storeId = null)
    {
        $product = $this->getScopedProduct($product, $storeId);

        if (
            $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
            && $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
            && $parentProduct = $this->getParentProduct($product)
        ) {
            return $parentProduct->getProductUrl();
        }

        return $product->getProductUrl();
    }

    /**
     * @param $product
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getProductImageUrl($product)
    {
        $product = $this->getScopedProduct($product);

        if (
            $product->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
            && ($product->getSmallImage() == 'no_selection' || empty($product->getSmallImage()))
            && $parentProduct = $this->getParentProduct($product)
        ) {
            $product = $parentProduct;
        }

        return Mage::getModel('catalog/product_media_config')
            ->getMediaUrl($product->getSmallImage());

    }

    /**
     * Set the correct store scope for a product, in cases where it is not already set.
     * Achieve this either by manually supplying a store ID, or by finding the default store ID when one is not supplied.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int|string|null $storeId
     *
     * @return Mage_Catalog_Model_Product
     * @throws Mage_Core_Exception
     */
    protected function getScopedProduct($product, $storeId = null)
    {
        if (empty($storeId) && in_array($product->getStoreId(), $product->getStoreIds())) {
            return $product;
        }

        // If $storeId is empty or 0, assign the default store ID
        if (empty($storeId) && !in_array($product->getStoreId(), $product->getStoreIds())) {
            $productInWebsites = $product->getWebsiteIds();
            $firstWebsite = Mage::app()->getWebsite($productInWebsites[0]);
            $storeId = (int) $firstWebsite->getDefaultGroup()->getDefaultStoreId();
        }

        return Mage::getModel('catalog/product')
            ->load($product->getId())
            ->setStoreId($storeId);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return Mage_Catalog_Model_Product|null
     */
    protected function getParentProduct($product)
    {
        return Mage::getSingleton('ddg_automation/catalog_parentfinder')
            ->getParentProduct($product);
    }
}
