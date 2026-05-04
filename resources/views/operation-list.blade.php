<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operatorlar - Admin Panel</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            min-width: 200px;
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.7);
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

        .content {
            padding: 30px;
            overflow-x: auto;
        }

        .table-wrapper {
            width: 100%;
            border-collapse: collapse;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f3f4f6;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .operator-name {
            font-weight: 600;
            color: #1f2937;
        }

        .email-link {
            color: #667eea;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .email-link:hover {
            color: #764ba2;
        }

        .phone {
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-view {
            background: #e0e7ff;
            color: #667eea;
        }

        .btn-view:hover {
            background: #c7d2fe;
        }

        .btn-edit {
            background: #fef3c7;
            color: #d97706;
        }

        .btn-edit:hover {
            background: #fde68a;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            background: #f3f4f6;
            padding: 15px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                flex-direction: column;
            }

            .search-box input {
                min-width: unset;
            }

            .content {
                padding: 15px;
            }

            th, td {
                padding: 10px;
                font-size: 12px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Operatorlar</h1>
            <div class="search-box">
                <input type="text" placeholder="Ism, email yoki telefon orqali qidiring...">
                 
                   <button class="btn-add" id="btnAdd">+ Qo'shish</button>
                    <button class="btn-add" id="profile">Profile</button>

            <script>
                 document.getElementById('btnAdd').addEventListener('click', function() {
                    // Bu yerga sahifa URLini yozing
                    window.location.href = '/add-user';
                });
                 document.getElementById('profile').addEventListener('click', function() {
                    // Bu yerga sahifa URLini yozing
                    window.location.href = '/admin-profile';
                });
            </script>
            </div>
        </div>

        <div class="content">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ism</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<script>
async function loadOperators() {
    try {
        // 🔹 API ga request
        const response = await fetch('https://izicrm.uz/api/all-operator', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token') // token bo'lsa
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = ''; // eski satrlarni tozalaymiz

            data.users.forEach((user, index) => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td>#${String(user.id).padStart(3, '0')}</td>
                    <td class="operator-name">${user.name}</td>
                    <td><a href="mailto:${user.email}" class="email-link">${user.email}</a></td>
                    <td class="phone">${user.phone_number ?? '-'}</td>
                    <td class="actions">
                        <button id="edit" class="btn btn-edit" onclick="editUser(${user.id})">Tahrir</button>
                        <button class="btn btn-delete" onclick="deleteUser(${user.id})">O'chirish</button>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        } else {
            alert('Operatorlarni olishda xatolik yuz berdi.');
        }
    } catch (err) {
        console.error(err);
        alert('Server bilan bog‘lanishda xatolik yuz berdi.');
    }
}



                

// 🔹 Tahrir va o'chirish funksiyalari
function editUser(id) {
    window.location.href = `/update-user/${id}`;
}

function deleteUser(id) {
    if (confirm('Haqiqatdan ham foydalanuvchini o‘chirmoqchimisiz?')) {
        // API orqali delete request yuborish
        fetch(`https://izicrm.uz/api/delete-user/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                console.log('Foydalanuvchi o‘chirildi.');
                loadOperators(); // jadvalni yangilash
            } else {
                console.log('O‘chirishda xatolik yuz berdi.');
            }
        }).catch(err => {
            console.error(err);
            alert('Server bilan bog‘lanishda xatolik yuz berdi.');
        });
    }
}

// 🔹 Sahifa yuklanganda operatorlarni yuklash
document.addEventListener('DOMContentLoaded', loadOperators);
</script>
