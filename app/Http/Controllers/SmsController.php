<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SmsLog;
use App\Models\StoreConfig;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function index(Request $request)
    {
        $smsApiKey   = StoreConfig::get('sms_api_key',   '');
        $smsSenderId = StoreConfig::get('sms_sender_id', '');
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone', 'due_amount']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'phone', 'due_amount']);

        $logs = SmsLog::with('sender')
            ->latest()
            ->paginate(20);

        $templates = $this->templates();

        return view('sms.index', compact('customers', 'suppliers', 'logs', 'templates', 'smsApiKey', 'smsSenderId'));
    }

    public function send(Request $request, SmsService $sms)
    {
        $request->validate([
            'message'    => 'required|string|max:640',
            'recipients' => 'required|array|min:1',
        ]);

        $recipients = [];
        foreach ($request->recipients as $r) {
            // Each entry: "name||phone"
            [$name, $phone] = explode('||', $r . '||', 3);
            if (!empty(trim($phone))) {
                $recipients[] = ['name' => trim($name), 'phone' => trim($phone)];
            }
        }

        if (empty($recipients)) {
            return back()->with('error', 'কোনো বৈধ নম্বর পাওয়া যায়নি।');
        }

        $result = $sms->sendBulk($recipients, $request->message);

        $msg = "মোট {$result['sent']}টি SMS পাঠানো হয়েছে।";
        if ($result['failed'] > 0) {
            $msg .= " {$result['failed']}টি ব্যর্থ হয়েছে।";
        }

        return back()->with($result['failed'] === count($recipients) ? 'error' : 'success', $msg);
    }

    public function sendCustom(Request $request, SmsService $sms)
    {
        $request->validate([
            'message' => 'required|string|max:640',
            'numbers' => 'required|string',
        ]);

        // Parse comma/newline separated numbers
        $numbers = preg_split('/[\s,]+/', trim($request->numbers));
        $numbers = array_filter(array_map('trim', $numbers));

        if (empty($numbers)) {
            return back()->with('error', 'কোনো নম্বর দেওয়া হয়নি।');
        }

        $recipients = array_map(fn($n) => ['name' => null, 'phone' => $n], $numbers);
        $result = $sms->sendBulk($recipients, $request->message);

        $msg = "মোট {$result['sent']}টি SMS পাঠানো হয়েছে।";
        if ($result['failed'] > 0) $msg .= " {$result['failed']}টি ব্যর্থ হয়েছে।";

        return back()->with($result['failed'] === count($recipients) ? 'error' : 'success', $msg);
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'sms_api_key'   => 'nullable|string|max:200',
            'sms_sender_id' => 'nullable|string|max:50',
        ]);

        StoreConfig::set('sms_api_key',   trim($request->sms_api_key   ?? ''));
        StoreConfig::set('sms_sender_id', trim($request->sms_sender_id ?? ''));

        return back()->with('success', 'SMS সেটিংস সংরক্ষণ হয়েছে।');
    }

    public function destroyLog(SmsLog $smsLog)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        $smsLog->delete();
        return back()->with('success', 'লগ মুছে ফেলা হয়েছে।');
    }

    private function templates(): array
    {
        return [
            ['label' => 'বাকী পরিশোধের অনুরোধ',  'text' => 'প্রিয় {নাম}, আপনার কাছে ৳{পরিমাণ} বাকী আছে। অনুগ্রহ করে দ্রুত পরিশোধ করুন। ধন্যবাদ।'],
            ['label' => 'বিক্রয় নিশ্চিতকরণ',      'text' => 'প্রিয় {নাম}, আপনার ৳{পরিমাণ} টাকার অর্ডার গ্রহণ করা হয়েছে। ধন্যবাদ।'],
            ['label' => 'পেমেন্ট গ্রহণ',            'text' => 'প্রিয় {নাম}, আমরা ৳{পরিমাণ} টাকা পেয়েছি। আপনার বাকী: ৳{বাকী}। ধন্যবাদ।'],
            ['label' => 'স্বাগত বার্তা',             'text' => 'প্রিয় {নাম}, আমাদের সাথে থাকার জন্য আপনাকে স্বাগত জানাই। যেকোনো প্রয়োজনে যোগাযোগ করুন।'],
            ['label' => 'কাস্টম বার্তা',             'text' => ''],
        ];
    }
}
