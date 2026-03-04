<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $zaloSetting = Setting::where('key', 'zalo_contact_number')->first();
        $writingLimitSetting = Setting::where('key', 'writing_grading_limit')->first();
        $speakingLimitSetting = Setting::where('key', 'speaking_grading_limit')->first();
        return view('admin.settings.index', compact('zaloSetting', 'writingLimitSetting', 'speakingLimitSetting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'zalo_contact_number' => 'required|string|max:20',
            'writing_grading_limit' => 'required|integer|min:1',
            'speaking_grading_limit' => 'required|integer|min:1',
        ]);

        Setting::updateOrCreate(
            ['key' => 'zalo_contact_number'],
            [
                'value' => $request->zalo_contact_number,
                'label' => 'Số Zalo đăng ký tài khoản (Admin)'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'writing_grading_limit'],
            [
                'value' => $request->writing_grading_limit,
                'label' => 'Giới hạn gửi bài Writing (lần)'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'speaking_grading_limit'],
            [
                'value' => $request->speaking_grading_limit,
                'label' => 'Giới hạn gửi bài Speaking (lần)'
            ]
        );

        return redirect()->route('admin.settings.index')->with('success', 'Đã cập nhật cài đặt thành công!');
    }
}
