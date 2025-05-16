<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\BoxModel;
use App\Models\BoxHomeModel;
use App\Models\BoxGameLinkModel;
use App\Models\BoxCategoryModel;
use App\Models\BoxLogInstallModel;
use App\Models\BoxLogUninstallModel;
use App\Models\BoxLogActiveModel;
use App\Models\GameModel;
use Illuminate\Support\Facades\Config;

class IndexController extends BaseController
{
    protected $box_model;
    protected $box_home_model;
    protected $box_game_link_model;
    protected $box_log_install_model;
    protected $box_log_uninstall_model;
    protected $box_log_active_model;
    protected $gameHost;

    public function __construct()
    {
        $this->box_model = new BoxModel();
        $this->box_home_model = new BoxHomeModel();
        $this->box_game_link_model = new BoxGameLinkModel();
        $this->box_log_install_model = new BoxLogInstallModel();
        $this->box_log_uninstall_model = new BoxLogUninstallModel();
        $this->box_log_active_model = new BoxLogActiveModel();
        $this->gameHost = env('GAME_HOST', 'http://111.22.161.226:18080');
    }

    public function index(Request $request)
    {
        $id = $request->input('box_id', 1);
        $gameModel = new GameModel();
        $box = $this->box_model->where('id', $id)->first();
        if ($box) {
            $box_home_list = $this->box_home_model->where('box_id', $box['id'])->get();
            foreach ($box_home_list as $box_home) {
                $box_game_link_list = $this->box_game_link_model->where('box_id', $box['id'])->where('home_id', $box_home['id'])->get();
                $gameids = $box_game_link_list->pluck('game_id')->toArray();
                $game_list = $gameModel->whereIn('id', $gameids)->select('id', 'flag', 'category', 'title', 'game_logo', 'game_url')->get()->toArray();
                foreach ($game_list as &$game) {
                    $game['category'] = explode(',', $game['category']);
                    $game['game_url'] = $this->gameHost . $game['game_url'];
                    $game['game_logo'] = $this->gameHost . $game['game_logo'];
                }
                $box_home['game_list'] = $game_list;
            }
            $box['box_home_list'] = $box_home_list;
        }
        return response()->json(['code' => 0, 'msg' => '', 'data' => $box]);
    }

    public function getCategory(Request $request)
    {
        $id = $request->input('box_id', 1);
        $categoryModel = new BoxCategoryModel();
        $game_list = $categoryModel->where('box_id', $id)->get()->toArray();
        foreach ($game_list as &$game) {
            $game['icon'] = $this->gameHost . $game['icon'];
        }
        return response()->json(['code' => 0, 'msg' => '', 'data' => $game_list]);
    }

    public function getGameListByCategory(Request $request)
    {
        $gameModel = new GameModel();
        $box_id = $request->input('box_id', 1);
        $category_id = $request->input('category_id', 0);
        $box_game_link_list = $this->box_game_link_model->where('box_id', $box_id)->where('category_id', $category_id)->get();
        $gameids = $box_game_link_list->pluck('game_id')->toArray();
        $game_list = [];
        if (!empty($gameids)) {
            $game_list = $gameModel->whereIn('id', $gameids)->select('id', 'flag', 'category', 'title', 'game_logo', 'game_url')->get()->toArray();
        }
        foreach ($game_list as &$game) {
            $game['category'] = explode(',', $game['category']);
            $game['game_url'] = $this->gameHost . $game['game_url'];
            $game['game_logo'] = $this->gameHost . $game['game_logo'];
        }
        return response()->json(['code' => 0, 'msg' => '', 'data' => $game_list]);
    }

    public function searchGamesByName(Request $request)
    {
        $gameModel = new GameModel();
        $box_id = $request->input('box_id', 1);
        $keyword = $request->input('keyword', '');
        $box_game_link_list = $this->box_game_link_model->where('box_id', $box_id)->get();
        $gameids = $box_game_link_list->pluck('game_id')->toArray();
        $query = $gameModel->whereIn('id', $gameids);
        if ($keyword !== '') {
            $query = $query->where('title', 'like', "%$keyword%");
        }
        $game_list = $query->select('id', 'flag', 'category', 'title', 'game_logo', 'game_url')->get()->toArray();
        foreach ($game_list as &$game) {
            $game['category'] = explode(',', $game['category']);
            $game['game_url'] = $this->gameHost . $game['game_url'];
            $game['game_logo'] = $this->gameHost . $game['game_logo'];
        }
        return response()->json(['code' => 0, 'msg' => '', 'data' => $game_list]);
    }

