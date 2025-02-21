<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleTable extends Migration
{
    public function up()
    {
        // Eliminamos la tabla si existe, para evitar conflictos al correr las migraciones
        Schema::dropIfExists('schedule');

        // Re-creamos la tabla con la estructura nueva
        Schema::create('schedule', function (Blueprint $table) {
            $table->bigIncrements('id'); // Clave primaria autoincremental
            $table->foreignId('day_id')->constrained('days')->onDelete('cascade'); // Relación con 'days'
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade'); // Relación con 'shifts'
            $table->timestamps(); // timestamps automáticos
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule');
    }
}
