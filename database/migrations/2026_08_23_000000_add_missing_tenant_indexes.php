<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'facture',
        'caisse_operations',
        'ordonnanceref',
        'boncommande',
        'bordereauxfactures',
    ];

    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (!$this->hasIndex($table, 'fkidCabinet')) {
                    $blueprint->index('fkidCabinet');
                }
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if ($this->hasIndex($table, 'fkidCabinet')) {
                    $blueprint->dropIndex($table . '_fkidcabinet_index');
                }
            });
        }
    }

    private function hasIndex(string $table, string $column): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);

        foreach ($indexes as $index) {
            if (in_array(strtolower($column), array_map('strtolower', $index->getColumns()))) {
                return true;
            }
        }

        return false;
    }
};
