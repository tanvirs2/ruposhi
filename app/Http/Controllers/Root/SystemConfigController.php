<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    public function edit()
    {
        $settings = [
            'support_phone'     => SystemConfig::get('support_phone'),
            'support_whatsapp'  => SystemConfig::get('support_whatsapp'),
            'support_email'     => SystemConfig::get('support_email'),
            'software_name'     => SystemConfig::get('software_name', 'Ruposhi POS'),
        ];

        return view('root.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'support_phone'    => 'nullable|string|max:20',
            'support_whatsapp' => 'nullable|string|max:20',
            'support_email'    => 'nullable|email|max:100',
            'software_name'    => 'nullable|string|max:100',
        ]);

        foreach (['support_phone', 'support_whatsapp', 'support_email', 'software_name'] as $key) {
            SystemConfig::set($key, $request->input($key));
        }

        return redirect()->back()->with('success', 'সিস্টেম সেটিংস আপডেট হয়েছে।');
    }
}
