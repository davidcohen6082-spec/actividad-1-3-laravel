<?php

namespace App\Http\Controllers;

use App\Models\Rol;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::withCount('usuarios')->paginate(5);

        return view('admin.roles.index', compact('roles'));
    }
}