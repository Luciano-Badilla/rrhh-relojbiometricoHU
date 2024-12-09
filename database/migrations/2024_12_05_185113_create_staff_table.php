<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id(); // Crea la columna id auto-incremental
            $table->string('file_number')->unique(); // Número de archivo, asegurándose de que sea único
            $table->string('name_surname'); // Nombre y apellido del personal
            $table->timestamps(); // Agrega las columnas created_at y updated_at
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff');
    }
}
