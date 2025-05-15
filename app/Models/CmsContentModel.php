<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CmsContentModel.
 * @method static \app\common\model\CmsContentModel imagesArr()
 */
class CmsContentModel extends Model
{
    protected $table = 'cms_content';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function class()
    {
        return $this->belongsTo(CmsClassModel::class, 'cid', 'id');
    }

    /**
     * 将图片字符串转成数组.
     * @param $query
     */
    public function scopeImagesArr($query)
    {
        $query->withAttr('images', function ($value) {
            if (empty($value)) {
                return [];
            }
            return explode(',', $value);
        });
    }
}
