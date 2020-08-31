<?php

namespace Fluent\ShoppingCart\Facades;

use Fluent\ShoppingCart\Cart as ShoppingCart;

/**
 * @method static \Fluent\ShoppingCart\Cart instance($instance = null)
 * @method static \Fluent\ShoppingCart\Cart currentInstance()
 * @method static \Fluent\ShoppingCart\Cart add($id, $name = null, $qty = null, $price = null, array $options = [], $taxrate = null)
 * @method static \Fluent\ShoppingCart\Cart update($rowId, $qty)
 * @method static \Fluent\ShoppingCart\Cart remove($rowId)
 * @method static \Fluent\ShoppingCart\Cart get($rowId)
 * @method static \Fluent\ShoppingCart\Cart destroy()
 * @method static \Fluent\ShoppingCart\Cart content()
 * @method static \Fluent\ShoppingCart\Cart count()
 * @method static \Fluent\ShoppingCart\Cart total($decimals = null, $decimalPoint = null, $thousandSeperator = null)
 * @method static \Fluent\ShoppingCart\Cart tax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
 * @method static \Fluent\ShoppingCart\Cart subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
 * @method static \Fluent\ShoppingCart\Cart search(\Closure $search)
 * @method static \Fluent\ShoppingCart\Cart associate($rowId, $model)
 * @method static \Fluent\ShoppingCart\Cart setTax($rowId, $taxRate)
 * @method static \Fluent\ShoppingCart\Cart store($identifier)
 * @method static \Fluent\ShoppingCart\Cart restore($identifier)
 * @method static \Fluent\ShoppingCart\Cart __get($attribute)
 * 
 * @see \Fluent\ShoppingCart\Cart
 */
class Cart
{
    /**
     * @param $method
     * @param $arguments
     * @return ShoppingCart
     */
    public static function __callStatic($method, $arguments)
    {
        return (new ShoppingCart())->$method(...$arguments);
    }
}