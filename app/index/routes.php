<?php

use think\facade\Route;

// cms搜索
Route::get('search$', 'cms/search');

// cms详情
Route::get('detail/<id>$', 'cms/detail')->pattern(['id' => '[\-\w]+']);

// cms栏目、列表、单页模型
Route::get('<cid>$', 'cms/index')->pattern(['cid' => '[\-\w]+']);
