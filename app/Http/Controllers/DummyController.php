<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;

class DummyController extends Controller
{
    public function index()
    { }

    public function show($id)
    { }

    public function create(Request $request)
    { }

    public function update(Request $request)
    { }

    public function destroy($id)
    { }

    public function check(Request $request)
    { }
}
