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
            $table->string('email'); // Email del personal
            $table->string('phone'); // Teléfono del personal
            $table->string('address'); // Dirección del personal

            // Agregar claves foráneas
            $table->unsignedBigInteger('coordinator_id'); // Relación con la tabla coordinator
            $table->unsignedBigInteger('category_id');    // Relación con la tabla category
            $table->unsignedBigInteger('secretary_id');   // Relación con la tabla secretary
            $table->unsignedBigInteger('scale_id');       // Relación con la tabla scale

            // Establecer las claves foráneas
            $table->foreign('coordinator_id')->references('id')->on('coordinators')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('secretary_id')->references('id')->on('secretaries')->onDelete('cascade');
            $table->foreign('scale_id')->references('id')->on('scales')->onDelete('cascade');

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
        Schema::table('staff', function (Blueprint $table) {
            // Eliminar las claves foráneas antes de borrar la tabla
            $table->dropForeign(['coordinator_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['secretary_id']);
            $table->dropForeign(['scale_id']);
        });

        Schema::dropIfExists('staff');
    }
}
