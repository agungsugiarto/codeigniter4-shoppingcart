<?php

namespace Fluent\ShoppingCart;

use Fluent\ShoppingCart\Contracts\Buyable;
use Fluent\ShoppingCart\Exceptions\InvalidRowIDException;
use Fluent\ShoppingCart\Exceptions\UnknownModelException;
use Fluent\ShoppingCart\Models\ShoppingCart;
use CodeIgniter\Config\Services;
use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;
use Tightenco\Collect\Support\Collection;

class Cart
{
    const DEFAULT_INSTANCE = 'default';

    /**
     * Instance session manager.
     *
     * @var \CodeIgniter\Session\Session
     */
    protected $session;

    /**
     * Model shopping cart.
     *
     * @var \Fluent\ShoppingCart\Models\ShoppingCart $model
     */
    protected $model;

    /**
     * @var string
     */
    protected $instance;

    /**
     * Cart constructor.
     *
     */
    public function __construct()
    {
        $this->session = Services::session();

        $this->model = new ShoppingCart();

        $this->instance(self::DEFAULT_INSTANCE);
    }

    /**
     * Get the current cart instance.
     *
     * @param string|null $instance
     * @return $this
     */
    public function instance($instance = null)
    {
        $instance = $instance ?? self::DEFAULT_INSTANCE;
        
        $this->instance = sprintf('%s.%s', 'cart', $instance);

        return $this;
    }

