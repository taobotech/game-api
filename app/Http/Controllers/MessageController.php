<?php

namespace App\Http\Controllers;

use App\Models\MessageModel;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class MessageController extends BaseController
{
    public function add(Request $request)
    {
        $param = $request->all();
        if (empty($param['tel']) && empty($param['email'])) {
            return response()->json(['success' => false, 'msg' => '请输入电话或邮箱至少其中一项']);
        }
        if (empty($param['title']) || empty($param['username']) || empty($param['msg'])) {
            return response()->json(['success' => false, 'msg' => '请填写完整信息']);
        }
        if (strlen($param['msg']) < 10) {
            return response()->json(['success' => false, 'msg' => '留言内容不能少于10个字']);
        }
        if (!empty($param['tel'])) {
            if (!preg_match('/^1[3456789]\d{9}$/', $param['tel'])) {
                return response()->json(['success' => false, 'msg' => '请输入正确的手机号']);
            }
        }
        if (!empty($param['email'])) {
            if (!preg_match('/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $param['email'])) {
                return response()->json(['success' => false, 'msg' => '请输入正确的邮箱']);
            }
        }
        $data = [
            'title'       => $param['title'],
            'username'    => $param['username'],
            'tel'         => $param['tel'],
            'email'       => $param['email'],
            'msg'         => $param['msg'],
            'create_time' => time(),
        ];
        if (MessageModel::create($data)) {
            return response()->json(['success' => true, 'msg' => '提交成功']);
        } else {
            $this->error('提交失败');
        }
    }
}
