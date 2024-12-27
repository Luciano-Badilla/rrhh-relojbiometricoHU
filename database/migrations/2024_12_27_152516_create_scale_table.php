<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importar DB

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scale', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insertar los datos en la tabla
        DB::table('scale')->insert([
            ['name' => 'Autoridades Superiores'],
            ['name' => 'Administrativo'],
            ['name' => 'Técnico'],
            ['name' => 'Profesional'],
            ['name' => 'Asistencial'],
            ['name' => 'Mantenimiento'],
            ['name' => 'Servicios'],
            ['name' => 'Docente Universitario'],
            ['name' => 'Docente Secundario'],
            ['name' => 'Horas Superiores'],
            ['name' => 'Horas Secundarias'],
            ['name' => 'Actividad Privada'],
            ['name' => 'Sin Especificar'],
            ['name' => 'Contrato de Servicio'],
            ['name' => 'Pasante'],
            ['name' => 'Becarios'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scale');
    }
};
