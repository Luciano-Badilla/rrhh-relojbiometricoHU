<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleTable extends Migration
{
    public function up()
    {
        Schema::create('schedule', function (Blueprint $table) {
            $table->bigIncrements('id'); // Clave primaria autoincremental
            $table->string('day'); // Día (varchar 255)
            $table->time('startTime'); // Hora de inicio
            $table->time('endTime'); // Hora de finalización
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule');
    }
}

