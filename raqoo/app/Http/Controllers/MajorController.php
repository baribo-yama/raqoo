<?php

namespace App\Http\Controllers;

use App\Models\Major;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    public function byDepartment(Request $request)
    {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
            ]);

            $majors = Major::where('department_id', $validated['department_id'])
               ->orderBy('id')
               ->get(['id', 'name']);

            return response()->json($majors);
    }
}
