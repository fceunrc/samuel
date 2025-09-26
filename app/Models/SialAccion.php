<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SialAccion extends Model
{
    protected $table = "sial_acciones";
    protected $fillable = ["tipodoc","nrodoc","estado","fecha_inscri","fecha_accion","apellido","nombre","email_personal","email_institucional","raw"];
    protected $casts = ["fecha_inscri"=>"datetime","fecha_accion"=>"datetime","raw"=>"array"];
}
