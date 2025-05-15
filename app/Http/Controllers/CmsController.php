<?php

namespace App\Http\Controllers;

use App\Models\CmsClassModel;
use App\Models\CmsContentModel;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class CmsController extends BaseController
{
    protected $classModel;
    protected $contentModel;

    public function __construct()
    {
        $this->classModel = new CmsClassModel();
        $this->contentModel = new CmsContentModel();
    }

    // 栏目封面页
    public function index(Request $request, $cid)
    {
        [$class_data, $children_ids, $parent_class_datas, $model_class_datas] = $this->classModel->getCmsClassDatas($cid);
        switch ($class_data['class_type']) {
            case 0:
                return $this->home($class_data, $children_ids, $parent_class_datas, $model_class_datas);
            case 1:
                return $this->list($class_data, $children_ids, $parent_class_datas, $model_class_datas, $request);
            case 2:
                return $this->page($class_data, $children_ids, $parent_class_datas, $model_class_datas);
            case 3:
                return redirect($class_data['url']);
            default:
                abort(404, '栏目类型错误');
        }
    }

    // 内容详情页
    public function detail(Request $request, $id)
    {
        $data = $this->contentModel->find($id);
        if (empty($data)) {
            abort(404, '内容不存在');
        }
        $data->increment('view');
        [$class_data, $children_ids, $parent_class_datas, $model_class_datas] = $this->classModel->getCmsClassDatas($data['cid']);
        if ($class_data['model']) {
            // 这里建议用 DB::table 查询扩展表
        }
        // 上一条、下一条
        // 这里建议用 Eloquent 查询
        // ...
        // 视图渲染或返回 JSON，保持原逻辑
    }

    // 单页内容
    public function page($class_data, $children_ids, $parent_class_datas, $model_class_datas)
    {
        if ($class_data['class_type'] != 2) {
            abort(404, '栏目类型错误');
        }
        $cid = $class_data['id'];
        $data = $class_data;
        // 视图渲染或返回 JSON，保持原逻辑
    }

    // 搜索
    public function search(Request $request)
    {
        $keyword = $request->input('keyword');
        if (empty($keyword)) {
            abort(400, '请输入搜索关键字');
        }
        $model = $this->contentModel;
        // 这里建议用 Eloquent where 查询
        // ...
        // 视图渲染或返回 JSON，保持原逻辑
    }

    // $class_data 当前分类数据
    // $children_ids 当前分类下的子分类ID
    // $parent_class_datas 当前分类的各级父级数据
    // $model_class_datas 所属模型下的所有分类（多维数组）
    protected function home($class_data = [], $children_ids = [], $parent_class_datas = [], $model_class_datas = [])
    {
        $cid = $class_data['id'];
        // 栏目下的一级分类
        $clist = $this->classModel->where('pid', $cid)->order('sort asc, id asc')->select();

        return $this->view(cmstpl($class_data['tpl_index']), compact('cid', 'clist', 'class_data', 'children_ids', 'parent_class_datas', 'model_class_datas'));
    }

    /**
     * 内容列表页.
     * @param mixed $class_data
     * @param mixed $children_ids
     * @param mixed $parent_class_datas
     * @param mixed $model_class_datas
     * @return mixed
     */
    protected function list($class_data = [], $children_ids = [], $parent_class_datas = [], $model_class_datas = [], $request)
    {
        $param   = $request->all();
        $cid     = $class_data['id'];
        $where   = [];
        $where[] = ['a.cid', 'in', $children_ids];

        if (isset($param['keyword'])) {
            $where[] = ['a.title|a.seo_title', 'like', '%' . $param['keyword'] . '%'];
        }

        // 排序
        $orderby = 'id desc';
        if (isset($param['orderby'])) {
            if ($param['orderby'] == 'view') {
                $orderby = 'view desc';
            } elseif ($param['orderby'] == 'time') {
                $orderby = 'create_time asc';
            }
        }
        // 每页显示数量
        $limit = $class_data['limit'] > 0 ? $class_data['limit'] : 12;
        if ($class_data['model_id'] > 0) {
            // 自定义模型
            $model_table_name = $class_data['model']['table_name'];
            $list             = $this->contentModel->alias('a')
                ->join("{$model_table_name} b", 'a.id=b.content_id', 'left')
                ->with('class')
                ->where($where)
                ->order($orderby)
                ->paginate([
                    'query'     => $param,
                    'list_rows' => $limit,
                ]);
        } else {
            // 默认模型
            $list = $this->contentModel
                ->alias('a')
                ->with('class')
                ->where($where)
                ->order($orderby)
                ->paginate([
                    'query'     => $param,
                    'list_rows' => $limit,
                ]);
        }
        // $list = $list->all();
        $list = $list->each(function ($item, $key) {
            $item['url'] = url('cms/detail', ['id' => $item['id']]);
            return $item;
        });

        // $list-内容列表
        // $cid-当前栏目ID
        // $class_data-当前栏目数据
        // $parent_class_datas-所有父级栏目数据
        // $model_class_datas-该模型下的分类 一般用于左右副导航
        return $this->view(cmstpl($class_data['tpl_list']), compact('list', 'cid', 'class_data', 'parent_class_datas', 'model_class_datas'));
    }
}
