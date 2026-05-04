<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driverlar Ro'yxati</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://images.ctfassets.net/tmcb0v2sc9iu/1lPIWBsODE6XG0TrHo1eH7/e0b13b28ec2d2c93de9db8735c11f76b/9.10.18_truck-appreciation-week.jpg')
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
        .temp-dashboard-link {
    display: inline-block;
    margin-bottom: 15px;
    padding: 10px 20px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    color: #fff;
    font-weight: bold;
    text-decoration: none;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    font-size: 16px;
}

.temp-dashboard-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    background: linear-gradient(90deg, #764ba2, #667eea);
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

    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Driver Ro'yxati</h1>

        <div class="search-box">
            <input type="text" id="driverSearch" placeholder="Telfon Raqami Orqali Qidirish..." onkeyup="searchDriver()">
        </div>
    </div>

{{-- <a href="{{ route('admin.dashboard') }}" class="temp-dashboard-link">IdentifyBot</a> --}}
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Driver ID</th>
                <th>Telefon</th>
                <th>CMR Fayl</th>
                <th>Manzil</th>
                <!-- <th>Amallar</th>  -->
            </tr>
        </thead>

        <tbody class="drivers-table">
            <tr><td colspan="5" style="text-align:center; padding:20px;">Yuklanmoqda...</td></tr>
        </tbody>
    </table>

</div> <!-- container tugadi -->


<script>
async function loadDrivers() {
    const tbody = document.querySelector('.drivers-table');
    tbody.innerHTML = '';

    try {
        const response = await fetch('https://izicrm.uz/api/all-drivers', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });

        if (!response.ok) {
            throw new Error('Server xatosi: ' + response.status);
        }

        const data = await response.json();

        data.data.forEach((driver, index) => {

            // Barcha fayllarni linkga aylantirish
            let filesLinks = '—';
           if (driver.files && driver.files.length > 0) {
            filesLinks = `<select class="file-select" onchange="if(this.value) window.location.href=this.value;">
                <option value="">Fayllarni tanlang</option>
                ${driver.files.map(f => `<option value="https://izicrm.uz/storage/${f.file_path}">Fayl#${f.id}</option>`).join('')}
            </select>`;
}

            let actions = `
                <button class="delete-driver" onclick="deleteDriver(${driver.id})">Driverni o'chirish</button>
                <button class="delete-driver" onclick="deleteFile(${driver.id})">Faylni o'chirish</button>
            `;

            let tr = `
                <tr>
                    <td>${index + 1}</td>
                    <td>Driver #${driver.id}</td>
                    <td><a href="tel:${driver.phone}">${driver.phone}</a></td>
                    <td>${filesLinks}</td>
                    <td>${driver.destination_country || '—'}</td>
                    
                </tr>
            `;
            // <td>${ actions || '—'}</td>

            tbody.innerHTML += tr;
        });

    } catch (err) {
        tbody.innerHTML = `<tr>
                <td colspan="5" style="color:red;text-align:center;">
                    Ma’lumot yuklashda xatolik yuz berdi.
                </td>
            </tr>`;
    }
}

document.addEventListener('DOMContentLoaded', loadDrivers);



    async function searchDriver() {
    const tbody = document.querySelector('.drivers-table');
    const phone = document.getElementById('driverSearch').value.trim();

    // Agar input bo‘sh bo‘lsa — barcha driverlarni qayta yuklaymiz
    if (phone === "") {
        loadDrivers();
        return;
    }

    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:20px;">Qidirilmoqda...</td></tr>`;

    try {
        const response = await fetch(`https://izicrm.uz/api/drivers/search?phone=${phone}`, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + localStorage.getItem("auth_token")
            }
        });

        const data = await response.json();

        tbody.innerHTML = "";

        if (data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:20px;">Hech narsa topilmadi</td></tr>`;
            return;
        }

        data.data.forEach((driver, index) => {
            const fileUrl = driver.cmr_file 
                ? `https://izicrm.uz/storage/${driver.cmr_file}`
                : null;

            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>Driver #${driver.id}</td>
                    <td><a href="tel:${driver.phone}">${driver.phone}</a></td>
                    <td>${fileUrl ? `<a href="${fileUrl}" target="_blank">Ko'rish</a>` : '—'}</td>
                    <td>${driver.destination_country || '—'}</td>
                </tr>
            `;
        });

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="5" style="color:red;text-align:center;">Server xatosi</td></tr>`;
    }
}


</script>

</body>
</html>
