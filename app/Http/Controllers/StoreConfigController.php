<?php

namespace App\Http\Controllers;

use App\Models\StoreConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreConfigController extends Controller
{
    /* ── Default payment methods (used as seed if DB is empty) ── */
    private static array $defaults = [
        ['name' => 'নগদ',                   'group' => 'নগদ'],
        ['name' => 'বিকাশ',                 'group' => 'মোবাইল ব্যাংকিং'],
        ['name' => 'নগদ মোবাইল',            'group' => 'মোবাইল ব্যাংকিং'],
        ['name' => 'রকেট',                  'group' => 'মোবাইল ব্যাংকিং'],
        ['name' => 'উপায়',                 'group' => 'মোবাইল ব্যাংকিং'],
        ['name' => 'T.T. সোনালী ব্যাংক',   'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. জনতা ব্যাংক',     'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. অগ্রণী ব্যাংক',   'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. রূপালী ব্যাংক',   'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. ন্যাশনাল ব্যাংক', 'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. উত্তরা ব্যাংক',   'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. ইসলামী ব্যাংক',   'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. ডাচবাংলা ব্যাংক', 'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. IFIC ব্যাংক',     'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. পূবালী ব্যাংক',   'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'T.T. NRB ব্যাংক RTGS', 'group' => 'ব্যাংক ট্রান্সফার'],
        ['name' => 'চেক (সাধারণ)',          'group' => 'চেক'],
        ['name' => 'চেক ডাচবাংলা',         'group' => 'চেক'],
        ['name' => 'চেক সোনালী',           'group' => 'চেক'],
        ['name' => 'মাল ফেরত',             'group' => 'অন্যান্য'],
        ['name' => 'কমিশন বাবদ',           'group' => 'অন্যান্য'],
        ['name' => 'বাকী',                 'group' => 'অন্যান্য'],
        ['name' => 'অন্যান্য',             'group' => 'অন্যান্য'],
    ];

    /* ── Get full payment methods list from DB (seed if missing) ─ */
    public static function getPaymentMethods(): array
    {
        $raw = StoreConfig::get('payment_methods');
        if ($raw !== null) {
            $arr = json_decode($raw, true);
            if (is_array($arr) && count($arr)) return $arr;
        }
        // First time: seed defaults into DB
        StoreConfig::set('payment_methods', json_encode(self::$defaults, JSON_UNESCAPED_UNICODE));
        return self::$defaults;
    }

    /* ── Grouped for Blade dropdowns: ['group' => ['name1','name2']] */
    public static function getGroupedPaymentMethods(): array
    {
        $grouped = [];
        foreach (self::getPaymentMethods() as $m) {
            $grouped[$m['group']][] = $m['name'];
        }
        return $grouped;
    }

    /* ── Legacy alias (sales/purchase custom optgroup compat) ─── */
    public static function getCustomPaymentMethods(): array
    {
        return []; // no longer used separately — full list is in getPaymentMethods()
    }

    /* ── Multimedia helpers ─────────────────────────────────── */
    public static function getMultimediaFiles(): array
    {
        if (!Storage::disk('public')->exists('multimedia')) {
            Storage::disk('public')->makeDirectory('multimedia');
        }
        $files = Storage::disk('public')->files('multimedia');
        sort($files);
        return array_values(array_map(function($f) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $videoExts = ['mp4','webm','mov'];
            $audioExts = ['mp3','wav','aac','ogg','m4a'];
            $type = in_array($ext, $videoExts) ? 'video' : (in_array($ext, $audioExts) ? 'audio' : 'image');
            return [
                'filename' => basename($f),
                'url'      => Storage::url($f),
                'type'     => $type,
            ];
        }, $files));
    }

    /* ── Store info update ───────────────────────────────────── */
    public function index()
    {
        $config              = StoreConfig::pluck('value', 'key');
        $methods             = self::getPaymentMethods();
        $groups              = array_values(array_unique(array_column($methods, 'group')));
        $multimediaFiles     = self::getMultimediaFiles();
        $multimediaEnabled   = StoreConfig::get('multimedia_enabled',  '0') === '1';
        $multimediaInterval  = (int) StoreConfig::get('multimedia_interval', '5');
        return view('store-config.index', compact(
            'config', 'methods', 'groups',
            'multimediaFiles', 'multimediaEnabled', 'multimediaInterval'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'store_name'    => 'required|string|max:255',
            'store_phone'   => 'nullable|string|max:20',
            'store_email'   => 'nullable|email',
            'store_address' => 'nullable|string',
            'currency'      => 'nullable|string|max:10',
        ]);

        foreach ($request->except(['_token', '_method']) as $key => $value) {
            StoreConfig::set($key, $value);
        }

        return redirect()->route('store-config.index')->with('success', 'স্টোর কনফিগারেশন সফলভাবে আপডেট করা হয়েছে।');
    }

    /* ── Add a payment method ────────────────────────────────── */
    public function addPaymentMethod(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'group' => 'required|string|max:100',
        ]);

        $name    = trim($request->name);
        $group   = trim($request->group);
        $methods = self::getPaymentMethods();

        $exists = collect($methods)->contains(fn($m) => $m['name'] === $name);
        if (!$exists) {
            $methods[] = ['name' => $name, 'group' => $group];
            StoreConfig::set('payment_methods', json_encode($methods, JSON_UNESCAPED_UNICODE));
        }

        return response()->json(['success' => true, 'methods' => $methods]);
    }

    /* ── Delete a payment method ─────────────────────────────── */
    public function deletePaymentMethod(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $name    = trim($request->name);
        $methods = array_values(array_filter(
            self::getPaymentMethods(),
            fn($m) => $m['name'] !== $name
        ));
        StoreConfig::set('payment_methods', json_encode($methods, JSON_UNESCAPED_UNICODE));

        return response()->json(['success' => true, 'methods' => $methods]);
    }

    /* ── Toggle multimedia on/off ─────────────────────────────── */
    public function toggleMultimedia(Request $request)
    {
        $enabled = $request->boolean('enabled') ? '1' : '0';
        StoreConfig::set('multimedia_enabled', $enabled);
        return response()->json(['success' => true, 'enabled' => $enabled === '1']);
    }

    /* ── Update slide interval ────────────────────────────────── */
    public function updateMultimediaInterval(Request $request)
    {
        $request->validate(['interval' => 'required|integer|min:1|max:120']);
        StoreConfig::set('multimedia_interval', $request->interval);
        return response()->json(['success' => true]);
    }

    /* ── Upload a multimedia file ────────────────────────────── */
    public function uploadMultimedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,mp3,wav,aac,ogg,m4a|max:51200', // 50MB
        ]);

        $file     = $request->file('file');
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = uniqid('mm_') . '.' . $ext;

        Storage::disk('public')->putFileAs('multimedia', $file, $filename);

        $videoExts = ['mp4','webm','mov'];
        $audioExts = ['mp3','wav','aac','ogg','m4a'];
        $type = in_array($ext, $videoExts) ? 'video' : (in_array($ext, $audioExts) ? 'audio' : 'image');

        return response()->json([
            'success'  => true,
            'filename' => $filename,
            'url'      => Storage::url('multimedia/' . $filename),
            'type'     => $type,
        ]);
    }

    /* ── Delete a multimedia file ────────────────────────────── */
    public function deleteMultimedia(Request $request)
    {
        $request->validate(['filename' => 'required|string|max:100']);
        $filename = basename($request->filename); // Prevent path traversal
        Storage::disk('public')->delete('multimedia/' . $filename);
        return response()->json(['success' => true, 'files' => self::getMultimediaFiles()]);
    }
}
