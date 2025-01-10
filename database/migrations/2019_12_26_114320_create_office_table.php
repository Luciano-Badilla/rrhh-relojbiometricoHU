<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importar DB

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('office', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insertar los datos en la tabla
        DB::table('office')->insert([
            ['name' => 'Cordinador'],
            ['name' => 'Dirección de Gestión Administrativa'],
            ['name' => 'Hospital Universitario'],
            ['name' => 'Medicina Interna'],
            ['name' => 'Ginecología y Obstetricia'],
            ['name' => 'Medios de Diagnóstico y Tratamiento'],
            ['name' => 'Enfermería'],
            ['name' => 'Odontología'],
            ['name' => 'Rehabilitación'],
            ['name' => 'Trabajo Social'],
            ['name' => 'Clínica Quirúrgica'],
            ['name' => 'Pediatría'],
            ['name' => 'UDA'],
            ['name' => 'Recursos Humanos'],
            ['name' => 'Jefatura Admisión y Call Center'],
            ['name' => 'Gestión de Procesos'],
            ['name' => 'Económico Financiera'],
            ['name' => 'Dirección General'],
            ['name' => 'Comunicación Organizacional'],
            ['name' => 'Dirección Académica'],
            ['name' => 'Tecnología Biomédica'],
            ['name' => 'Dirección Asistencial'],
            ['name' => 'Jefatura Servicios Complementarios'],
            ['name' => 'TIC´s'],
            ['name' => 'Contratos de Locacion de Servicios'],
            ['name' => 'Jefatura de Call Center, Informes y Secretaría de Odontología'],
            ['name' => 'Jefatura de Admisión General y Secretarías de Sala'],
            ['name' => 'Laboratorio'],
            ['name' => 'Diagnóstico por Imágenes'],
            ['name' => 'Farmacia'],
            ['name' => 'Despacho - Mesa de Entradas'],
            ['name' => 'Kinesiología'],
            ['name' => 'Comunicación Institucional'],
            ['name' => 'Admisión Servicios Complementarios'],
            ['name' => 'Dirección de Tecnologia Biométrica'],
            ['name' => 'Ginecología'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office');
    }
};
