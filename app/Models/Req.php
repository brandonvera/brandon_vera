<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Req extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'url',
        'wallet',
        'request',
    ];
}
