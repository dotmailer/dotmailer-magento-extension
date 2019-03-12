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
     * Dotdigitalgroup_Email_Model_Connector_Product constructor.
     * @param Mage_Catalog_Model_Product $product
     */
    public function __construct(Mage_Catalog_Model_Product $product)
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

        $this->getMinPrices($product);

        $this->url          = $product->getProductUrl();
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

        //bundle product options
        if ($product->getTypeId()
            == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
        ) {
            $optionCollection    = $product->getTypeInstance()
                ->getOptionsCollection();
            $selectionCollection = $product->getTypeInstance()
                ->getSelectionsCollection(
                    $product->getTypeInstance()->getOptionsIds()
                );
            $options = $optionCollection->appendSelections($selectionCollection);
            foreach ($options as $option) {
                $count      = 0;
                $title      = str_replace(' ', '', $option->getDefaultTitle());
                $selections = $option->getSelections();
                $sOptions   = array();
                foreach ($selections as $selection) {
                    $sOptions[$count]['name']  = $selection->getName();
                    $sOptions[$count]['sku']   = $selection->getSku();
                    $sOptions[$count]['id']    = $selection->getProductId();
                    $sOptions[$count]['price'] = (float)number_format(
                        $selection->getPrice(),
                        2,
                        '.',
                        ''
                    );
                    $count++;
                }

                $this->$title = $sOptions;
            }
        }

        //configurable product options
        if ($product->getTypeId()
            == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
        ) {
            $productAttributeOptions = $product->getTypeInstance(true)
                ->getConfigurableAttributesAsArray($product);
            foreach ($productAttributeOptions as $productAttribute) {
                $count   = 0;
                $label   = strtolower(
                    str_replace(' ', '', $productAttribute['label'])
                );
                $options = array();
                foreach ($productAttribute['values'] as $attribute) {
                    $options[$count]['option'] = $attribute['default_label'];
                    $options[$count]['price']  = (float)number_format(
                        $attribute['pricing_value'],
                        2,
                        '.',
                        ''
                    );
                    $count++;
                }

                $this->$label = $options;
            }
        }
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
                $specialPriceSimpleProducts = min($specialPriceArrayFlag);

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
}
