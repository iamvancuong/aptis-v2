<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HighScore;

class HighScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $highScores = HighScore::latest()->paginate(10);
        return view('admin.high-scores.index', compact('highScores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.high-scores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certificate' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        HighScore::create($validated);

        return redirect()->route('admin.high-scores.index')->with('success', 'Thêm thành tích thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HighScore $highScore)
    {
        return view('admin.high-scores.edit', compact('highScore'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HighScore $highScore)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certificate' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $highScore->update($validated);

        return redirect()->route('admin.high-scores.index')->with('success', 'Cập nhật thành tích thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HighScore $highScore)
    {
        $highScore->delete();

        return redirect()->route('admin.high-scores.index')->with('success', 'Xóa thành tích thành công.');
    }
}
