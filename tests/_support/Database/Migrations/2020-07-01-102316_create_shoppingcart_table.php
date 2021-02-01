<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Config\Config;
use CodeIgniter\Database\Migration;

class CreateShoppingcartTable extends Migration
{
    protected $table;

    public function __construct()
    {
        parent::__construct();

        $this->table = Config::get('Cart')->table ?? 'shoppingcart';
    }

    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'identifier' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'instance'   => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'content'    => ['type' => 'text'],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable($this->table, true);
    }

    //--------------------------------------------------------------------

    public function down()
    {
        $this->forge->dropTable($this->table, true);
    }
}
