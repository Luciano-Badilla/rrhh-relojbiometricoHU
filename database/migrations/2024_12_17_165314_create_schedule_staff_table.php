<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleStaffTable extends Migration
{
    public function up()
    {
        Schema::create('schedule_staff', function (Blueprint $table) {
            $table->bigIncrements('id'); // Clave primaria autoincremental
            $table->bigInteger('staff_id')->unsigned();
            $table->bigInteger('schedule_id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Índices
            $table->index('schedule_id', 'schedule_staff_schedule_id_foreign');
            $table->index('staff_id', 'fk_staff_id');

            // Claves foráneas
            $table->foreign('staff_id', 'fk_staff_id')
                ->references('id')->on('staff');

            $table->foreign('schedule_id', 'schedule_staff_schedule_id_foreign')
                ->references('id')->on('schedule')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_staff');
    }
}
