<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * রেকর্ড মুছে ফেলার পর ফেরত পাঠানো — `back()`-এর নিরাপদ রূপ।
     *
     * ⚠️ অ্যাডমিন যদি মুছে ফেলা রেকর্ডের নিজের পেজ থেকেই (যেমন মেমো
     * `/sales/3004` বা তার `/edit`) "ডিলিট অনুমোদন" চাপে, তাহলে `back()`
     * সেই মুছে যাওয়া পেজেই ফেরত পাঠাত → ৪০৪। তখন তালিকায় পাঠাই; অন্য
     * পেজ (অনুমোদন তালিকা, বিক্রয় তালিকা) থেকে এলে আগের মতোই সেখানে।
     */
    protected function backAfterDelete(string $recordUrl, string $fallbackRoute): RedirectResponse
    {
        $prevPath   = rtrim((string) parse_url(url()->previous(), PHP_URL_PATH), '/');
        $recordPath = rtrim((string) parse_url($recordUrl, PHP_URL_PATH), '/');

        // `/sales/30` যেন `/sales/3004`-এর সাথে না মেলে — তাই হুবহু মিল অথবা `/…`
        $onDeletedPage = $prevPath === $recordPath
            || str_starts_with($prevPath, $recordPath . '/');

        return $onDeletedPage ? redirect()->route($fallbackRoute) : back();
    }
}
