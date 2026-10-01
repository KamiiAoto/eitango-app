<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeckController extends Controller
{
    public function index(Request $request) 
    {
        $decks = $request->user()->decks()->get();
        return view('decks.index', ['decks' => $decks]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:100',
        ]);
        
        $request->user()->decks()->create($validated);

        return redirect('/decks');
    }

    public function edit(Request $request, $id)
    {
        $deck = $request->user()->decks()->findOrFail($id);
        return view('decks.edit', ['deck' => $deck]);
    }

    public function update(Request $request, $id)
    {
        $deck = $request->user()->decks()->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:100',
        ]);
        $deck->update($validated);
        return redirect('/decks');
    }

    public function destroy(Request $request, $id)
    {
        $deck = $request->user()->decks()->findOrFail($id);
        $deck->delete();
        return redirect('/decks');
    }
}
