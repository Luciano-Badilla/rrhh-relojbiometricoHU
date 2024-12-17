<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // Campo id
            $table->string('file_number'); // Número de archivo
            $table->date('date'); // Fecha
            $table->time('entryTime'); // Hora de entrada
            $table->time('departureTime'); // Hora de salida
            $table->foreignId('absenceReason_id')->constrained('absence_reasons')->onDelete('cascade'); // Relación con la tabla absence_reasons
            $table->text('observations')->nullable(); // Observaciones
            $table->timestamps(); // Tiempos de creación y actualización
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
