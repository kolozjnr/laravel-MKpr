<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookPost extends Model
{

    use HasFactory;
    protected $fillable = ['facebook_user_id', 'message', 'created_time'];
}
