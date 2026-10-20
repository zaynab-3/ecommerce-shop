<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CookieNoticeController extends Controller
{
    public const COOKIE = 'shop_cookie_notice';

    public const VERSION = '1';

    public function __invoke(Request $request)
    {
        $request->validate(['version' => ['required', Rule::in([self::VERSION])]]);

        // An acknowledgement of essential cookies, not consent to optional tracking.
        $cookie = cookie(self::COOKIE, self::VERSION, 60 * 24 * 180, '/', null,
            $request->isSecure() || (bool) config('session.secure'), true, false, 'lax');

        return ($request->expectsJson() ? response()->json(['acknowledged' => true]) : back())
            ->withCookie($cookie);
    }
}
