<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Instruction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstructionController extends Controller
{
    public function index()
    {
        $instructions = Instruction::orderBy('sort_order', 'asc')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.instructions.index', compact('instructions'));
    }

    public function create()
    {
        return view('admin.instructions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:204800', // 200MB max
            'video_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data['is_published'] = $request->has('is_published');
        $data['sort_order'] = $request->input('sort_order', 0);
        $data['slug'] = Str::slug($data['title']);

        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('instructions', 'public');
            $data['video_url'] = null; // Clear URL if a file is uploaded
        } else {
            $data['video_path'] = null;
        }

        Instruction::create($data);

        return redirect()->route('admin.instructions.index')
            ->with('success', 'Hướng dẫn đã được tạo thành công.');
    }

    public function edit(Instruction $instruction)
    {
        return view('admin.instructions.edit', compact('instruction'));
    }

    public function update(Request $request, Instruction $instruction)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:204800',
            'video_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data['is_published'] = $request->has('is_published');
        $data['sort_order'] = $request->input('sort_order', 0);
        $data['slug'] = Str::slug($data['title']);

        if ($request->hasFile('video_file')) {
            // Delete old file if updating
            if ($instruction->video_path) {
                Storage::disk('public')->delete($instruction->video_path);
            }
            $data['video_path'] = $request->file('video_file')->store('instructions', 'public');
            $data['video_url'] = null; // Clear URL if a file is uploaded
        } elseif ($request->filled('video_url')) {
             // Delete old file if switching to URL
             if ($instruction->video_path) {
                Storage::disk('public')->delete($instruction->video_path);
                $data['video_path'] = null;
            }
        }

        $instruction->update($data);

        return redirect()->route('admin.instructions.index')
            ->with('success', 'Hướng dẫn đã được cập nhật thành công.');
    }

    public function destroy(Instruction $instruction)
    {
        if ($instruction->video_path) {
            Storage::disk('public')->delete($instruction->video_path);
        }
        
        $instruction->delete();

        return redirect()->route('admin.instructions.index')
            ->with('success', 'Hướng dẫn đã được xóa thành công.');
    }
}
