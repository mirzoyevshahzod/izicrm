<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qarzdorligi Haqida Ma'lumotlar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
          .search-box {
            margin-top: 10px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .debt-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .debt-table {
            width: 100%;
            border-collapse: collapse;
        }

        .debt-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
        }

        .debt-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #667eea;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .debt-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s ease;
        }

        .debt-table tbody tr:hover {
            background: #f8f9fa;
        }

        .debt-table td {
            padding: 16px;
            color: #333;
        }

        .company-name {
            color: #667eea;
            font-weight: 600;
        }

        .employee-name {
            font-weight: 600;
        }

        .debt-amount {
            color: #d32f2f;
            font-weight: 600;
            font-size: 16px;
        }

        .reason-text {
            color: #555;
            font-size: 13px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .media-section {
            display: flex;
            gap: 8px;
        }

        .media-button {
            padding: 8px 12px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
        }

        .media-button:hover {
            background: #667eea;
            color: white;
        }

        .media-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            color: white;
            font-size: 18px;
            padding: 40px;
        }

        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .modal-audio {
            width: 100%;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .debt-table th,
            .debt-table td {
                padding: 12px 8px;
                font-size: 12px;
            }

            .debt-table th {
                font-size: 11px;
            }

            .media-button {
                padding: 6px 8px;
                font-size: 11px;
            }

            .reason-text {
                max-width: 100px;
            }

            .header h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .debt-table {
                font-size: 11px;
            }

            .debt-table th,
            .debt-table td {
                padding: 10px 6px;
            }

            .media-button {
                padding: 5px 6px;
                font-size: 10px;
            }

            .reason-text {
                max-width: 80px;
            }
            
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Qarzdorligi Haqida Ma'lumotlar</h1>
            <p>Kompaniyalarnin qayz bo'yicha detallı ma'lumotlar</p>


            <div class="search-box">
            <input type="text" id="fioSearch" placeholder="Ism Familya Orqali Qidirish...">
            </div>

            <div class="search-box">
                <input type="number" id="timeSearch" placeholder="Jarayonda Keyinchalik qo'shiladi" min="1" max="31">
            </div>
        </div>

        <div id="error-container"></div>
        <div id="loading" class="loading">
            <div class="loading-spinner"></div>
            <p>Ma'lumotlar yuklanmoqda...</p>
        </div>

        <div id="debt-list" class="debt-list">
            <table class="debt-table">
                <thead>
                    <tr>
                        <th>Kompaniya Nomi</th>
                        <th>Xodim Ismi</th>
                        <th>Qarz Miqdori</th>
                        <th>Sababi</th>
                        <th>Fayl</th>
                        <th>Vqati</th>
                    </tr>
                </thead>
                <tbody id="debt-body"></tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Image -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeImageModal()">&times;</button>
            <img id="modalImage" class="modal-image" src="" alt="Sababi fotosi">
        </div>
    </div>

    <!-- Modal for Audio -->
    <div id="audioModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeAudioModal()">&times;</button>
            <h3 style="margin-bottom: 20px;">Sababi Ovozi</h3>
            <audio id="modalAudio" class="modal-audio" controls>
                <source src="" type="audio/mpeg">
                Sizning brauzeringiz audio formatini qo'llamaydigan bo'lsa.
            </audio>
        </div>
    </div>

<script>

let searchTimeout = null;

async function loadDebtData() {
    const fio  = document.getElementById('fioSearch').value.trim();
    const days = document.getElementById('timeSearch').value;

    const loadingDiv = document.getElementById('loading');
    loadingDiv.style.display = 'block';

    let params = new URLSearchParams();

    if (fio.length > 0) {
        params.append('employee_name', fio);
    }

    if (days) {
        const to   = new Date();
        const from = new Date();
        from.setDate(to.getDate() - Number(days));

        params.append('from', from.toISOString().split('T')[0]);
        params.append('to', to.toISOString().split('T')[0]);
    }

    try {
        const response = await fetch(`/api/debts/search?${params.toString()}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Server xatoligi');

        const result = await response.json();
        console.log("API RESULT:", result);

        // moslashuvchan olish
        const debts = result?.data?.data || result?.data || result || [];

        displayDebtList(debts);

    } catch (error) {
        showError("Ma'lumotlarni yuklashda xatolik: " + error.message);
    }
}

/* ===== SEARCH EVENTS ===== */

// FIO input → debounce (serverga bosim bo‘lmasin)
document.getElementById('fioSearch').addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadDebtData();
    }, 500); // 0.5 sekund kutadi
});

// Kun bo‘yicha filter
document.getElementById('timeSearch').addEventListener('change', () => {
    loadDebtData();
});

// Sahifa ochilganda
document.addEventListener('DOMContentLoaded', loadDebtData);


/* ===== TABLE RENDER ===== */

function displayDebtList(debts) {
    const loadingDiv = document.getElementById('loading');
    const debtBodyDiv = document.getElementById('debt-body');

    loadingDiv.style.display = 'none';
    debtBodyDiv.innerHTML = '';

    if (!Array.isArray(debts) || debts.length === 0) {
        debtBodyDiv.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center;padding:20px;">
                    Ma'lumot topilmadi
                </td>
            </tr>`;
        return;
    }

    debts.forEach(debt => {

        const reasonText =
            debt?.reasons?.texts?.[0]?.text ?? '—';

        const photoUrl =
            debt?.reasons?.photos?.[0]?.file
                ? `/storage/${debt.reasons.photos[0].file}`
                : null;

        const audioUrl =
            debt?.reasons?.voices?.[0]?.file
                ? `/storage/${debt.reasons.voices[0].file}`
                : null;

        const date = debt?.updated_at
            ? new Date(debt.updated_at).toLocaleString('uz-UZ')
            : '—';

        debtBodyDiv.innerHTML += `
            <tr>
                <td class="company-name">${debt.company_name || '—'}</td>
                <td class="employee-name">${debt.employee_name || '—'}</td>
                <td class="debt-amount">
                    ${Number(debt.total_amount || 0).toLocaleString()} so'm
                </td>
                <td class="reason-text" title="${reasonText}">
                    ${reasonText}
                </td>
                <td>
                    <div class="media-section">
                        <button class="media-button"
                            ${!photoUrl ? 'disabled' : ''}
                            onclick="openImageModal('${photoUrl || ''}')">
                            Foto
                        </button>
                        <button class="media-button"
                            ${!audioUrl ? 'disabled' : ''}
                            onclick="openAudioModal('${audioUrl || ''}')">
                            Ovoz
                        </button>
                    </div>
                </td>
                <td>${date}</td>
            </tr>
        `;
    });
}


/* ===== MODALS ===== */

function openImageModal(imageUrl) {
    if(!imageUrl) return;
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('imageModal').classList.add('active');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.remove('active');
}

function openAudioModal(audioUrl) {
    if(!audioUrl) return;
    const audio = document.getElementById('modalAudio');
    audio.src = audioUrl;
    audio.load();
    document.getElementById('audioModal').classList.add('active');
}

function closeAudioModal() {
    const audio = document.getElementById('modalAudio');
    audio.pause();
    audio.src = '';
    document.getElementById('audioModal').classList.remove('active');
}


/* ===== ERROR ===== */

function showError(message) {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('error-container').innerHTML =
        `<div class="error">${message}</div>`;
}

</script>

</body>
</html>
