<?php

use Illuminate\Support\Facades\Route;

// Salud simple
Route::get("ping", fn() => response()->json(["pong" => true]));

// Grupo v1 protegido por Sanctum
Route::middleware("auth:sanctum")->prefix("v1")->group(function () {
    Route::get("alumnos", fn() => response()->json(["data" => []]));
});
