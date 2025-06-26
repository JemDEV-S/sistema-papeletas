<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('permission_types', function (Blueprint $table) {
            // Cambiar el tamaño de la columna code
            $table->string('code', 50)->change(); // o el tamaño que necesites
        });
    }

    public function down()
    {
        Schema::table('permission_types', function (Blueprint $table) {
            $table->string('code', 255)->change(); // tamaño original
        });
    }
};