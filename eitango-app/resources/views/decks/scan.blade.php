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
        const ocrbtn = document.getElementById('ocrBtn');
        const img = document.getElementById('preview');
        const resultEl = document.getElementById('result');
        const statusEl = document.getElementById('status');


        input.addEventListener('change', function() {
            ocrbtn.disabled = true;
            img.hidden = true;
            img.removeAttribute('src');
            resultEl.textContent = '';
            statusEl.textContent = '';
            
        
            const file = input.files[0];
            if (!file) {
                return
            }
            
            input.disabled = true;
            statusEl.textContent = '画像を読み込み中．．．';
            
            const reader = new FileReader();
            
            reader.addEventListener('load', function() {
                img.src = reader.result;
                img.hidden = false;
                statusEl.textContent = '';
                input.disabled = false;
                ocrbtn.disabled = false;
            });
            reader.addEventListener('error', function() {
                statusEl.textContent = '画像の読み込みに失敗しました．別の画像を選んでください．';
                input.disabled = false;
            });

            reader.readAsDataURL(file);
        });


        ocrbtn.addEventListener('click', runOCR);

        async function runOCR() {
            
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