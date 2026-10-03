<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\UniqueConstraintViolationException;

class Card extends Model
{
    protected $fillable = ['deck_id', 'term', 'meaning'];
    
    // 保存の直前に，必ず重複判定用のハッシュを計算し直す
    protected static function booted(): void
    {
        static::creating(function (Card $card) {
            $card->export_key = (string) Str::uuid();
        });
    
        static::saving(function (Card $card) {
            $card->content_hash = self::contentHash($card->term, $card->meaning);
        });
    }

    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }

    // 英語：空白を整えて小文字にする（比較用）
    public static function normalizeTerm(string $term): string
    {
        return mb_strtolower(self::normalizeSpaces($term));
    }

    // 訳：空白だけを整える（比較用）
    public static function normalizeMeaning(string $meaning): string
    {
        return self::normalizeSpaces($meaning);
    }
    
    // 英語と訳の組み合わせから、重複判定用のハッシュを作る
    public static function contentHash(string $term, string $meaning): string
    {
        return hash('sha256', json_encode([
            self::normalizeTerm($term),
            self::normalizeMeaning($meaning),
        ]));
    }
    
    // 一意制約エラーが「同じデッキ・同じ内容」によるものか
    public static function isDuplicateContent(UniqueConstraintViolationException $e): bool
    {
        return str_contains($e->errorInfo[2] ?? '', 'content_hash');
    }

    // 連続した空白（全角スペース・タブ・改行を含む）を1つにまとめ、前後を除く
    private static function normalizeSpaces(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
