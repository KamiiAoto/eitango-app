<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>{{ $deck->name }}</title>
</head>
<body>
    <h1>{{ $deck->name }}</h1>
    <a href="/decks">← 一覧に戻る</a>
    <a href="/decks/{{ $deck->id }}/scan">画像から読み取る</a>
    <a href="/decks/{{ $deck->id }}/export">Anki用ファイルをダウンロード</a>
    <form action="/decks/{{ $deck->id }}/cards" method="post">
        @csrf
        <div>
            <input type="text" name="term" placeholder="英単語・熟語" value="{{ old('term') }}" class="@error('term') is-invalid @enderror">
            @error('term')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <input type="text" name="meaning" placeholder="日本語訳" value="{{ old('meaning') }}" class="@error('meaning') is-invalid @enderror">
            @error('meaning')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="btn-primary">追加</button>
    </form>
    <form action="/decks/{{ $deck->id }}" method="get">
        <input type="text" name="q" value="{{ $q }}" placeholder="英語または日本語で検索">
        <button type="submit">検索</button>
        @if ($q !== '')
            <a href="/decks/{{ $deck->id }}">検索解除</a>
        @endif
    </form>
    <ul class="item-list">
        @forelse ($cards as $card)
            <li class="item">
                <div>
                    <div class="item-title">{{ $card->term }}</div>
                    <div class="item-meta">{{ $card->meaning }}</div>
                </div>
                <div class="item-actions">
                    <a href="/cards/{{ $card->id }}/edit">編集</a>
                    <form action="/cards/{{ $card->id }}" method="post" class="card-delete-form" data-term="{{ $card->term }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">削除</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="empty">
                @if ($q !=='')
                    「{{ $q }}」に一致するカードはありません
                @else
                    カードはまだありません
                @endif
            </li>
        @endforelse
    </ul>
    {{ $cards->links('pagination::default') }}

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