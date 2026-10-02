<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardBulkRequest extends Model
{
    protected $fillable = ['deck_id', 'request_key', 'saved_count'];
}
