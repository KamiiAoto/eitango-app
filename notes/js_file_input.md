# ファイル入力とイベント処理（JS基礎）

## HTML：ファイル入力欄

```html
<input type="file" accept="image/*" id="ocr-imgs">
```

| 属性 | 役割 |
|---|---|
| `type="file"` | ファイル選択ボタンになる |
| `accept="image/*"` | 選べるファイルを画像に限定する |
| `id` | JSから要素を取得するための識別子 |

---

## JS：要素の取得とイベント検知

```js
const input = document.getElementById('ocr-imgs');

input.addEventListener('change', function() {
    // ファイルが選ばれたときの処理
});
```

- `getElementById` でHTMLの要素を取得する
- `addEventListener('change', ...)` でファイル選択時の処理を登録する
- `<script>` は `</body>` の直前に書く（HTMLが読み込まれてからJSを実行するため）

---

## JS：ファイル情報の取得

```js
const file = input.files[0];  // 選ばれた最初のファイル

file.name   // ファイル名（例: photo.jpg）
file.type   // MIMEタイプ（例: image/jpeg）
file.size   // バイト数（例: 204800）
```

---

## JS：ファイルが未選択のときの早期終了

```js
const file = input.files[0];

if (!file) {
    return;  // ここで処理を終了する
}
```

- `files[0]` が `undefined` のとき、そのまま `file.name` を呼ぶとエラーになる
- 処理の最初で確認して `return` することでエラーを防ぐ

---

## FileReader：画像をプレビュー表示する

```js
const reader = new FileReader();

reader.addEventListener('load', function() {
    // 読み込み完了後にここが実行される
    const img = document.getElementById('preview');
    img.src = reader.result;  // data:image/jpeg;base64,... という文字列
    img.hidden = false;       // 表示する
});

reader.readAsDataURL(file);  // 読み込み開始（完了を待たずに次の行へ進む）
```

**重要：非同期処理**
- `readAsDataURL` は「読み込んでおいて」と指示するだけで、完了を待たずに次の行へ進む
- 結果（`reader.result`）は `load` イベントの中でしか使えない
- イベントを登録してから読み込みを開始する順番を守る

---

## CSS：画像が横にはみ出さないようにする

```css
#preview {
    max-width: 100%;  /* 親要素の幅を超えない */
    height: auto;     /* 幅に合わせて高さを自動調整（縦横比を保つ） */
}
```

- `id` セレクタは `#名前`、`class` セレクタは `.名前`
- `<style>` タグは `<head>` の中に書く
