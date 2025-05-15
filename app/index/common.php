<?php

use App\Models\BannerModel;
use App\Models\CmsClassModel;
use App\Models\CmsContentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use utils\Data;

/**
 * 获取菜单数据.
 * @return array
 */
function cms_get_menu()
{
    $list = Cache::get('cache_menu');
    if ($list) {
        return $list;
    }

    $cmsClassModel = new CmsClassModel();
    $list = $cmsClassModel->select(['id','pid','class_type','model_id','model_type','title','image','menu','link','diyname'])
        ->where('menu', 1)
        ->orderBy('sort', 'asc')
        ->orderBy('id', 'asc')
        ->get()->toArray();
    // 这里需要你自己实现 channelLevel 方法，或用递归处理树结构
    // $list = Data::channelLevel($list, 0, '', 'id', 'pid', 1);
    Cache::put('cache_menu', $list, 60*60);
    return $list;
}

/**
 * 获取底部菜单数据.
 * @return array
 */
function cms_get_f_menu()
{
    $list = Cache::get('cache_f_menu');
    if ($list) {
        return $list;
    }

    $cmsClassModel = new CmsClassModel();
    $list = $cmsClassModel->select(['id','pid','class_type','model_id','model_type','title','image','menu','link','diyname'])
        ->where('f_menu', 1)
        ->orderBy('sort', 'asc')
        ->orderBy('id', 'asc')
        ->get()->toArray();
    // $list = Data::channelLevel($list, 0, '', 'id', 'pid', 1);
    Cache::put('cache_f_menu', $list, 60*60);
    return $list;
}

/**
 * 获取分类URL.
 * @param mixed $class_data
 */
function cms_class_url($class_data)
{
    $cid = $class_data['diyname'] != '' ? $class_data['diyname'] : $class_data['id'];
    if ($class_data['class_type'] == 3) {
        return $class_data['link'];
    }
    // 这里建议直接拼接URL或用 route() 生成
    return url('/cms/index', ['cid' => $cid]);
}

/**
 * 获取列表数据.
 * @param mixed $cid
 * @param mixed $limit
 * @param mixed $where
 * @param mixed $field
 * @param mixed $order
 */
function cms_list($cid, $limit = 20, $where = [], $field = ['id','cid','title','intro','image','is_hot','view','created_at','updated_at'], $order = ['id' => 'desc'])
{
    $cmsClassModel = new CmsClassModel();
    $cmsContentModel = new CmsContentModel();
    $cms_class = $cmsClassModel->with('model')->find($cid);

    $ids = [$cid];
    // 这里需要你自己实现 getChildrenIds 方法
    // $childrenIds = $cmsClassModel->getChildrenIds($cid);
    // $ids = array_merge($ids, $childrenIds);

    if ($cms_class && $cms_class->model_id > 0) {
        $model_table_name = $cms_class->model->table_name;
        $list = $cmsContentModel
            ->select($field)
            ->leftJoin($model_table_name, 'cms_content.id', '=', $model_table_name.'.content_id')
            ->whereIn('cid', $ids)
            ->where($where)
            ->with('class')
            ->limit($limit)
            ->orderBy(key($order), current($order))
            ->get()->toArray();
    } else {
        $list = $cmsContentModel
            ->select($field)
            ->whereIn('cid', $ids)
            ->where($where)
            ->with('class')
            ->limit($limit)
            ->orderBy(key($order), current($order))
            ->get()->toArray();
    }
    foreach ($list as &$v) {
        $v['url'] = url('/cms/detail', ['id' => $v['id']]);
    }
    return $list;
}

function cms_list2($table_name, $limit = 20, $where = '1', $field = '*', $order = 'id desc')
{
    $table_name = env('DB_PREFIX', '') . Str::snake($table_name);
    $sql = "select {$field} from {$table_name} where {$where} order by {$order} limit {$limit}";
    return DB::select($sql);
}

// 生成修改cfg text的快捷方式
function cms_cfg($field, $default = '')
{
    $is_img = substr($field, -4) == '_img';
    $cfg_file = '_text';
    // 这里建议你用 config() 或 env() 获取主题路径
    $theme = config('cms_theme.theme', 'theme1');
    $path = base_path('resources/views/' . $theme . '/_text.php');
    $data = include $path;
    $text = $data[$field] ?? '';
    if (empty($text)) {
        if ($default == '' && $is_img) {
            $default = 'data:image/png;base64,...'; // 省略base64
        }
        $text = $default == '' ? $field : $default;
    }
    if (strpos($text, '</p>') === false) {
        $text = str_replace("\n", '<br>', $text);
    }
    $text = str_replace(' ', '&nbsp;', $text);
    echo $text;
}

function cms_textarea_br($text)
{
    $text = str_replace("\n", '<br>', $text);
    echo $text;
}

/**
 * 获取banner、幻灯片数据.
 * @param $type
 * @param $limit 0 - 不限制
 * @return \app\common\model\BannerModel[]|array|\think\Collection
 */
function cms_get_banner($type, $limit = 0)
{
    $model = new BannerModel();
    if ($limit > 0) {
        $model = $model->limit($limit);
    }
    return $model->where('type', $type)->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();
}

/**
 * 截取文章内容.
 * @param $content
 * @param int $limit
 * @return string
 */
function cms_substr($content, $limit = 200)
{
    $contentWithoutTags = strip_tags($content);
    $limitedContent = mb_strcut($contentWithoutTags, 0, $limit, 'utf-8');
    return $limitedContent . '...';
}

function cmstpl($tpl)
{
    $tpl = str_replace('.html', '', $tpl);
    return config('cms_theme.theme', 'theme1') . '/' . $tpl;
}
