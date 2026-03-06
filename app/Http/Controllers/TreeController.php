<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    public function index(Request $request)
    {
        $rootId = $request->query('user_id');

        $rootUser = $rootId
            ? User::with('children')->findOrFail($rootId)
            : User::with('children')->whereNull('parent_id')->first();

        $allUsers = User::orderBy('name')->get();

        return view('tree', [
            'rootUser' => $rootUser,
            'allUsers' => $allUsers,
        ]);
    }
}
