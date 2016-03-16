<?php

class Dotdigitalgroup_Email_Model_Customer_Wishlist_Item
{

    protected $_sku;
    protected $_qty;
    protected $_name;
    protected $_price;
    protected $_totalValueOfProduct;


    /**
     * construnctor.
     *
     * @param $product
     */
    public function __construct($product)
    {
        $this->setSku($product->getSku());
        $this->setName($product->getName());
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        $this->_qty = (int)$qty;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->_qty;
    }

    /**
     * @return mixed
     */
    public function getTotalValueOfProduct()
    {
        return $this->_totalValueOfProduct;
    }

    /**
     * @param $product
     *
     * @return $this
     */
    public function setPrice($product)
    {
        $this->_price = $product->getFinalPrice();
        $total        = $this->_price * $this->_qty;

        $this->_totalValueOfProduct = number_format($total, 2, '.', ',');

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->_price;
    }

    /**
     * @param $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        $this->_sku = $sku;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSku()
    {
        return $this->_sku;
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}