<?php

namespace Tests;

use CodeIgniter\Config\Config;
use CodeIgniter\Events\Events;
use CodeIgniter\Test\CIDatabaseTestCase;
use Fluent\ShoppingCart\Cart;
use Fluent\ShoppingCart\CartItem;
use PHPUnit\Framework\Assert;
use Tests\Support\Database\Seeds\CartSeeder;
use Tightenco\Collect\Support\Collection;

class CartTest extends CIDatabaseTestCase
{
    use CartAssertions;

    /** @var \Fluent\ShoppingCart\Cart */
    protected $getCart;

    protected $seed = CartSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Events::on('cart.added', function($arg) {});
        Events::on('cart.updated', function($arg) {});
        Events::on('cart.removed', function($arg) {});
        Events::on('cart.stored', function() {});
        Events::on('cart.restored', function() {});

        $this->getCart = new Cart;
    }

    public function test_it_has_a_default_instance()
    {
        $this->assertEquals(Cart::DEFAULT_INSTANCE, $this->getCart->currentInstance());
    }

    public function test_it_can_have_multiple_instances()
    {
        $cart = $this->getCart;

        $cart->add(new BuyAbleProduct(1, 'First item'));
        $cart->instance('wishlist')->add(new BuyAbleProduct(2, 'Second item'));

        $this->assertItemsInCart(1, $cart->instance(Cart::DEFAULT_INSTANCE));
        $this->assertItemsInCart(1, $cart->instance('wishlist'));
    }

    public function test_it_can_add_an_item()
    {
        $cart = $this->getCart;

        $cart->add(new BuyAbleProduct);

        $this->assertEquals(1, $cart->count());
        $this->assertEventTriggered('cart.added');
    }

    public function test_it_will_return_the_cartitem_of_the_added_item()
    {
        $cart = $this->getCart;

        $cartItem = $cart->add(new BuyAbleProduct);

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals('027c91341fd5cf4d2579b49c4b6a90da', $cartItem->rowId);
        $this->assertEventTriggered('cart.added');
    }

    public function test_it_can_add_multiple_buyable_items_at_once()
    {
        $cart = $this->getCart;

        $cart->add([new BuyableProduct(1), new BuyableProduct(2)]);

        $this->assertEquals(2, $cart->count());
        $this->assertEventTriggered('cart.added');
    }

    public function test_it_will_return_an_array_of_cartitems_when_you_add_multiple_items_at_once()
    {
        $cart = $this->getCart;

        $cartItems = $cart->add([new BuyableProduct(1), new BuyableProduct(2)]);

        $this->assertTrue(is_array($cartItems));
        $this->assertCount(2, $cartItems);
        $this->assertContainsOnlyInstancesOf(CartItem::class, $cartItems);
        $this->assertEventTriggered('cart.added');
    }

    public function test_it_can_add_an_item_from_attributes()
    {
        $cart = $this->getCart;

        $cart->add(1, 'Test item', 1, 10.00);

        $this->assertEquals(1, $cart->count());

        $this->assertEventTriggered('cart.added');
    }

    public function test_it_can_add_an_item_from_an_array()
    {
        $cart = $this->getCart;

        $cart->add(['id' => 1, 'name' => 'Test item', 'qty' => 1, 'price' => 10.00]);

        $this->assertEquals(1, $cart->count());

        $this->assertEventTriggered('cart.added');
    }

    public function test_it_can_add_multiple_array_items_at_once()
    {
        $cart = $this->getCart;

        $cart->add([
            ['id' => 1, 'name' => 'Test item 1', 'qty' => 1, 'price' => 10.00],
            ['id' => 2, 'name' => 'Test item 2', 'qty' => 1, 'price' => 10.00]
        ]);

        $this->assertEquals(2, $cart->count());

        $this->assertEventTriggered('cart.added');
    }

    public function test_it_can_add_an_item_with_options()
    {
        $cart = $this->getCart;

        $options = ['size' => 'XL', 'color' => 'red'];

        $cart->add(new BuyableProduct, 1, $options);

        $cartItem = $cart->get('07d5da5550494c62daf9993cf954303f');

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals('XL', $cartItem->options->size);
        $this->assertEquals('red', $cartItem->options->color);

        $this->assertEventTriggered('cart.added');
    }

    public function test_it_will_validate_the_identifier()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid identifier');

        $cart = $this->getCart;

        $cart->add(null, 'Some title', 1, 10.00);
    }
    
    public function test_it_will_validate_the_name()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid name');

        $cart = $this->getCart;

        $cart->add(1, null, 1, 10.00);
    }

    public function test_it_will_validate_the_quantity()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid quantity');

        $cart = $this->getCart;

        $cart->add(1, 'Some title', 'invalid', 10.00);
    }

    public function test_it_will_validate_the_price()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid price');

        $cart = $this->getCart;

        $cart->add(1, 'Some title', 1, 'invalid');
    }

    public function test_it_will_update_the_cart_if_the_item_already_exists_in_the_cart()
    {
        $cart = $this->getCart;

        $item = new BuyableProduct;

        $cart->add($item);
        $cart->add($item);

        $this->assertItemsInCart(2, $cart);
        $this->assertRowsInCart(1, $cart);
    }

    public function test_it_will_keep_updating_the_quantity_when_an_item_is_added_multiple_times()
    {
        $cart = $this->getCart;

        $item = new BuyableProduct;

        $cart->add($item);
        $cart->add($item);
        $cart->add($item);

        $this->assertItemsInCart(3, $cart);
        $this->assertRowsInCart(1, $cart);
    }

    public function test_it_can_update_the_quantity_of_an_existing_item_in_the_cart()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 2);

        $this->assertItemsInCart(2, $cart);
        $this->assertRowsInCart(1, $cart);

        $this->assertEventTriggered('cart.updated');
    }

    public function test_it_can_update_an_existing_item_in_the_cart_from_a_buyable()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', new BuyableProduct(1, 'Different description'));

        $this->assertItemsInCart(1, $cart);
        $this->assertEquals('Different description', $cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name);

        $this->assertEventTriggered('cart.updated');
    }

    public function test_it_can_update_an_existing_item_in_the_cart_from_an_array()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', ['name' => 'Different description']);

        $this->assertItemsInCart(1, $cart);
        $this->assertEquals('Different description', $cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name);

        $this->assertEventTriggered('cart.updated');
    }
    
    public function test_it_will_throw_an_exception_if_a_rowid_was_not_found()
    {
        $this->expectException('\Fluent\ShoppingCart\Exceptions\InvalidRowIDException');

        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->update('none-existing-rowid', new BuyableProduct(1, 'Different description'));
    }

    public function test_it_will_regenerate_the_rowid_if_the_options_changed()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct, 1, ['color' => 'red']);

        $cart->update('ea65e0bdcd1967c4b3149e9e780177c0', ['options' => ['color' => 'blue']]);

        $this->assertItemsInCart(1, $cart);
        $this->assertEquals('7e70a1e9aaadd18c72921a07aae5d011', $cart->content()->first()->rowId);
        $this->assertEquals('blue', $cart->get('7e70a1e9aaadd18c72921a07aae5d011')->options->color);
    }

    public function test_it_will_add_the_item_to_an_existing_row_if_the_options_changed_to_an_existing_rowid()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct, 1, ['color' => 'red']);
        $cart->add(new BuyableProduct, 1, ['color' => 'blue']);

        $cart->update('7e70a1e9aaadd18c72921a07aae5d011', ['options' => ['color' => 'red']]);

        $this->assertItemsInCart(2, $cart);
        $this->assertRowsInCart(1, $cart);
    }

    public function test_it_can_remove_an_item_from_the_cart()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->remove('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertItemsInCart(0, $cart);
        $this->assertRowsInCart(0, $cart);

        $this->assertEventTriggered('cart.removed');
    }

    public function test_it_will_remove_the_item_if_its_quantity_was_set_to_zero()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 0);

        $this->assertItemsInCart(0, $cart);
        $this->assertRowsInCart(0, $cart);

        $this->assertEventTriggered('cart.removed');
    }

    public function test_it_will_remove_the_item_if_its_quantity_was_set_negative()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', -1);

        $this->assertItemsInCart(0, $cart);
        $this->assertRowsInCart(0, $cart);

        $this->assertEventTriggered('cart.removed');
    }

    public function test_it_can_get_an_item_from_the_cart_by_its_rowid()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertInstanceOf(CartItem::class, $cartItem);
    }

    public function test_it_can_get_the_content_of_the_cart()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1));
        $cart->add(new BuyableProduct(2));

        $content = $cart->content();

        $this->assertInstanceOf(Collection::class, $content);
        $this->assertCount(2, $content);
    }

    public function test_it_will_return_an_empty_collection_if_the_cart_is_empty()
    {
        $cart = $this->getCart;

        $content = $cart->content();

        $this->assertInstanceOf(Collection::class, $content);
        $this->assertCount(0, $content);
    }

    public function test_it_will_include_the_tax_and_subtotal_when_converted_to_an_array()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1));
        $cart->add(new BuyableProduct(2));

        $content = $cart->content();

        $this->assertInstanceOf(Collection::class, $content);
    }

    
    public function test_it_can_destroy_a_cart()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $this->assertItemsInCart(1, $cart);

        $cart->destroy();

        $this->assertItemsInCart(0, $cart);
    }

    public function test_it_can_get_the_total_price_of_the_cart_content()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'First item', 10.00));
        $cart->add(new BuyableProduct(2, 'Second item', 25.00), 2);

        $this->assertItemsInCart(3, $cart);
        $this->assertEquals(60.00, $cart->subtotal());
    }

    public function test_it_can_return_a_formatted_total()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'First item', 1000.00));
        $cart->add(new BuyableProduct(2, 'Second item', 2500.00), 2);

        $this->assertItemsInCart(3, $cart);
        $this->assertEquals('6.000,00', $cart->subtotal(2, ',', '.'));
    }

    public function test_it_can_search_the_cart_for_a_specific_item()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some item'));
        $cart->add(new BuyableProduct(2, 'Another item'));

        $cartItem = $cart->search(function ($cartItem, $rowId) {
            return $cartItem->name == 'Some item';
        });

        $this->assertInstanceOf(Collection::class, $cartItem);
        $this->assertCount(1, $cartItem);
        $this->assertInstanceOf(CartItem::class, $cartItem->first());
        $this->assertEquals(1, $cartItem->first()->id);
    }

    public function test_it_can_search_the_cart_for_multiple_items()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some item'));
        $cart->add(new BuyableProduct(2, 'Some item'));
        $cart->add(new BuyableProduct(3, 'Another item'));

        $cartItem = $cart->search(function ($cartItem, $rowId) {
            return $cartItem->name == 'Some item';
        });

        $this->assertInstanceOf(Collection::class, $cartItem);
    }

    public function test_it_can_search_the_cart_for_a_specific_item_with_options()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some item'), 1, ['color' => 'red']);
        $cart->add(new BuyableProduct(2, 'Another item'), 1, ['color' => 'blue']);

        $cartItem = $cart->search(function ($cartItem, $rowId) {
            return $cartItem->options->color == 'red';
        });

        $this->assertInstanceOf(Collection::class, $cartItem);
        $this->assertCount(1, $cartItem);
        $this->assertInstanceOf(CartItem::class, $cartItem->first());
        $this->assertEquals(1, $cartItem->first()->id);
    }

    public function test_it_can_associate_the_cart_item_with_a_model()
    {
        $cart = $this->getCart;

        $cart->add(new BuyAbleProduct());

        $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', new ProductModel);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertEquals(ProductModel::class, $cartItem->associatedModel);
    }

    public function test_it_will_throw_an_exception_when_a_non_existing_model_is_being_associated()
    {
        $this->expectException('\Fluent\ShoppingCart\Exceptions\UnknownModelException');
        $this->expectExceptionMessage('The supplied model SomeModel does not exist.');

        $cart = $this->getCart;

        $cart->add(1, 'Test item', 1, 10.00);

        $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', 'SomeModel');
    }

    public function test_it_can_get_the_associated_model_of_a_cart_item()
    {
        $cart = $this->getCart;

        $cart->add(1, 'Test item', 1, 10.00);

        $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', new ProductModel);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertInstanceOf(ProductModel::class, $cartItem->model);
        $this->assertEquals('Some value', $cartItem->model->someValue);
    }

    public function test_it_can_calculate_the_subtotal_of_a_cart_item()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 9.99), 3);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertEquals(29.97, $cartItem->subtotal);
    }

    public function test_it_can_calculate_tax_based_on_the_default_tax_rate_in_the_config()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertEquals(2.10, $cartItem->tax);
    }

    public function test_it_can_calculate_tax_based_on_the_specified_tax()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);

        $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertEquals(1.90, $cartItem->tax);
    }

    public function test_it_can_return_the_calculated_tax_formatted()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10000.00), 1);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertEquals('2.100,00', $cartItem->tax(2, ',', '.'));
    }

    /** @test */
    public function it_can_return_formatted_total_tax()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 1000.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 2000.00), 2);

        $this->assertEquals('1.050,00', $cart->tax(2, ',', '.'));
    }

    /** @test */
    public function it_can_return_the_subtotal()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 20.00), 2);

        $this->assertEquals(50.00, $cart->subtotal);
    }

    /** @test */
    public function it_can_return_formatted_subtotal()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 1000.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 2000.00), 2);

        $this->assertEquals('5000,00', $cart->subtotal(2, ',', ''));
    }

    public function test_it_can_store_the_cart_in_a_database()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct);

        $cart->store($identifier = 123);

        $serialized = serialize($cart->content());

        $this->seeInDatabase('shoppingcart', ['identifier' => $identifier, 'instance' => 'default', 'content' => $serialized]);
    }

    /** @test */
    public function it_can_calculate_all_values()
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'First item', 10.00), 2);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);

        $this->assertEquals(10.00, $cartItem->price(2));
        $this->assertEquals(11.90, $cartItem->priceTax(2));
        $this->assertEquals(23.80, $cartItem->total(2));
        $this->assertEquals(1.90, $cartItem->tax(2));
        $this->assertEquals(3.80, $cartItem->taxTotal(2));

        $this->assertEquals(20.00, $cart->subtotal(2));
        $this->assertEquals(23.80, $cart->total(2));
        $this->assertEquals(3.80, $cart->tax(2));
    }

    /**
     * Set the config number format.
     * 
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     */
    private function setConfigFormat($decimals, $decimalPoint, $thousandSeperator)
    {
        $config = Config::get('Cart');

        $config->format['decimals'] = $decimals;
        $config->format['decimal_point'] = $decimalPoint;
        $config->format['thousand_seperator'] = $thousandSeperator;

        return $config;
    }
}