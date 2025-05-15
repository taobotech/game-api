<?php

namespace App\Http\Controllers;

use App\Models\TongjiModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Lumen\Routing\Controller as BaseController;

class TongjiController extends BaseController
{
    public function add(Request $request)
    {
        $s = md5($request->ip() . $request->input('url'));
        if (Cache::has($s)) {
            return;
        }

        $referrer = $request->input('referrer');
        if (!empty($referrer)) {
            $domain = strtolower(parse_url($referrer, PHP_URL_HOST));
            if (strpos($domain, 'baidu.com') !== false) {
                $engine = '百度';
            } elseif (strpos($domain, 'google') !== false) {
                $engine = '谷歌';
            } elseif (strpos($domain, 'sogou.com') !== false) {
                $engine = '搜狗';
            } elseif (strpos($domain, 'bing.com') !== false) {
                $engine = '必应';
            } elseif (strpos($domain, 'so.com') !== false) {
                $engine = '360';
            } elseif (strpos($domain, $request->getHost()) !== false) {
                $engine = '站内';
            } else {
                $engine = '其他';
            }
        } else {
            $engine = '直接输入';
        }

        $data = [
            'app'         => $request->input('app'),
            'url'         => $request->input('url'),
            'referrer'    => $referrer,
            'engine'      => $engine,
            'ip'          => $request->ip(),
            'client'      => $request->header('User-Agent'),
            'create_time' => time(),
        ];
        $model     = new TongjiModel();
        $cache_key = $model->getCacheKey();
        $items     = Cache::get($cache_key, []);
        $items[]   = json_encode($data);
        Cache::set($cache_key, $items);
        Cache::set($s, 1, 60);
        // 这里建议用 return response()->json(['success' => true]);
        return response()->json(['success' => true]);
    }
}
