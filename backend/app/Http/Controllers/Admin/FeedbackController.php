<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Feedback;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feedbacks = Feedback::latest()->paginate(10);
        return view('admin.feedback.index', compact('feedbacks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.feedback.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        Feedback::create($validated);

        return redirect()->route('admin.feedback.index')->with('success', 'Thêm feedback thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedback $feedback)
    {
        return view('admin.feedback.edit', compact('feedback'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $feedback->update($validated);

        return redirect()->route('admin.feedback.index')->with('success', 'Cập nhật feedback thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return redirect()->route('admin.feedback.index')->with('success', 'Xóa feedback thành công.');
    }
}
