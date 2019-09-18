<?php

/**
 * @codingStandardsIgnoreStart
 * Class Dotdigitalgroup_Email_Model_Connector_Product
 */
class Dotdigitalgroup_Email_Model_Connector_Product
{

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $sku = '';

    /**
     * @var string
     */
    public $status = '';

    /**
     * @var string
     */
    public $visibility = '';

    /**
     * @var float
     */
    public $price = 0;

    /**
     * @var float
     */
    public $specialPrice = 0;

    /**
     * @var array
     */
    public $categories = array();

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $imagePath = '';

    /**
     * @var string
     */
    public $short_description = '';

    /**
     * @var float
     */
    public $stock = 0;

    /**
     * @var array
     */
    public $websites = array();

    /**
     * @var
     */
    public $attributes;

    /**
     * @var string
     */
    public $type = '';

    /**
     * Set the product data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string|int|null $storeId
     *
     * @return $this
     */
    public function setProduct(
        Mage_Catalog_Model_Product $product,
        $storeId
    )
    {
        $this->id           = $product->getId();
        $this->sku          = $product->getSku();
        $this->name         = $product->getName();
        $statuses           = Mage::getModel('catalog/product_status')
            ->getOptionArray();
        $this->status       = $statuses[$product->getStatus()];
        $options            = Mage::getModel('catalog/product_visibility')
            ->getOptionArray();
        $this->visibility   = $options[$product->getVisibility()];
        $this->type         = ucfirst($product->getTypeId());

        $this->getMinPrices($product);

        $this->url          = Mage::getSingleton('ddg_automation/catalog_urlfinder')
            ->fetchFor(
                $product,
                $storeId
            );

        $this->imagePath    = Mage::getModel('catalog/product_media_config')
            ->getMediaUrl($product->getSmallImage());
        $stock              = Mage::getModel('cataloginventory/stock_item')
            ->loadByProduct($product);
        $this->stock        = (float)number_format(
            $stock->getQty(),
            2,
            '.',
            ''
        );

        $shortDescription = $product->getShortDescription();
        //limit short description
        if (strlen($shortDescription) > 250) {
            $shortDescription = substr($shortDescription, 0, 250);
        }

        $this->short_description = $shortDescription;

        //category data
        $count              = 0;
        $categoryCollection = $product->getCategoryCollection()
            ->addNameToResult();
        foreach ($categoryCollection as $cat) {
            $this->categories[$count]['Id']   = $cat->getId();
            $this->categories[$count]['Name'] = $cat->getName();
            $count++;
        }

        //website data
        $count      = 0;
        $websiteIds = $product->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            $website                        = Mage::app()->getWebsite(
                $websiteId
            );
            $this->websites[$count]['Id']   = $website->getId();
            $this->websites[$count]['Name'] = $website->getName();
            $count++;
        }

        //Custom Attributes
        $this->_setCustomAttributes($product, $storeId);

        return $this;
    }

    /**
     * Exposes the class as an array of objects.
     *
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }

    /**
     * Set the Minimum Prices for Configurable and Bundle products.
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return null
     */
    private function getMinPrices($product)
    {
        switch ($product->getTypeId()) {
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                $this->getMinConfigurablePrices($product);
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                $this->getMinBundlePrices($product);
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                $this->getMinGroupedPrices($product);
                break;
            default:
                $this->price = $product->getPrice();
                $this->specialPrice = $product->getSpecialPrice();
        }

        $this->formatPriceValues();
    }

    /**
     * Calculates the Minimum Final Price and Special Price for the Configurable Products.
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return null
     */
    private function getMinConfigurablePrices($product)
    {
        foreach ($product->getTypeInstance()->getChildrenIds($product->getId()) as $childProductIds) {
            foreach ($childProductIds as $id) {
                $productById = Mage::getModel('catalog/product')->load($id);
                $childPrices[] = $productById->getPrice();
                if ($productById->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $productById->getSpecialPrice();
                }
            }
        }
        $this->price = isset($childPrices) ? min($childPrices) : null;
        $this->specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
    }

    /**
     * Calculates the Minimum Final Price and Special Price for the Bundle Products.
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return null
     */
    private function getMinBundlePrices($product)
    {
        $this->price = 0;
        $this->specialPrice = 0;
        $productTypeInstance = $product->getTypeInstance(true);
        $optionCol= $productTypeInstance->getOptionsCollection($product);
        $selectionCol= $productTypeInstance->getSelectionsCollection(
            $productTypeInstance->getOptionsIds($product),
            $product
        );
        $optionCol->appendSelections($selectionCol);
        foreach ($optionCol as $option) {
            if ($option->getRequired()) {
                $selections = $option->getSelections();
                $specialPriceArrayFlag = [];
                $minPrice = min(array_map(function ($s) use (&$specialPriceArrayFlag) {
                    if ($s->getSpecialPrice() > 0) {
                        $specialPriceArrayFlag[] = $s->getSpecialPrice();
                    }
                    return $s->price;
                }, $selections));
                $specialPriceSimpleProducts = count($specialPriceArrayFlag) > 0 ? min($specialPriceArrayFlag) : 0;

                if ($specialPriceSimpleProducts > 0) {
                    if ($product->getSpecialPrice() > 0) {
                        $specialPriceSimpleProducts = ($specialPriceSimpleProducts * $product->getSpecialPrice()/100);
                        $this->specialPrice += $specialPriceSimpleProducts;
                    } else {
                        $this->specialPrice += $specialPriceSimpleProducts;
                    }
                } elseif ($product->getSpecialPrice() > 0) {
                    $minSpecialPrice = ($minPrice * $product->getSpecialPrice()/100);
                    $this->specialPrice += $minSpecialPrice;
                }

                $this->price += round($minPrice, 2);
                $this->specialPrice = ($this->price === $this->specialPrice) ? null : $this->specialPrice;
            }
        }
    }

    /**
     * Calculates the Minimum Final Price and Special Price for the Grouped Products.
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return null
     */
    private function getMinGroupedPrices($product)
    {
        $childProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
        foreach ($childProducts as $childProduct) {
            $childPrices[] = $childProduct->getPrice();
            if ($childProduct->getSpecialPrice() !== null) {
                $childSpecialPrices[] = $childProduct->getSpecialPrice();
            }
        }
        $this->price = isset($childPrices) ? min($childPrices) : null;
        $this->specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
    }


    /**
     * Formats the price values.
     *
     * @return null
     */
    private function formatPriceValues()
    {
        $this->price = (float)number_format(
            $this->price,
            2,
            '.',
            ''
        );
        $this->specialPrice = (float)number_format(
            $this->specialPrice,
            2,
            '.',
            ''
        );
    }

    /**
     * Ensure text matches insight data key restrictions
     * https://support.dotmailer.com/hc/en-gb/articles/212214538-Using-Insight-data-developers-guide-#restrictkeys
     *
     * @param string $text
     *
     * @return false|int
     */
    private function textIsValidForInsightDataKey($text)
    {
        return preg_match('/^[a-zA-Z_\\\\-][a-zA-Z0-9_\\\\-]*$/', $text);
    }

    /**
     * Initializes Custom Product Attributes to be imported via Catalog Sync
     * @param $product
     */
    private function _setCustomAttributes($product, $storeId)
    {
        $configAttributes = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_ATTRIBUTES,
            Mage::app()->getStore($storeId)->getWebsiteId()
        );
        if ($configAttributes) {
            $attributor = Mage::getModel('ddg_automation/connector_productattributes');
            $this->attributes = $attributor->initializeCustomAttributes($configAttributes,$product);
        }
    }
}
