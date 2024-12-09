<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('clock_logs', function (Blueprint $table) {
            $table->id();                // Crea la columna 'id' auto-incremental
            $table->string('file_number'); // Crea la columna 'file_number'
            $table->timestamp('timestamp'); // Crea la columna 'timestamp'
            $table->unsignedBigInteger('device_id'); // Crea la columna 'type_id' como clave foránea      // Crea las columnas 'created_at' y 'updated_at'
           
            $table->foreign('device_id')
                ->references('id')        
                ->on('devices')
                ->onDelete('cascade');    
        });
    }

    public function down()
    {
        Schema::dropIfExists('clock_logs');
    }
};
