<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>画像読み取り - {{ $deck->name }}</title>
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'></script>
</head>
<body>
    <h1>{{ $deck->name }} - 画像読み取り</h1>
    <a href="/decks/{{ $deck->id }}">← デッキ詳細に戻る</a>
    <div>
        <input type="file" id="imageInput" accept="image/*">
        <img id="preview" hidden style="max-width:400px;">
    </div>
    <div>
        <button id="ocrBtn" disabled>読み取り開始</button>
    </div>
    <div>
        <p id="status"></p>
        <pre id="result"></pre>
    </div>

    <script>
        const input = document.getElementById('imageInput');
        input.addEventListener('change', function() {
            const files = input.files;
            const file = files[0];
            if (!file) {
                return
            }
            
            const reader = new FileReader();
            
            reader.addEventListener('load', function() {
                const img = document.getElementById('preview');
                img.src = reader.result;
                img.hidden = false;
                ocrbtn.disabled = false;
            });
            
            reader.readAsDataURL(file);
        });


        const ocrbtn = document.getElementById('ocrBtn');
        ocrbtn.addEventListener('click', runOCR);

        async function runOCR() {
            const img = document.getElementById('preview');
            const resultEl = document.getElementById('result');
            const statusEl = document.getElementById('status');
            
            if(!img.src || img.hidden) {
                return;
            }
            
            input.disabled  = true;
            ocrbtn.disabled = true;
            statusEl.textContent = '読み取り中．．．';
            resultEl.textContent = '';

            let worker = null;
            try {
                worker = await Tesseract.createWorker('eng+jpn');
                await worker.setParameters({
                    tessedit_pageseg_mode: Tesseract.PSM.SPARSE_TEXT
                });
                const result = await worker.recognize(img.src);
                const text   = result.data.text.trim();
                
                if (text === '') {
                    statusEl.textContent = '文字が検出されませんでした．別の画像を試してください．';
                } else {
                    statusEl.textContent = '読み取り完了';
                    resultEl.textContent = result.data.text;
                }
            } catch (error) {
                console.error(error);
                statusEl.textContent = '読み取りに失敗しました';
            } finally {
                if (worker) {
                    try {
                        await worker.terminate();                
                    } catch (e) {
                        console.error('ワーカーの終了に失敗:', e);
                    }
                }
                input.disabled  = false;
                ocrbtn.disabled = false;
            }
        }

    </script>
</body>
</html>