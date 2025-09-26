<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alumno;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min(200, (int) $request->input("per_page", 50)));
        $q = Alumno::query();

        if ($s = $request->input("q")) {
            $needle = mb_strtolower($s);
            $q->where(function($w) use ($needle){
                $w->whereRaw("LOWER(apellido) LIKE ?", ["%$needle%"])
                  ->orWhereRaw("LOWER(nombre) LIKE ?", ["%$needle%"])
                  ->orWhere("nrodoc", "like", "%$needle%");
            });
        }

        $page = $q->orderBy("apellido")->paginate($perPage)->appends($request->query());
        return response()->json([
            "data"  => $page->items(),
            "meta"  => ["page"=>$page->currentPage(),"per_page"=>$page->perPage(),"total"=>$page->total()],
            "links" => ["next"=>$page->nextPageUrl(),"prev"=>$page->previousPageUrl()],
        ]);
    }
}
