<?php

namespace App\Http\Controllers;

use App\Models\Log as LogModel;

class LogController extends Controller
{
    public function index()
    {
        $logs = LogModel::with('usuario')->latest('fecha')->paginate(5);

        return view('admin.logs.index', compact('logs'));
    }
}