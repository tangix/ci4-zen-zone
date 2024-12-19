<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExampleTable extends Migration
{
    private $table = 'example';
    private array $fields = [
        'id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
        'message' => ['type' => 'varchar', 'constraint' => 255],
    ];

    public function up()
    {
        $this->forge->addPrimaryKey('id', 'id_key');
        $this->forge->addField($this->fields);
        $this->forge->createTable($this->table);
    }

    public function down()
    {
        $this->forge->dropTable($this->table);
    }
}
