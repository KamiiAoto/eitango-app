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
        <a href="/decks/{{ $deck->id }}/edit">編集</a>
        <form action="/decks/{{ $deck->id }}" method="post" class="delete-form" data-name="{{ $deck->name }}">
            @csrf
            @method('DELETE')
            <button type="submit">削除</button>
        </form>
    @empty
        <p>単語帳はまだありません</p>
    @endforelse

    <script>
    document.querySelectorAll('.delete-form').forEach(function(form)
    {
        form.addEventListener('submit', function(event) {
            const name = this.dataset.name;
            if (!window.confirm('「' + name + '」を削除しますか？')) {
                event.preventDefault();
            }
        });
    });
    </script>

</body>
</html>