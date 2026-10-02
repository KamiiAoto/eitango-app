<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deck extends Model
{
    protected $fillable = ['user_id', 'name']; // Mass Asignment 対策
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }
    
    public function cardBulkRequests()
    {
        return $this->hasMany(CardBulkRequest::class);
    }
}
