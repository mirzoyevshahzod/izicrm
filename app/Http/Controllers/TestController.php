<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
   public function index()
{
    $data = DB::connection('hr_db')
        ->table('access_logs')
        ->orderBy('id', 'desc')
        ->get();

   return response()->json($data);
}
}
