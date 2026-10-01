<form action="/decks/{{ $deck->id }}" method="post">
    @csrf
    @method('PATCH')
    <input type="text" name="name" value="{{ old('name', $deck->name) }}">
    @error('name')
    <p>{{ $message }}</p>
    @enderror
    <button type="submit">更新</button>
</form>
<a href="/decks">キャンセル</a>