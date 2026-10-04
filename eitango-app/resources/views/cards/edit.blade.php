<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>カード編集</title>
</head>
<body>
    <h1>カード編集</h1>
    <form action="/cards/{{ $card->id }}" method="post">
        @csrf
        @method('PATCH')
        <div>
            <input type="text" name="term" value="{{ old('term', $card->term) }}" class="@error('term') is-invalid @enderror">
            @error('term')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <input type="text" name="meaning" value="{{ old('meaning', $card->meaning) }}" class="@error('meaning') is-invalid @enderror">
            @error('meaning')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="btn-primary">更新</button>
    </form>
    <a href="/decks/{{ $card->deck_id }}">キャンセル</a>
</body>
</html>