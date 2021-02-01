<?php

namespace Tests;

use Fluent\ShoppingCart\Contracts\Buyable;

class BuyAbleProduct implements Buyable
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $price;

    public function __construct($id = 1, $name = 'Item name', $price = 10.00)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

    public function getBuyableIdentifier($options = null)
    {
        return $this->id;
    }

    public function getBuyableDescription($options = null)
    {
        return $this->name;
    }

    public function getBuyablePrice($options = null)
    {
        return $this->price;
    }
}