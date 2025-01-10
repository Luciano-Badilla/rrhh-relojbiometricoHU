<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnualVacationDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('annual_vacation_days', function (Blueprint $table) {
            $table->id(); // Campo ID
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade'); // Llave foránea con eliminación en cascada
            $table->integer('days'); // Campo para días
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('annual_vacation_days');
    }
}
