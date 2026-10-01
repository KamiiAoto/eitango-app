<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = ['deck_id', 'term', 'meaning'];
    
    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }
}
