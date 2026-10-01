<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $deck->name }}</title>
</head>
<body>
    <h1>{{ $deck->name }}</h1>
    <a href="/decks">← 一覧に戻る</a>
    <form action="/decks/{{ $deck->id }}/cards" method="post">
        @csrf
        <div>
            <input type="text" name="term" placeholder="英単語・熟語" value="{{ old('term') }}">
            @error('term')
                <p>{{ $message }}</p>
            @enderror
        </div>
        <div>
            <input type="text" name="meaning" placeholder="日本語訳" value="{{ old('meaning') }}">
            @error('meaning')
                <p>{{ $message }}</p>
            @enderror
        </div>
        <button type="submit">追加</button>
    </form>
    @forelse ($cards as $card)
        <p>{{ $card->term }} - {{ $card->meaning }}</p>
    @empty
        <p>カードはまだありません</p>
    @endforelse
</body>
</html>