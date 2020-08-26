<?php

namespace Fluent\ShoppingCart\Models;

use CodeIgniter\Config\Config;
use CodeIgniter\Model;

class ShoppingCart extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['identifier', 'instance', 'content'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get config table name.
     *
     * @return string
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->table = Config::get('Cart')->table ?? 'shoppingcart';
    }
}
