<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;

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

    public function bulkStore(Request $request, $deck_id)
    {
        $deck = $request->user()->decks()->findOrFail($deck_id);
        $validated = $request->validate([
            'request_key'     => 'required|uuid',
            'cards'           => 'required|array|min:1|max:50',
            'cards.*.id'      => 'required|integer',
            'cards.*.term'    => 'required|string|max:150',
            'cards.*.meaning' => 'required|string|max:1000',
        ]);

        try {
            $savedCount = DB::transaction(function () use ($deck, $validated) {
                // 同じキーで保存済みなら，保存せず前回の件数を返す
                $existing = $deck->cardBulkRequests()
                    ->where('request_key', $validated['request_key'])
                    ->first();
                if ($existing) {
                    return $existing->saved_count;
                }

                // 先にキーを記録する（同時に届いた２つ目はここで一意制約エラーになる）
                $deck->cardBulkRequests()->create([
                    'request_key' => $validated['request_key'],
                    'saved_count' => count($validated['cards']),
                ]);
                foreach ($validated['cards'] as $card) {
                    $deck->cards()->create([
                        'term'    => $card['term'],
                        'meaning' => $card['meaning'],
                    ]);
                }

                return count($validated['cards']);
            });
        } catch (UniqueConstraintViolationException $e) {
            // ほぼ同時に届いた同じ要求：先に処理された方の結果を返す
            $savedCount = $deck->cardBulkRequests()
                ->where('request_key', $validated['request_key'])
                ->value('saved_count');
        }

        return response()->json(['saved_count' => $savedCount]);
    }

}
