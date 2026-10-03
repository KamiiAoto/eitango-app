<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function store(Request $request, $deck_id)
    {
        $deck = $request->user()->decks()->findOrFail($deck_id);
        $validated = $request->validate([
            'term'    => 'required|string|max:150',
            'meaning' => 'required|string|max:1000',
        ]);
        try {
            $deck->cards()->create($validated);
        } catch (UniqueConstraintViolationException $e) {
            if (!Card::isDuplicateContent($e)) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'term' => 'このデッキには，同じ英語・訳のカードがすでに登録されています',
            ]);
        }
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
        try {
            $card->update($validated);
        } catch (UniqueConstraintViolationException $e) {
            if (!Card::isDuplicateContent($e)) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'term' => 'このデッキには，同じ英語・訳のカードがすでに登録されています',
            ]);
        }
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
        $contents = array_map(function ($card) {
            return [$card['term'], $card['meaning']];
        }, $validated['cards']);
        $hash = hash('sha256', json_encode($contents));

        try {
            $result = DB::transaction(function () use ($deck, $validated, $hash) {
                // 同じキーで保存済みなら，保存せず初回の結果を返す
                $existing = $deck->cardBulkRequests()
                    ->where('request_key', $validated['request_key'])
                    ->first();
                if ($existing) {
                    return $this->resultForRetry($existing, $hash);
                }

                // 先にキーを記録する（同時に届いた２つ目はここで一意制約エラーになる）
                $record = $deck->cardBulkRequests()->create([
                    'request_key'     => $validated['request_key'],
                    'request_hash'    => $hash,
                    'saved_count'     => 0,
                    'duplicate_count' => 0    
                ]);
                
                $savedCount = 0;
                $duplicateCount = 0;
                foreach ($validated['cards'] as $card) {
                    try {
                        $deck->cards()->create([
                            'term'    => $card['term'],
                            'meaning' => $card['meaning'],
                        ]);
                        $savedCount++;
                    } catch (UniqueConstraintViolationException $e) {
                        if (!Card::isDuplicateContent($e)) {
                            throw $e;
                        }
                        $duplicateCount++;
                    }
                }

                // 確定した件数を，カードと同じトランザクションで記録する
                $record->update([
                    'saved_count'     => $savedCount,
                    'duplicate_count' => $duplicateCount,
                ]);

                return ['saved_count' => $savedCount, 'duplicate_count' => $duplicateCount];
            });
        } catch (UniqueConstraintViolationException $e) {
            // 要求キーの衝突：先に処理された方の結果を返す
            $existing = $deck->cardBulkRequests()
                ->where('request_key', $validated['request_key'])
                ->first();
            if (!$existing) {
                throw $e;   // 要求キーの衝突ではない一意制約エラーなので，そのまま投げる
            }
            $result = $this->resultForRetry($existing, $hash);
        }
        return response()->json($result);
    }

    private function resultForRetry($existing, $hash)
    {
        abort_if($existing->request_hash !== $hash, 409, 'この保存キーは別の内容で使用済みです');
        return[
            'saved_count'     => $existing->saved_count,
            'duplicate_count' => $existing->duplicate_count,
        ];
    }
}
