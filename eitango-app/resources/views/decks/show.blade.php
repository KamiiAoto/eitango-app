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
    <form action="/decks/{{ $deck->id }}" method="get">
        <input type="text" name="q" value="{{ $q }}" placeholder="英語または日本語で検索">
        <button type="submit">検索</button>
        @if ($q !== '')
            <a href="/decks/{{ $deck->id }}">検索解除</a>
        @endif
    </form>
    @forelse ($cards as $card)
        <p>{{ $card->term }} - {{ $card->meaning }}</p>
        <a href="/cards/{{ $card->id }}/edit">編集</a>
        <form action="/cards/{{ $card->id }}" method="post" class="card-delete-form" data-term="{{ $card->term }}">
            @csrf
            @method('DELETE')
            <button type="submit">削除</button>
        </form>
    @empty
        @if ($q !=='')
            <p>「{{ $q }}」に一致するカードはありません</p>
        @else
            <p>カードはまだありません</p>
        @endif
    @endforelse
    {{ $cards->links() }}

    <script>
        document.querySelectorAll('.card-delete-form').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                const term = this.dataset.term;
                if (!window.confirm('「' + term + '」を削除しますか？')) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>