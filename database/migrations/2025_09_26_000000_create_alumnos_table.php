<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("alumnos", function (Blueprint $t) {
            $t->id();
            $t->string("tipodoc",10)->nullable();
            $t->string("nrodoc",30);
            $t->string("apellido",120)->nullable();
            $t->string("nombre",120)->nullable();
            $t->string("email_personal",180)->nullable();
            $t->string("email_institucional",180)->nullable();
            $t->timestamps();
            $t->unique(["tipodoc","nrodoc"]);
            $t->index(["apellido","nombre"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("alumnos");
    }
};
