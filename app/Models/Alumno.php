<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $table = "alumnos";
    protected $fillable = ["tipodoc","nrodoc","apellido","nombre","email_personal","email_institucional"];
}
