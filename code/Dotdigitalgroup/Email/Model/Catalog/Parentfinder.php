<?php

class Dotdigitalgroup_Email_Model_Catalog_Parentfinder
{
    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return Mage_Catalog_Model_Product|null
     */
    public function getParentProduct($product)
    {
        if ($parentId = $this->getFirstParentId($product)) {
            return Mage::getModel('catalog/product')
                ->load($parentId)
                ->setStoreId($product->getStoreId());
        }
        return null;
    }

    /**
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductParentIdToSync($product)
    {
        $parent = $this->getParentProduct($product);
        if ($parent && $parent->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            return $parent->getId();
        }
        return '';
    }

    /**
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    public function hasConfigurableParent($product)
    {
        $configurableChildren = Mage::getModel('catalog/product_type_configurable')
            ->getParentIdsByChild($product->getId());
        return count($configurableChildren) > 0;
    }

    /**
     * Return parent ID for configurable, grouped or bundled products (in that order of priority)
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return mixed
     */
    protected function getFirstParentId($product)
    {
        $configurableProducts = Mage::getModel('catalog/product_type_configurable')
            ->getParentIdsByChild($product->getId());
        if (isset($configurableProducts[0])) {
            return $configurableProducts[0];
        }

        $groupedProducts = Mage::getModel('catalog/product_type_grouped')
            ->getParentIdsByChild($product->getId());
        if (isset($groupedProducts[0])) {
            return $groupedProducts[0];
        }

        $bundleProducts = Mage::getResourceSingleton('bundle/selection')
            ->getParentIdsByChild($product->getId());
        if (isset($bundleProducts[0])) {
            return $bundleProducts[0];
        }

        return null;
    }
}