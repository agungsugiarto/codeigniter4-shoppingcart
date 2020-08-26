<?php

namespace Fluent\ShoppingCart\Config;

use Fluent\ShoppingCart\Cart;
use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    public static function cart($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('cart');
        }

        return new Cart();
    }
}
