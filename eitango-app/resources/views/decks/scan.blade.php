<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>画像読み取り - {{ $deck->name }}</title>
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        <p>参考：元画像のOCR結果．誤認識を含みます</p>
        <pre id="result"></pre>
    </div>
    <h2>カード候補</h2>
    <p id="candidateCount"></p>
    <button type="button" id="addCandidateBtn">候補を追加</button>
    <div id="candidateList"></div>
    <button type="button" id="validateBtn">入力を確認</button>
    <p id="validateMessage"></p>
    <button type="button" id="saveBtn">選択した候補を保存</button>
    <p id="saveMessage"></p>
    

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
            img.hidden = true;
            img.removeAttribute('src');
            resultEl.textContent = '';
            statusEl.textContent = '';
            
            clearCandidates();
            const file = input.files[0];
            if (!file) {
                return
            }
            
            isReadingFile = true;
            updateControls();
            statusEl.textContent = '画像を読み込み中．．．';
            
            const reader = new FileReader();
            
            reader.addEventListener('load', function() {
                img.src = reader.result;
                img.hidden = false;
                statusEl.textContent = '';
                isReadingFile = false;
                updateControls();
            });
            reader.addEventListener('error', function() {
                statusEl.textContent = '画像の読み込みに失敗しました．別の画像を選んでください．';
                isReadingFile = false;
                updateControls();
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
            
            isRunningOCR = true;
            updateControls();
            statusEl.textContent = '読み取り中．．．';
            resultEl.textContent = '';

            let worker = null;
            try {
                worker = await Tesseract.createWorker('eng+jpn');
                
                const fullText    = await recognizeWith(worker, 'eng+jpn', img.src);
                const termText    = await recognizeWith(worker, 'eng', prepareArea(TERM_AREA, isBlackInk));
                const meaningText = await recognizeWith(worker, 'jpn', prepareArea(MEANING_AREA, isRedInk));

                // 全文は，候補を直す時の参考として表示する
                resultEl.textContent = fullText;

                const term    = cleanText(termText);
                const meaning = cleanMeaning(meaningText);
                
                if (term === '' && meaning === '') {
                    statusEl.textContent = '英語と訳を読み取れませんでした．「候補を追加」から手で入力してください．';
                } else {
                    candidates.push({ id: nextId++, term: term, meaning: meaning, selected: false });
                    renderCandidates();
                    statusEl.textContent = '読み取り完了，候補の内容を確認・修正し，チェックを入れて保存してください．';
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
                isRunningOCR = false;
                updateControls();
            }
        }

        // 見出し語が上、訳が下にある画像の読み取り範囲（画像の高さに対する割合）
        const TERM_AREA    = { top: 0, bottom: 0.40 };
        const MEANING_AREA = { top: 0.65, bottom: 1.00};

        // 割合で決めた範囲を，元画像のピクセル座標に変換する
        function toRectangle(area) {
            const top    = Math.round(img.naturalHeight * area.top);
            const bottom = Math.round(img.naturalHeight * area.bottom);
            return { left: 0,  top: top, width: img.naturalWidth, height: bottom-top };
        }

        function isBlackInk(r, g, b) {
            return 0.299*r + 0.587*g + 0.114*b < 100;
        }

        function isRedInk(r, g, b) {
            return r - g > 60 && r - b > 40;
        }

        // 元画像の一部を切り出し，文字の色だけを黒．それ以外を白にしたキャンバスを返す
        function prepareArea(area, isInk) {
            const rect = toRectangle(area);
            const padding = 40;

            const canvas = document.createElement('canvas');
            canvas.width  = rect.width + padding*2;
            canvas.height = rect.height + padding*2;
            const ctx = canvas.getContext('2d');

            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, rect.left, rect.top, rect.width, rect.height, padding, padding, rect.width, rect.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const d = imageData.data;
            for (let i=0; i<d.length; i+=4) {
                const v = isInk(d[i], d[i+1], d[i+2]) ? 0 : 255;
                d[i] = v;
                d[i+1] = v;
                d[i+2] = v;
            }
            ctx.putImageData(imageData, 0, 0);
            return canvas;
        }

        // 同じ worker の言語を切り替えて読み取る
        async function recognizeWith(worker, lang, image) {
            await worker.reinitialize(lang);
            await worker.setParameters({ tessedit_pageseg_mode: Tesseract.PSM.SPARSE_TEXT });
            const result = await worker.recognize(image);
            return result.data.text;
        }

        // 日本語の文字と文字の間に入った空白を取り除く
        function cleanMeaning(text) {
            return cleanText(text).replace(/(?<=[^\x00-\x7F]) (?=[^\x00-\x7F])/g, '');
        }

        // 改行や連続した空白を１つの空白にまとめる
        function cleanText(text) {
            return text.replace(/\s+/g, ' ').trim();
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
        
        const saveBtn     = document.getElementById('saveBtn');
        const saveMessage = document.getElementById('saveMessage');
        const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;
        const deckUrl     = '/decks/{{ $deck->id }}';
        const bulkUrl     = deckUrl + '/cards/bulk';
        
        let pendingRequest = null;  // 結果が確定していない要求 { key: 'UUID', cards: [送った行] }
        let isSending = false;
        let isReadingFile = false;
        let isRunningOCR = false;

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
            updateControls();
        }

        // 件数表示と追加ボタンの状況を更新
        function updateCount() {
            const selectedCount = candidates.filter(function(c) {
                return c.selected;
            }).length;
            candidateCount.textContent = '候補' + candidates.length + '件・選択' + selectedCount + '件';
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

        function validateSelected() {
            errors = {};
            const selected = candidates.filter(function(c) {
                return c.selected;
            });

            if (selected.length === 0) {
                validateMessage.textContent = '保存する候補を選択してください';
                renderCandidates();
                return null;
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
            return errorCount === 0 ? selected : null
        }

        validateBtn.addEventListener('click', function() {
            validateSelected();
        });

        function updateControls() {
            const busy = isReadingFile || isRunningOCR || isSending;
            const locked = busy || pendingRequest !== null;
            
            input.disabled = locked;
            ocrbtn.disabled = locked || img.hidden;
            validateBtn.disabled = locked;
            addCandidateBtn.disabled = locked || candidates.length >= MAX_CANDIDATES;
            candidateList.querySelectorAll('input, button').forEach(function(el) {
                el.disabled = locked;
            });
            
            saveBtn.disabled = busy;
            if (pendingRequest !== null && !isSending) {
                saveBtn.textContent = '保存結果を確認する（同じ内容で再送）';
            } else {
                saveBtn.textContent = '選択した候補を保存';
            }
        }

        // Laravelの入力エラーを，行のidに対応づける
        function showServerErrors(serverErrors, sentCards) {
            errors = {};
            const otherMessages = [];
            Object.keys(serverErrors).forEach(function(key) {
                const match = key.match(/^cards\.(\d+)\./);
                if (match) {
                    const id = sentCards[Number(match[1])].id;
                    errors[id] = (errors[id] || []).concat(serverErrors[key]);
                } else {
                    otherMessages.push(...serverErrors[key]);
                }
            });
            return otherMessages;
        }

        function showSaved(count) {
            saveMessage.replaceChildren();
            saveMessage.append(count + '件保存しました．');
            const link = document.createElement('a');
            link.href = deckUrl;
            link.textContent = 'デッキ詳細で確認する';
            saveMessage.append(link);
        }

        function showConflict() {
            saveMessage.replaceChildren();
            saveMessage.append('保存キーが競合しています．同じ内容がすでに保存されている可能性があります．');
            const link = document.createElement('a');
            link.href = deckUrl;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = 'デッキ詳細で保存済みの内容を確認する';
            saveMessage.append(link);
            saveMessage.append('(別タブで開きます)．確認後，まだ保存されていない候補だけを選んで保存してください．');
        }

        saveBtn.addEventListener('click', async function() {
            // 結果がわからない要求がなければ，新しい要求を作る
            if (pendingRequest === null) {
                const selected = validateSelected();
                if (!selected) {
                    return;
                }
                pendingRequest = {
                    key: crypto.randomUUID(),
                    cards: selected.map(function(c) {
                        return { id: c.id, term: c.term, meaning: c.meaning };
                    }),
                };
            }
            // 結果不明の要求があれば，それをそのまま再送する
            const sentCards = pendingRequest.cards;

            isSending = true;
            updateControls();
            saveMessage.textContent = '保存中．．．';


            try {
                const response = await fetch(bulkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ request_key: pendingRequest.key, cards: sentCards }),
                });
                if (response.status === 422) {
                    pendingRequest = null; // 保存されていないことが確定したので，編集できるようにする
                    const data = await response.json();
                    const otherMessages = showServerErrors(data.errors, sentCards);
                    saveMessage.textContent = '入力内容に問題があります．' + otherMessages.join(' ');
                    return;
                }

                if (response.status === 409) {
                    pendingRequest = null;  // 同じ再送では解決しないので，ロックを解除する
                    showConflict();
                    return;
                }

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();
                const savedIds = sentCards.map(function(c) {
                    return c.id;
                });
                candidates = candidates.filter(function(c) {
                    return !savedIds.includes(c.id);
                });
                pendingRequest = null;  
                showSaved(data.saved_count);
            } catch (error) {
                console.error(error);
                // pendingRequset は残す → 候補はロックされたまま
                saveMessage.textContent = '保存結果を確認できませんでした．「保存結果を確認する」を押して，同じ内容をもう一度送ってください．';
            } finally {
                isSending = false;
                renderCandidates();     // 中で updateControls() も呼ばれる
            }
        });

        renderCandidates();

    </script>
</body>
</html>