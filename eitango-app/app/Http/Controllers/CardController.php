<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CardController extends Controller
{
    public function store(Request $request, $deck_id)
    {
        $deck = $request->user()->decks()->findOrFail($deck_id);
        $validated = $request->validate([
            'term'    => 'required|string|max:150',
            'meaning' => 'required|string|max:1000',
        ]);
        $deck->cards()->create($validated);
        return redirect('/decks/' . $deck_id);
    }
}
