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
        $zaloSetting2 = Setting::where('key', 'zalo_contact_number_2')->first();
        $emailSetting = Setting::where('key', 'contact_email')->first();
        $hotlineSetting = Setting::where('key', 'contact_hotline')->first();
        $writingLimitSetting = Setting::where('key', 'writing_grading_limit')->first();
        $speakingLimitSetting = Setting::where('key', 'speaking_grading_limit')->first();
        $defaultMaxDevices = Setting::where('key', 'default_max_devices')->first();
        $defaultAiLimit = Setting::where('key', 'default_ai_limit')->first();
        return view('admin.settings.index', compact(
            'zaloSetting', 'zaloSetting2', 'emailSetting', 'hotlineSetting', 'writingLimitSetting', 'speakingLimitSetting', 'defaultMaxDevices', 'defaultAiLimit'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'zalo_contact_number' => 'required|string|max:20',
            'zalo_contact_number_2' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'contact_hotline' => 'nullable|string|max:20',
            'writing_grading_limit' => 'required|integer|min:1',
            'speaking_grading_limit' => 'required|integer|min:1',
            'default_max_devices' => 'required|integer|min:1|max:10',
            'default_ai_limit' => 'required|integer|min:1',
        ]);

        Setting::updateOrCreate(
            ['key' => 'zalo_contact_number'],
            [
                'value' => $request->zalo_contact_number,
                'label' => 'Số Zalo đăng ký tài khoản (Admin)'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'zalo_contact_number_2'],
            [
                'value' => $request->zalo_contact_number_2,
                'label' => 'Số Zalo dự phòng (Admin)'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'contact_email'],
            [
                'value' => $request->contact_email,
                'label' => 'Email liên hệ'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'contact_hotline'],
            [
                'value' => $request->contact_hotline,
                'label' => 'Hotline hỗ trợ'
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

        Setting::updateOrCreate(
            ['key' => 'default_max_devices'],
            [
                'value' => $request->default_max_devices,
                'label' => 'Số lượng thiết bị tối đa mặc định'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'default_ai_limit'],
            [
                'value' => $request->default_ai_limit,
                'label' => 'Số lượt dùng AI tối đa mặc định'
            ]
        );

        if ($request->has('sync_max_devices')) {
             \App\Models\User::query()->update(['max_devices' => $request->default_max_devices]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Đã cập nhật cài đặt thành công!');
    }
}
