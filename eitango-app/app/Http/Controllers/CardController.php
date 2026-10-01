<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;

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

    public function edit(Request $request, $id)
    {
        $card = Card::findOrFail($id);
        abort_if($card->deck->user_id !== $request->user()->id, 404);
        return view('cards.edit', ['card' => $card]);
    }

    public function update(Request $request, $id)
    {
        $card = Card::findOrFail($id);
        abort_if($card->deck->user_id !== $request->user()->id, 404);
        $validated = $request->validate([
            'term'      => 'required|string|max:150',
            'meaning'   => 'required|string|max:1000',
        ]);
        $card->update($validated);
        return redirect('/decks/' . $card->deck_id);
    }

    public function destroy(Request $request, $id)
    {
        $card = Card::findOrFail($id);
        abort_if($card->deck->user_id !== $request->user()->id, 404);
        $deck_id = $card->deck_id;
        $card->delete();
        return redirect('/decks/' . $deck_id);
    }
}
