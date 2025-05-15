<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxModel extends Model
{
    // 可根据实际情况设置表名、主键等
    protected $table = 'box';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
