<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("sial_acciones", function (Blueprint $t) {
            $t->id();
            $t->string("tipodoc",10)->nullable();
            $t->string("nrodoc",30);
            $t->string("estado",20);
            $t->dateTime("fecha_inscri")->nullable();
            $t->dateTime("fecha_accion")->nullable();
            $t->string("apellido",120)->nullable();
            $t->string("nombre",120)->nullable();
            $t->string("email_personal",180)->nullable();
            $t->string("email_institucional",180)->nullable();
            $t->json("raw")->nullable();
            $t->timestamps();
            $t->index(["tipodoc","nrodoc"]);
            $t->index(["estado","fecha_inscri"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("sial_acciones");
    }
};
