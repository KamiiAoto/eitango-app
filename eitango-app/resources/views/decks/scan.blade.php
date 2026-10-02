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
    <h2>カード候補</h2>
    <p id="candidateCount"></p>
    <button type="button" id="addCandidateBtn">候補を追加</button>
    <div id="candidateList"></div>
    <button type="button" id="validateBtn">入力を確認</button>
    <p id="validateMessage"></p>
    

    <script>
        const input = document.getElementById('imageInput');
        const ocrbtn = document.getElementById('ocrBtn');
        const img = document.getElementById('preview');
        const resultEl = document.getElementById('result');
        const statusEl = document.getElementById('status');

        input.addEventListener('click', function(event) {
            if (!confirmDiscardCandidates()) {
                event.preventDefault();     // ファイル選択ダイアログを開かない
            }
        });

        input.addEventListener('change', function() {
            ocrbtn.disabled = true;
            img.hidden = true;
            img.removeAttribute('src');
            resultEl.textContent = '';
            statusEl.textContent = '';
            
            clearCandidates();
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

            if (!confirmDiscardCandidates()) {
                return;
            }
            clearCandidates();
            
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

        const MAX_CANDIDATES = 50;
        let candidates = [];
        let nextId = 1;
        let errors = {};    // { 行のid: ['エラーメッセージ', ...] }

        const candidateList   = document.getElementById('candidateList');
        const candidateCount  = document.getElementById('candidateCount');
        const addCandidateBtn = document.getElementById('addCandidateBtn');
        const validateBtn     = document.getElementById('validateBtn');
        const validateMessage = document.getElementById('validateMessage');


        addCandidateBtn.addEventListener('click', function() {
            if (candidates.length >= MAX_CANDIDATES) {
                return;
            }
            candidates.push({ id: nextId++, term: '', meaning: '', selected: true });
            renderCandidates();
        });

        // 配列から候補一覧を作り直す
        function renderCandidates() {
            candidateList.replaceChildren();
            candidates.forEach(function(candidate) {
                const row = document.createElement('div');
                
                const check = document.createElement('input');
                check.type = 'checkbox';
                check.checked = candidate.selected;
                check.addEventListener('change', function() {
                    candidate.selected = check.checked;
                    updateCount();
                });

                const termInput = document.createElement('input');
                termInput.type = 'text';
                termInput.placeholder = '英語';
                termInput.value = candidate.term;
                termInput.addEventListener('input', function() {
                    candidate.term = termInput.value;
                });

                const meaningInput = document.createElement('input');
                meaningInput.type = 'text';
                meaningInput.placeholder = '日本語訳';
                meaningInput.value = candidate.meaning;
                meaningInput.addEventListener('input', function() {
                    candidate.meaning = meaningInput.value;
                });
                
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.textContent = '削除';
                deleteBtn.addEventListener('click', function() {
                    candidates = candidates.filter(function(c) {
                        return c.id !== candidate.id;
                    });
                    renderCandidates();
                });

                row.append(check, termInput, meaningInput, deleteBtn);
                if (errors[candidate.id]) {
                    errors[candidate.id].forEach(function(message) {
                        const p = document.createElement('p');
                        p.textContent = message;
                        row.append(p);
                    });
                }

                candidateList.append(row);
            });
            updateCount();
        }

        // 件数表示と追加ボタンの状況を更新
        function updateCount() {
            const selectedCount = candidates.filter(function(c) {
                return c.selected;
            }).length;
            candidateCount.textContent = '候補' + candidates.length + '件・選択' + selectedCount + '件';
            addCandidateBtn.disabled = candidates.length >= MAX_CANDIDATES;
        }
        
        function hasEditedCandidates() {
            return candidates.some(function(c) {
                return c.term.trim() !== '' || c.meaning.trim() !== '';
            });
        }

        function confirmDiscardCandidates() {
            if (!hasEditedCandidates()) {
                return true;
            }
            return window.confirm('編集中の候補を破棄します．よろしいですか？');
        }

        function clearCandidates() {
            candidates = [];
            errors = {};
            validateMessage.textContent = '';
            renderCandidates();
        }

        function validateCandidate(candidate) {
            const messages = [];
            const term = candidate.term.trim();
            const meaning = candidate.meaning.trim();

            if (term === '') {
                messages.push('英語を入力してください');
            } else if ([...term].length > 150) {
                messages.push('英語は150文字以内で入力してください');
            }

            if (meaning === '') {
                messages.push('日本語訳を入力してください');
            } else if ([...meaning].length > 1000) {
                messages.push('日本語訳は1000文字以内で入力してください');
            }
            
            return messages;
        }

        validateBtn.addEventListener('click', function() {
            errors = {};
            const selected = candidates.filter(function(c) {
                return c.selected;
            });

            if (selected.length === 0) {
                validateMessage.textContent = '保存する候補を選択してください';
                renderCandidates();
                return;
            }

            selected.forEach(function(candidate) {
                const messages = validateCandidate(candidate);
                if (messages.length > 0) {
                    errors[candidate.id] = messages;
                }
            });

            const errorCount = Object.keys(errors).length;
            if (errorCount === 0) {
                validateMessage.textContent = '選択した' + selected.length + '件で保存できます';
            } else {
                validateMessage.textContent = errorCount + '件の候補に問題があります';
            }
            renderCandidates();
        });

        renderCandidates();

    </script>
</body>
</html>