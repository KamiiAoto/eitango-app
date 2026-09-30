<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>単語帳一覧</title>
</head>
<body>
    <h1>単語帳一覧</h1>

    <form action="/decks" method="post">
        @csrf
        <input type="text" name="name" placeholder="単語帳の名前">
        @error('name')
            <p>{{ $message }}</p>
        @enderror
        <button type="submit">作成</button>
    </form>
    @forelse ($decks as $deck)
        <p>{{ $deck->name }}</p>
    @empty
        <p>単語帳はまだありません</p>
    @endforelse

</body>
</html>