<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsenceReasonsTable extends Migration
{
    public function up()
    {
        Schema::create('absence_reasons', function (Blueprint $table) {
            $table->id(); // Campo id
            $table->string('name'); // Nombre de la razón de ausencia
            $table->string('article'); // Artículo relacionado
            $table->string('subsection'); // Subsección relacionada
            $table->string('item'); // Ítem relacionado
            $table->boolean('enjoyment'); // Disfrute relacionado
            $table->integer('year'); // Año
            $table->integer('month'); // Mes
            $table->boolean('continuous'); // Continuo (booleano)
            $table->boolean('businessDay'); // Día hábil (booleano)
            $table->string('decree'); // Decreto relacionado
            $table->timestamps(); // Tiempos de creación y actualización
        });
    }

    public function down()
    {
        Schema::dropIfExists('absence_reasons');
    }
}
