<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonAttendanceTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('non_attendance', function (Blueprint $table) {
            $table->id(); // Crea el campo id (clave primaria)
            $table->string('file_number'); // Crea el campo file_number
            $table->date('date'); // Crea el campo date
            $table->foreignId('absenceReason_id')->nullable() // Crea el campo de la clave foránea
                ->constrained('absence_reasons') // Relaciona con la tabla absence_reasons
                ->onDelete('cascade'); // Elimina los registros en caso de eliminar la razón de ausencia
            $table->binary('logical_erase');

            $table->timestamps(); // Crea los campos created_at y updated_at
        });
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('non_attendance');
    }
}
