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
        return view('admin.settings.index', compact('zaloSetting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'zalo_contact_number' => 'required|string|max:20',
        ]);

        Setting::updateOrCreate(
            ['key' => 'zalo_contact_number'],
            [
                'value' => $request->zalo_contact_number,
                'label' => 'Số Zalo đăng ký tài khoản (Admin)'
            ]
        );

        return redirect()->route('admin.settings.index')->with('success', 'Đã cập nhật cài đặt thành công!');
    }
}
