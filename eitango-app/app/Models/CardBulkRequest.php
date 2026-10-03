<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardBulkRequest extends Model
{
    protected $fillable = ['deck_id', 'request_key', 'request_hash', 'saved_count', 'duplicate_count'];
}
