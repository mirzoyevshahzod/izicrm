<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Imzo qo'yish</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #1c1c1e);
        }
        .wrap {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 16px;
            gap: 12px;
        }
        h1 { font-size: 18px; margin: 0; }
        .hint { font-size: 14px; margin: 0; color: var(--tg-theme-hint-color, #8e8e93); }
        .pad {
            position: relative;
            flex: 1;
            min-height: 220px;
            border-radius: 12px;
            border: 2px dashed var(--tg-theme-hint-color, #c7c7cc);
            background: #ffffff;
            overflow: hidden;
        }
        .pad canvas {
            width: 100%;
            height: 100%;
            display: block;
            touch-action: none;
        }
        .pad .line {
            position: absolute;
            left: 24px;
            right: 24px;
            bottom: 28%;
            border-bottom: 1px solid #d1d1d6;
            pointer-events: none;
        }
        .actions { display: flex; gap: 12px; }
        button {
            flex: 1;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        button:disabled { opacity: .5; }
        .btn-clear {
            background: var(--tg-theme-secondary-bg-color, #f2f2f7);
            color: var(--tg-theme-text-color, #1c1c1e);
        }
        .btn-send {
            background: var(--tg-theme-button-color, #2481cc);
            color: var(--tg-theme-button-text-color, #ffffff);
        }
        .msg { min-height: 20px; font-size: 14px; text-align: center; margin: 0; }
        .msg.error { color: #e53935; }
        .msg.ok { color: #2e7d32; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>✍️ Imzo qo'yish</h1>
    <p class="hint">Quyidagi oq maydonga barmog'ingiz bilan imzo chizing.</p>

    <div class="pad">
        <div class="line"></div>
        <canvas id="canvas"></canvas>
    </div>

    <p class="msg" id="msg"></p>

    <div class="actions">
        <button type="button" class="btn-clear" id="clear">Tozalash</button>
        <button type="button" class="btn-send" id="send">Yuborish</button>
    </div>
</div>

<script>
    const tg = window.Telegram.WebApp;
    tg.ready();
    tg.expand();
    if (tg.disableVerticalSwipes) tg.disableVerticalSwipes();

    const eventId = new URLSearchParams(location.search).get('event');
    const canvas = document.getElementById('canvas');
    const msg = document.getElementById('msg');
    const sendBtn = document.getElementById('send');
    const clearBtn = document.getElementById('clear');

    const pad = new SignaturePad(canvas, {
        penColor: '#0b1f66',
        minWidth: 1,
        maxWidth: 2.8,
    });

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const data = pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        pad.fromData(data);
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    function showMessage(text, type) {
        msg.textContent = text;
        msg.className = 'msg ' + (type || '');
    }

    // Imzo atrofidagi bo'sh joyni kesib, faqat imzoning o'zini qaytaradi
    function trimmedDataUrl() {
        const ctx = canvas.getContext('2d');
        const { width, height } = canvas;
        const pixels = ctx.getImageData(0, 0, width, height).data;
        let top = height, left = width, right = 0, bottom = 0;

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                if (pixels[(y * width + x) * 4 + 3] > 0) {
                    if (x < left) left = x;
                    if (x > right) right = x;
                    if (y < top) top = y;
                    if (y > bottom) bottom = y;
                }
            }
        }

        const margin = 10;
        left = Math.max(left - margin, 0);
        top = Math.max(top - margin, 0);
        right = Math.min(right + margin, width - 1);
        bottom = Math.min(bottom + margin, height - 1);

        const out = document.createElement('canvas');
        out.width = right - left + 1;
        out.height = bottom - top + 1;
        out.getContext('2d').drawImage(canvas, left, top, out.width, out.height, 0, 0, out.width, out.height);

        return out.toDataURL('image/png');
    }

    if (!tg.initData || !eventId) {
        showMessage("Bu sahifa faqat Telegram bot ichidan ochiladi.", 'error');
        sendBtn.disabled = true;
    }

    clearBtn.addEventListener('click', () => {
        pad.clear();
        showMessage('');
    });

    sendBtn.addEventListener('click', async () => {
        if (pad.isEmpty()) {
            showMessage("Iltimos, avval imzo chizing.", 'error');
            return;
        }

        sendBtn.disabled = true;
        clearBtn.disabled = true;
        showMessage("Yuborilmoqda...");

        try {
            const res = await fetch('/api/telegram/egs-attendance-signature', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    event_id: Number(eventId),
                    init_data: tg.initData,
                    signature: trimmedDataUrl(),
                }),
            });
            const json = await res.json().catch(() => ({}));

            if (!res.ok || !json.ok) {
                throw new Error(json.message || "Xatolik yuz berdi");
            }

            showMessage("✅ Imzo qabul qilindi! Tushuntirish xati botga yuborildi.", 'ok');
            setTimeout(() => tg.close(), 1200);
        } catch (e) {
            showMessage(e.message, 'error');
            sendBtn.disabled = false;
            clearBtn.disabled = false;
        }
    });
</script>
</body>
</html>
