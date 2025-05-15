<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
// use App\Utils\Data; // 你需要自己实现 Data 工具类或用递归

class CmsClassModel extends Model
{
    protected $table = 'cms_class';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function model()
    {
        return $this->belongsTo(CmsModelModel::class, 'model_id', 'id');
    }

    // 获取所有分类
    public function getAll()
    {
        $list = Cache::get('cache_cms_class');
        if ($list) {
            return $list;
        }
        $list = $this->select(['id','pid','class_type','model_id','model_type','title','subtitle','image','menu','link','diyname'])
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get()->toArray();
        Cache::put('cache_cms_class', $list, 60*60);
        return $list;
    }

    // 获取所有分类
    public function getChildren($pid = 0)
    {
        $list = $this->getAll();
        return Data::channelLevel($list, $pid, '', 'id', 'pid', 1);
        /* $list = Data::channelList($list,$pid,'', 'id'); */
    }

    // 获取PID下所有子类ID
    public function getChildrenIds($pid = 0)
    {
        $list = $this->getAll();
        $list = Data::channelList($list, $pid, '', 'id');
        $ids  = [];
        foreach ($list as $v) {
            $ids[] = $v['id'];
        }
        return $ids;
    }

    // 获取所有父级栏目数据
    public function getParentData($cid)
    {
        $list = $this->getAll();
        return Data::parentChannel($list, $cid, 'id', 'pid');
    }

    /**
     * 返回当前栏目数据、栏目下的所有子分类ID、所有父级栏目数据、该模型下的分类.
     * @param $cid
     * @return array
     */
    public function getCmsClassDatas($cid)
    {
        if (!is_numeric($cid) || strval(intval($cid)) !== strval($cid)) {
            $class_data = $this->with('model')->where('diyname', $cid)->first();
        } else {
            $class_data = $this->with('model')->where('id', $cid)->first();
        }
        if (!$class_data) {
            abort(404, '栏目不存在');
        }
        $cid = $class_data['id'];
        $ids = [$cid];
        // $children_ids = $this->getChildrenIds($cid);
        // $children_ids = array_merge($ids, $children_ids);
        $children_ids = $ids;
        // $parent_class_datas = $this->getParentData($cid);
        $parent_class_datas = [];
        $top_id = $cid;
        // $model_class_datas = $this->getChildren($top_id);
        $model_class_datas = [];
        return [$class_data, $children_ids, $parent_class_datas, $model_class_datas];
    }

    // 栏目TopId
    public function getTopId($cid)
    {
        $data = $this->find($cid);
        if ($data['pid'] == 0) {
            return $cid;
        }
        return $this->getTopId($data['pid']);
    }

    // 获取模板列表
    public function getTplList()
    {
        $theme = xn_cfg('cms_theme.theme', 'theme');
        // 获取文件夹里的模板
        $tpl_list = [];
        $tpl_dir  = app()->getBasePath() . 'index' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR;
        if (is_dir($tpl_dir)) {
            $dh = opendir($tpl_dir);
            while (($file = readdir($dh)) !== false) {
                // 后缀html的文件
                if (preg_match('/\.html$/', $file)) {
                    $tpl_list[]['title'] = $file;
                }
            }
            closedir($dh);
        }
        return json_encode($tpl_list);
    }
}