    public function install(Request $request)
    {
        $data = $request->json()->all();
        if (!$data) {
            $data = $request->only(['app_id', 'channel', 'device_id', 'platform', 'timestamp', 'version', 'sign', 'sys_version']);
            $key = $this->box_model->where('id', $data['app_id'])->value('key');
            $data['sign'] = md5("app_id={$data['app_id']}&channel={$data['channel']}&device_id={$data['device_id']}&platform={$data['platform']}&sys_version={$data['sys_version']}&timestamp={$data['timestamp']}&version={$data['version']}&key={$key}");
        }
        // Redis 队列
        Cache::store('redis')->rpush('box_install', json_encode($data));
        return response()->json(['code' => 0, 'type' => 'install', 'msg' => 'success']);
    }

    public function uninstall(Request $request)
    {
        $data = $request->json()->all();
        if (!$data) {
            $data = $request->only(['app_id', 'channel', 'device_id', 'platform', 'timestamp', 'version', 'sign', 'sys_version']);
        }
        Cache::store('redis')->rpush('box_uninstall', json_encode($data));
        return response()->json(['code' => 0, 'type' => 'uninstall', 'msg' => 'success']);
    }

    public function active(Request $request)
    {
        $data = $request->json()->all();
        if (!$data) {
            $data = $request->only(['app_id', 'channel', 'device_id', 'platform', 'timestamp', 'version', 'sign', 'sys_version']);
        }
        Cache::store('redis')->rpush('box_active', json_encode($data));
        return response()->json(['code' => 0, 'type' => 'active', 'msg' => 'success']);
    }

    public function syncLog()
    {
        $active_count = $this->processLogItems('box_active');
        $uninstall_count = $this->processLogItems('box_uninstall');
        $install_count = $this->processLogItems('box_install');
        return response()->json([
            'code' => 0,
            'msg' => '同步成功',
            'data' => [
                'active' => $active_count,
                'uninstall' => $uninstall_count,
                'install' => $install_count
            ]
        ]);
    }

    private function processLogItems($key)
    {
        $redis = Cache::store('redis');
        $length = $redis->llen($key);
        $successCount = 0;
        for ($i = 0; $i < $length; $i++) {
            $item = $redis->lpop($key);
            if ($item) {
                $result = $this->saveLog($key, $item);
                if ($result) {
                    $successCount++;
                }
            }
        }
        return $successCount;
    }

    private function saveLog($type, $item)
    {
        if (is_string($item)) {
            $item = json_decode($item, true);
        }
        $requiredKeys = ['app_id', 'channel', 'device_id', 'platform', 'timestamp', 'version', 'sign'];
        foreach ($requiredKeys as $requiredKey) {
            if (!isset($item[$requiredKey])) {
                Log::error("数据验证失败，缺少必要参数：{$requiredKey}");
                return false;
            }
        }
        $is_verify = $this->verify($item);
        if ($is_verify) {
            $model = null;
            $saved = null;
            if ($type == 'box_install') {
                $model = $this->box_log_install_model;
                $saved = $model->where('app_id', $item['app_id'])->where('device_id', $item['device_id'])->first();
            }
            if ($type == 'box_uninstall') {
                $model = $this->box_log_uninstall_model;
                $saved = $model->where('app_id', $item['app_id'])->where('device_id', $item['device_id'])->first();
            }
            if ($type == 'box_active') {
                $model = $this->box_log_active_model;
                $saved = $model->where('app_id', $item['app_id'])->where('device_id', $item['device_id'])->where('create_date', date('Y-m-d'))->first();
            }
            if ($model == null) {
                return false;
            }
            if (!$saved) {
                $model->create([
                    'type' => $type,
                    'app_id' => $item['app_id'],
                    'channel' => $item['channel'],
                    'device_id' => $item['device_id'],
                    'platform' => $item['platform'],
                    'timestamp' => $item['timestamp'],
                    'version' => $item['version'],
                    'sys_version' => $item['sys_version'] ?? '',
                    'create_date' => date('Y-m-d'),
                    'sign' => $item['sign'],
                ]);
                return true;
            }
            return true;
        }
        return false;
    }

    private function verify($item)
    {
        if (!is_array($item)) {
            return false;
        }
        $requiredKeys = ['app_id', 'channel', 'device_id', 'platform', 'timestamp', 'version', 'sign'];
        foreach ($requiredKeys as $key) {
            if (!isset($item[$key])) {
                return false;
            }
        }
        $app_id = $item['app_id'];
        $channel = $item['channel'];
        $device_id = $item['device_id'];
        $platform = $item['platform'];
        $timestamp = $item['timestamp'];
        $version = $item['version'];
        $sys_version = $item['sys_version'] ?? '';
        $receivedSign = $item['sign'];
        $box = $this->box_model->where('id', $app_id)->first();
        if ($box) {
            $key = $box['key'] ?? '';
            $signStr = "app_id={$app_id}&channel={$channel}&device_id={$device_id}&platform={$platform}&sys_version={$sys_version}&timestamp={$timestamp}&version={$version}&key={$key}";
            $calculatedSign = md5($signStr);
            if ($calculatedSign == $receivedSign) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
