<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageModel extends Model
{
    protected $table = 'message';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}
