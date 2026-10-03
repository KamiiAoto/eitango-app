<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeckController extends Controller
{
    public function index(Request $request) 
    {
        $decks = $request->user()->decks()->withCount('cards')->get();
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

    public function show(Request $request, $id)
    {
        $deck = $request->user()->decks()->findOrFail($id);
        $input = $request->input('q', '');
        $q = is_string($input) ? trim($input) : '';

        $query = $deck->cards()->orderBy('id');
        if ($q !== '') {
            $query->where(function ($query) use ($q) {
                $query->where('term', 'like', '%' . $q . '%')
                    ->orWhere('meaning', 'like', '%' . $q . '%');
            });
        }
        $cards = $query->paginate(50)->withQueryString();
        return view('decks.show', ['deck' => $deck, 'cards' => $cards, 'q' => $q]);
    }

    public function scan(Request $request, $id)
    {
        $deck = $request->user()->decks()->findOrFail($id);
        return view('decks.scan', ['deck' => $deck]);
    }

    public function  export(Request $request, $id)
    {
        $deck = $request->user()->decks()->findOrFail($id);
        $cards = $deck->cards()->orderBy('id')->get();

        $lines = ['#separator:Tab', '#html:false'];
        foreach ($cards as $card) {
            $lines[] = implode("\t", [
                $this->ankiField($card->export_key),
                $this->ankiField($card->term),
                $this->ankiField($card->meaning),
            ]);
        }
        $content = implode("\n", $lines) . "\n";

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'deck-' . $deck->id . '-anki.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    // Ankiの1項目分の文字列を作る：タブ・改行を空白にし，" を "" にして全体を " で囲む
    private function ankiField(string $value): string
    {
        $value = preg_replace('/[\t\r\n]+/u', ' ', $value);
        return '"' . str_replace('"', '""', $value) . '"';
    }

}
