<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoordinatorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coordinator', function (Blueprint $table) {
            $table->id(); // Crea una columna 'id' auto incrementable.
            $table->unsignedBigInteger('staff_id'); // Crea la columna 'staff_id'.
            $table->unsignedBigInteger('office_id'); 
            $table->timestamps(); // Crea columnas 'created_at' y 'updated_at'.php artisan make:migration create_office_table


            // Configuración de la clave foránea.
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('office_id')->references('id')->on('office')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coordinator');
    }
}
