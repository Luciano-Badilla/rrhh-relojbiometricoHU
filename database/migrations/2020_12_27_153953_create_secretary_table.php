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
        Schema::create('secretary', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insertar los datos en la tabla
        DB::table('secretary')->insert([
            ['name' => 'Sec. Académica'],
            ['name' => 'Sec. Administrativa'],
            ['name' => 'Secretaria Económico Financiera'],
            ['name' => 'Dirección Asistencial'],
            ['name' => 'Dirección de Gestión Administrativa'],
            ['name' => 'Dirección General'],
            ['name' => 'Dirección de Tecnología Biomédica'],
            ['name' => 'Contratos de Locación Asistencial'],
            ['name' => 'Contrato de Locacion No docente'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secretary');
    }
};
