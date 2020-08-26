<?php

namespace Fluent\ShoppingCart;

trait CanBeBought
{
    /**
     * Get the identifier of buyable item.
     *
     * @return int|string
     */
    public function getBuyableIdentifier()
    {
        return method_exists($this, 'getKey')
            ? $this->getKey()
            : $this->id;
    }

    /**
     * Get the description or title of the buyable item.
     *
     * @param null $options
     * @return string
     */
    public function getBuyableDescriptions($options = null)
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        if (property_exists($this, 'title')) {
            return $this->title;
        }

        if (property_exists($this, 'description')) {
            return $this->description;
        }

        return null;
    }

    /**
     * Get the price of buyable item.
     *
     * @return float
     */
    public function getBuyablePrice()
    {
        return property_exists($this, 'price')
            ? $this->price
            : null;
    }
}
