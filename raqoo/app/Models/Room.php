<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    // fillableはseederでcreate()でデータを入れるときに、このmodelで指定したカラムだけを保存する
    protected $fillable = ['name', 'status'];
}
