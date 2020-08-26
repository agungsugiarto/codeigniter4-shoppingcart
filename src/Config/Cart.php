<?php

namespace Fluent\ShoppingCart\Config;

use CodeIgniter\Config\BaseConfig;

class Cart extends BaseConfig
{
   /*
   |--------------------------------------------------------------------------
   | Default tax rate
   |--------------------------------------------------------------------------
   |
   | This default tax rate will be used when you make a class implement the
   | Taxable interface and use the HasTax trait.
   |
   */
    public $tax = 21;

   /*
   |--------------------------------------------------------------------------
   | Shoppingcart table settings
   |--------------------------------------------------------------------------
   |
   | Here you can set the connection that the shoppingcart should use when
   | storing and restoring a cart.
   |
   */
    public $table = 'shoppingcart';

   /*
   |--------------------------------------------------------------------------
   | Default number format
   |--------------------------------------------------------------------------
   |
   | This defaults will be used for the formated numbers if you don't
   | set them in the method call.
   |
   */
    public $format = [

      'decimals' => 2,

      'decimal_point' => '.',

      'thousand_seperator' => ','

    ];
}