    /**
     * Get the current instance.
     *
     * @return string
     */
    public function currentInstance()
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * @param mixed          $id
     * @param mixed          $name
     * @param int|float|null $qty
     * @param float|null     $price
     * @param array          $options
     * @param float|null     $taxrate
     * @return \Fluent\ShoppingCart\CartItem|array
     */
    public function add($id, $name = null, $qty = null, $price = null, array $options = [], $taxrate = null)
    {
        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $id);
        }

        if ($id instanceof CartItem) {
            $cartItem = $id;
        } else {
            $cartItem = $this->createCartItem($id, $name, $qty, $price, $options, $taxrate);
        }

        $content = $this->getContent();

        if ($content->has($cartItem->rowId)) {
            $cartItem->qty += $content->get($cartItem->rowId)->qty;
        }

        $content->put($cartItem->rowId, $cartItem);

        Events::trigger('cart.added', $cartItem);

        $this->session->set($this->instance, $content);

        return $cartItem;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param string $rowId
     * @param mixed  $qty
     * @return \Fluent\ShoppingCart\CartItem
     */
    public function update($rowId, $qty)
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof Buyable) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();

        if ($rowId !== $cartItem->rowId) {
            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);

            return;
        } else {
            $content->put($cartItem->rowId, $cartItem);
        }

        Events::trigger('cart.updated', $cartItem);

        $this->session->set($this->instance, $content);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     * @return void
     */
    public function remove($rowId)
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        Events::trigger('cart.removed', $cartItem);

        $this->session->set($this->instance, $content);
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param string $rowId
     * @return \Fluent\ShoppingCart\CartItem
     */
    public function get($rowId)
    {
        $content = $this->getContent();

        if (! $content->has($rowId)) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     *
     * @return void
     */
    public function destroy()
    {
        $this->session->remove($this->instance);
    }

    /**
     * Get the content of the cart.
     *
     * @return \Tightenco\Collect\Support\Collection|array
     */
    public function content()
    {
        if (is_null($this->session->get($this->instance))) {
            return new Collection([]);
        }

        return $this->session->get($this->instance);
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        return $this->getContent()->sum('qty');
    }

    /**
     * Get the total price of the items in the cart.
     *
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function total($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {
        $content = $this->getContent();

        $total = $content->reduce(function ($total, CartItem $cartItem) {
            return $total + ($cartItem->qty * $cartItem->priceTax);
        }, 0);

        return CartItem::numberFormat($total, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return float
     */
    public function tax($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {
        $content = $this->getContent();

        $tax = $content->reduce(function ($tax, CartItem $cartItem) {
            return $tax + ($cartItem->qty * $cartItem->tax);
        }, 0);

        return CartItem::numberFormat($tax, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeperator
     * @return float
     */
    public function subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $content = $this->getContent();

        $subTotal = $content->reduce(function ($subTotal, CartItem $cartItem) {
            return $subTotal + ($cartItem->qty * $cartItem->price);
        }, 0);

        return CartItem::numberFormat($subTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param \Closure $search
     * @return \Tightenco\Collect\Support\Collection
     */
    public function search(\Closure $search)
    {
        return $this->getContent()->filter($search);
    }

    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param string $rowId
     * @param mixed  $model
     * @return void
     */
    public function associate($rowId, $model)
    {
        if (is_string($model) && ! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($rowId);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->set($this->instance, $content);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     *
     * @param string    $rowId
     * @param int|float $taxRate
     * @return void
     */
    public function setTax($rowId, $taxRate)
    {
        $cartItem = $this->get($rowId);

        $cartItem->setTaxRate($taxRate);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->set($this->instance, $content);
    }

    /**
     * Store an the current instance of the cart.
     *
     * @param mixed $identifier
     * @return void
     *
     * @throws \Exception
     */
    public function store($identifier)
    {
        $content = $this->getContent();

        $this->model
            ->where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->delete();

        $this->model->insert([
            'identifier' => $identifier,
            'instance'   => $this->currentInstance(),
            'content'    => serialize($content),
            'created_at' => Time::now(),
            'updated_at' => Time::now(),
        ]);

        Events::trigger('cart.stored');
    }

    /**
     * Restore the cart with the given identifier.
     *
     * @param mixed $identifier
     * @return void
     */
    public function restore($identifier)
    {
        if (! $this->storedCartWithIdentifierExists($identifier)) {
            return;
        }

        $stored = $this->model
            ->where('instance', $this->currentInstance())
            ->where('identifier', $identifier)
            ->first();

        $storedContent = unserialize($stored->content);

        $currentInstance = $this->currentInstance();

        $this->instance($stored->instance);

        $content = $this->getContent();

        foreach ($storedContent as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }

        Events::trigger('cart.restored');

        $this->session->set($this->instance, $content);

        $this->instance($currentInstance);
    }


    /**
     * Deletes the stored cart with given identifier
     *
     * @param mixed $identifier
     * @return mixed
     */
    protected function deleteStoredCart($identifier)
    {
        return $this->model->where('identifier', $identifier)->delete();
    }

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @param string $attribute
     * @return float|null
     */
    public function __get($attribute)
    {
        if ($attribute === 'total') {
            return $this->total();
        }

        if ($attribute === 'tax') {
            return $this->tax();
        }

        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }

        return null;
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection.
     *
     * @return \Tightenco\Collect\Support\Collection|array
     */
    protected function getContent()
    {
        return $this->session->has($this->instance)
            ? $this->session->get($this->instance)
            : new Collection();
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param mixed     $id
     * @param mixed     $name
     * @param int|float $qty
     * @param float     $price
     * @param array     $options
     * @param float     $taxrate
     * @return \Fluent\ShoppingCart\CartItem
     */
    private function createCartItem($id, $name, $qty, $price, array $options, $taxrate)
    {
        if ($id instanceof Buyable) {
            $cartItem = CartItem::fromBuyable($id, $qty ?: []);
            $cartItem->setQuantity($name ?: 1);
            $cartItem->associate($id);
        } elseif (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
        } else {
            $cartItem = CartItem::fromAttributes($id, $name, $price, $options);
            $cartItem->setQuantity($qty);
        }

        if (isset($taxrate) && is_numeric($taxrate)) {
            $cartItem->setTaxRate($taxrate);
        } else {
            $cartItem->setTaxRate(config('Cart')->tax);
        }

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param mixed $item
     * @return bool
     */
    private function isMulti($item)
    {
        if (! is_array($item)) {
            return false;
        }

        return is_array(reset($item)) || reset($item) instanceof Buyable;
    }

    /**
     * @param $identifier
     * @return bool
     */
    protected function storedCartWithIdentifierExists($identifier)
    {
        return $this->model->where('identifier', $identifier)->where('instance', $this->currentInstance())->first()
            ? true
            : false;
    }
}
