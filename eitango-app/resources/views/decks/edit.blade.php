<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>単語帳の編集</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <h1>単語帳の編集</h1>
    <form action="/decks/{{ $deck->id }}" method="post">
        @csrf
        @method('PATCH')
        <input type="text" name="name" value="{{ old('name', $deck->name) }}" class="@error('name') is-invalid @enderror">
        @error('name')
        <p class="error">{{ $message }}</p>
        @enderror
        <button type="submit" class="btn-primary">更新</button>
    </form>
    <a href="/decks">キャンセル</a>
</body>
</html>



