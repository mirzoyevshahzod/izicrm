<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xodimlar Davomati</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://t4.ftcdn.net/jpg/08/45/27/83/360_F_845278348_WlDw0x7efqnIHj1lsFmGdjsUSsd373Jm.jpg')
                no-repeat center center fixed;
            background-size: cover;
            padding: 20px;
        }


        /* 🌟 CONTAINER qo'shildi */
        .container {
            max-width: 900px;
            margin: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        thead {
            background: #667eea;
            color: white;
            font-size: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background: #f2f3ff;
        }

        a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        @media(max-width: 600px) {
            table {
                font-size: 13px;
            }

            th, td {
                padding: 8px;
            }
        }

        .header {
            margin-bottom: 20px;
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

        .delete-driver {
            background-color: #ff4d4f;  /* Qizil fon */
            color: white;               /* Oq matn */
            border: none;               /* Chegara yo‘q */
            padding: 6px 12px;          /* Ichki bo‘shliq */
            border-radius: 5px;         /* Yumaloq burchak */
            cursor: pointer;            /* Kursor ko‘rsatkich */
            font-weight: bold;
            transition: background 0.3s;
        }

        .delete-driver:hover {
            background-color: #ff7875;  /* Hoverda ochroq qizil */
        }

        .file-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            cursor: pointer;
            background-color: #f9f9f9;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .file-select:hover {
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .file-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
        }
        .pagination-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 14px;
            margin: 0 5px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.25s, transform 0.15s, box-shadow 0.15s;
        }

        .pagination-btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .pagination-btn:disabled {
            background: #c7d2fe;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .pagination-info {
            font-weight: bold;
            margin: 0 10px;
            color: #333;
        }

        .search-time {
            margin-top: 10px;
            display: flex;
            gap: 8px; /* inputlar orasidagi masofa */
        }

        .search-time input {
            flex: 1; /* hammasi teng bo‘lib yonma-yon chiqadi */
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }
        .actions-time {
            margin-top: 10px;
            display: flex;
            gap: 8px; /* inputlar orasidagi masofa */
        }

        .actions-time input {
            flex: 1; /* hammasi teng bo‘lib yonma-yon chiqadi */
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        
        .btn-add {
            background: #10b981;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .btn-add:hover {
            background: #059669;
        }

        .btn-download {
            background: #5a67d8;;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .btn-download:hover {
            background: #0bb40b;
        }

        



    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Xodimlar Davomati</h1>

         <div class="search-box">
            <input type="text" id="fioSearch" placeholder="Ism Familya Orqali Qidirish...">
            </div>
            
            <div class="search-time">
                <input type="number" id="daySearch" placeholder="Kun" min="1" max="31">
                <input type="number" id="monthSearch" placeholder="Oy" min="1" max="12">
                <input type="number" id="yearSearch" placeholder="Yil">
            </div>
            <div class="actions-time">
                <button class="btn-download">Faylni Yuklash</button>
                <button class="btn-add" id="profile">Profile</button>
            </div>
    </div>
    <script>
      document.getElementById('profile').addEventListener('click', function() {
                    // Bu yerga sahifa URLini yozing
                    window.location.href = '/admin-profile';
                });
    </script>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Chat_id</th>
                <th>Ism Familya</th>
                <th>Kech qolish sababi</th>
                <th>Kechikkan vaqti</th>
                <th>Qachon</th>
                <!-- <th>Amallar</th>  -->
            </tr>
        </thead>


        <tbody class="drivers-table">
            <tr><td colspan="5" style="text-align:center; padding:20px;">Yuklanmoqda...</td></tr>
        </tbody>
    </table>
        <div id="pagination" style="margin-top:15px; text-align:center;"></div>


</div> <!-- container tugadi -->

<script>
let currentPage = 1;

async function loadDrivers(page = 1) {
    currentPage = page;

    const fio   = document.getElementById('fioSearch')?.value || '';
    const day   = document.getElementById('daySearch')?.value || '';
    const month = document.getElementById('monthSearch')?.value || '';
    const year  = document.getElementById('yearSearch')?.value || '';

    let query = new URLSearchParams({
        page: page
    });

    if (fio)   query.append('fio', fio);
    if (day)   query.append('day', day);
    if (month) query.append('month', month);
    if (year)  query.append('year', year);

    const tbody = document.querySelector('.drivers-table');
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Yuklanmoqda...</td></tr>`;

    try {
        const response = await fetch(`https://izicrm.uz/api/attendances/search?${query.toString()}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        const data = result.data;

        tbody.innerHTML = '';

        if (data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Maʼlumot topilmadi</td></tr>`;
            return;
        }

        data.data.forEach((driver, index) => {
            const dayStr   = driver.day < 10 ? '0' + driver.day : driver.day;
            const monthStr = driver.month < 10 ? '0' + driver.month : driver.month;

              // HAR BIR DRIVER OBYEKTIDA late_minutes bo'lishi kerak
                const totalMinutes = driver.late_minutes || 0; // agar undefined bo'lsa 0 qilamiz
                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;

                  // Soat 0 bo'lsa, uni chiqarish shart emas
            let timeStr = '';
            if (hours > 0) timeStr += `${hours} soat `;
            if (minutes > 0 || hours === 0) timeStr += `${minutes} daqiqa`;
            tbody.innerHTML += `
                <tr>
                    <td>${(data.per_page * (data.current_page - 1)) + index + 1}</td>
                    <td>${driver.chat_id}</td>
                    <td>${driver.fio}</td>
                    <td>${driver.reason || '—'}</td>
                     <td>${timeStr}</td>
                    <td>${dayStr}.${monthStr}.${driver.year}</td>
                </tr>
            `;
        });

        renderPagination(data.current_page, data.last_page);

    } catch (err) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="color:red;text-align:center;">
                    Server bilan bog‘lanib bo‘lmadi
                </td>
            </tr>`;
        console.error(err);
    }
}

['fioSearch', 'daySearch', 'monthSearch', 'yearSearch'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', () => loadDrivers(1));
    }
});

document.addEventListener('DOMContentLoaded', () => {
    loadDrivers(1);
});

function renderPagination(current, last) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    if (last <= 1) return;

    if (current > 1) {
        pagination.innerHTML += `
            <button class="pagination-btn" onclick="loadDrivers(${current - 1})">
                ⬅ Oldingi
            </button>

        `;
    }

    pagination.innerHTML += `
        <span style="margin:0 10px;">${current} / ${last}</span>
    `;

    if (current < last) {
        pagination.innerHTML += `
            <button class="pagination-btn" onclick="loadDrivers(${current + 1})">
                Keyingi ➡
            </button>
        `;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDrivers(1);
});

document.querySelector('.btn-download').addEventListener('click', function () {
    const fio   = document.getElementById('fioSearch')?.value || '';
    const day   = document.getElementById('daySearch')?.value || '';
    const month = document.getElementById('monthSearch')?.value || '';
    const year  = document.getElementById('yearSearch')?.value || '';

    let query = new URLSearchParams();

    if (fio)   query.append('fio', fio);
    if (day)   query.append('day', day);
    if (month) query.append('month', month);
    if (year)  query.append('year', year);

    const token = localStorage.getItem('auth_token');

    // 🔥 download qilish
    fetch(`https://izicrm.uz/api/attendances/export?${query.toString()}`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'attendances.xlsx';
        document.body.appendChild(a);
        a.click();
        a.remove();
    });
});
</script>

</body>
</html>
