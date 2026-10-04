<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>単語帳一覧</title>
</head>
<body>
    <h1>単語帳一覧</h1>

    <form action="/decks" method="post">
        @csrf
        <input type="text" name="name" placeholder="単語帳の名前" value="{{ old('name') }}" class="@error('name') is-invalid @enderror">
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror
        <button type="submit" class="btn-primary">作成</button>
    </form>
    <ul class="item-list">
        @forelse ($decks as $deck)
        <li class="item">
            <div>
                <a href="/decks/{{ $deck->id }}" class="item-title">{{ $deck->name }}</a>
                <div class="item-meta">{{ $deck->cards_count }}枚</div>
            </div>
            <div class="item-actions"> 
                <a href="/decks/{{ $deck->id }}/edit">編集</a>
                <form action="/decks/{{ $deck->id }}" method="post" class="delete-form" data-name="{{ $deck->name }}" data-count="{{ $deck->cards_count }}">                    
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">削除</button>
                </form>
            </div>
        </li>
        @empty
            <li class="empty">単語帳はまだありません</li>
        @endforelse
    </ul>

    <script>
    document.querySelectorAll('.delete-form').forEach(function(form){
        form.addEventListener('submit', function(event) {
            const name = this.dataset.name;
            const count = parseInt(this.dataset.count, 10);
            let message = '「' + name + '」';
            if (count > 0) {
                message += 'と、その中のカード' + count + '件';
            }
            message += 'を削除しますか？';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
    </script>

</body>
</html>