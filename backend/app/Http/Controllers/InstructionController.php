<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Instruction;

class InstructionController extends Controller
{
    public function index()
    {
        $instructions = Instruction::where('is_published', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('instructions.index', compact('instructions'));
    }

    public function show($slug)
    {
        $instruction = Instruction::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('instructions.show', compact('instruction'));
    }
}
