# Tesseract.js によるOCR処理

## Tesseract.js の読み込み（CDN）

```html
<head>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
</head>
```

- 外部ライブラリは `<head>` 内に読み込む
- 自分で書くJSより先に読み込まれる必要があるため

---

## 基本の流れ

```js
const worker = await Tesseract.createWorker('eng');  // 英語ワーカーを作成
const result = await worker.recognize(img.src);       // 画像を認識
console.log(result.data.text);                        // 認識されたテキスト
await worker.terminate();                             // ワーカーを終了
```

- `createWorker` で言語を指定する（`'eng'` = 英語、`'jpn'` = 日本語）
- `recognize` には `<img>` の `src`（`data:image/...` の文字列）を渡せる
- 使い終わったら必ず `terminate()` で終了する（終了後は再利用不可）

---

## async / await

```js
async function runOCR() {
    const result = await worker.recognize(img.src);  // 完了を待つ
    console.log(result.data.text);                   // 完了後に実行される
}
```

- `async` をつけた関数の中で `await` が使える
- `await` は非同期処理の完了を待ってから次の行へ進む
- `FileReader` の `load` イベントと同じ考え方を、より読みやすく書ける

**`await` で待つのはどれか：**
- `Tesseract.createWorker()`：ワーカーの初期化完了を待つ
- `worker.recognize()`：画像認識の完了を待つ
- `worker.terminate()`：ワーカーの終了を待つ

---

## try / catch / finally と堅牢なエラー処理

```js
let worker = null;  // try の外で宣言しておく
try {
    worker = await Tesseract.createWorker('eng');  // 初期化も try の中に入れる
    const result = await worker.recognize(img.src);
    resultEl.textContent = result.data.text;
} catch (error) {
    console.error(error);
    resultEl.textContent = '読み取りに失敗しました';
} finally {
    if (worker) {             // worker が作成できていた場合のみ terminate
        try {
            await worker.terminate();
        } catch (e) {
            console.error('ワーカーの終了に失敗:', e);
        }
    }
    input.disabled  = false;  // terminate の成否に関わらず必ず実行される
    ocrbtn.disabled = false;
}
```

- `try`：失敗するかもしれない処理（初期化も含める）
- `catch`：失敗したときの処理（利用者に伝えるメッセージも表示する）
- `finally`：成功・失敗に関わらず必ず実行される（後片付けに使う）

**重要：`finally` の中の処理が失敗すると残りの行まで実行されない**
- `terminate()` が失敗すると、その後の `disabled = false` に到達しない
- `finally` 内でも失敗しうる処理は `try/catch` で個別に守る
- 画面操作の復帰（`disabled = false`）は `terminate` の外側に置く

**重要：`let` と `const` のスコープ**
```js
let worker = null;       // 外側で宣言
try {
    const worker = ...;  // バグ：try の中だけで有効な別変数になる
    worker = ...;        // 正しい：外側の worker に代入する
}
```

---

## 処理中の操作制御（disabled）

```js
input.disabled  = true;   // 処理中は操作不可にする
ocrbtn.disabled = true;

// ... 処理 ...

input.disabled  = false;  // 処理完了後に操作可能に戻す
ocrbtn.disabled = false;
```

- `disabled = true` でボタンや入力欄をグレーアウトして操作不可にする
- `finally` の中で解除すると、成功・失敗どちらでも確実に戻せる

---

## イベントリスナーへの関数の渡し方

```js
// 正しい：関数そのものを渡す（クリック時に実行される）
ocrbtn.addEventListener('click', runOCR);

// バグ：関数を今すぐ実行した結果を渡してしまう
ocrbtn.addEventListener('click', runOCR());
```

---

## 言語設定の比較実験（eng vs eng+jpn）

対象：英単語・品詞・日本語訳が混在する単語帳ページの切り出し画像

| 観点 | eng のみ | eng+jpn |
|---|---|---|
| 英単語の認識 | contribution, overall, prior, demonstrate, update を認識 | ほぼ同等 |
| 日本語部分 | 完全に文字化け（`BoKOAS TH`、`SHELTH` など） | 部分的に読める（`全体 と し て は`、`は っ きり 示す` など） |
| ノイズ | 日本語→ASCII変換ノイズが多い | 記号ノイズが混入（`£`、`]`、`回` など） |

**結論**
- 英単語の精度は両設定で差なし
- 日本語を含む画像には `eng+jpn` の方がマシ
- どちらも単語と訳の対応づけは自動ではできない（テキストが混在して出力される）

---

## PSM（ページ分割モード）の比較実験

**PSM = Page Segmentation Mode**：Tesseract が画像内の文字領域をどう探すかの設定。

```js
await worker.setParameters({
    tessedit_pageseg_mode: Tesseract.PSM.SPARSE_TEXT  // 採用
});
```

| モード | 意味 | 向いている画像 |
|---|---|---|
| `SINGLE_BLOCK` | 画像全体を1つの連続した文章として扱う | 小説・記事など普通の段落 |
| `SPARSE_TEXT` | 散在する文字を探す。読み順は保証しない | 単語帳・フォーム・ラベルなど |

**実験結果**（画像：test2-re.JPG / 言語：eng+jpn）

| 観点 | SINGLE_BLOCK | SPARSE_TEXT |
|---|---|---|
| `contribution` | あり | あり |
| 発音記号 | あり（部分的） | あり（部分的） |
| `貢献`・`寄付` | なし | あり（`貢献 ② 寄 付`） |
| ノイズ | `\|` のみ | `図 〇`（アイコンの誤認識） |

**結論**：単語帳のように文字が散在するレイアウトには `SPARSE_TEXT` が有効。残ったノイズは確認画面で手修正する前提で扱う。
