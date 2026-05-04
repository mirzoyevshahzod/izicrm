<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Profili</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            font-weight: bold;
            color: #667eea;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .info-group {
            margin-bottom: 30px;
        }

        .info-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .info-group p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .divider {
            height: 1px;
            background: #e9ecef;
            margin: 30px 0;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        button {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }
        .btn-back {
            background: #c3bad3ff;
            color: white;
        }

        .btn-edit:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-delete:hover {
            background: #f5c6cb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(248, 215, 218, 0.3);
        }

        @media (max-width: 600px) {
            .content {
                padding: 25px 20px;
            }

            .info-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .header h1 {
                font-size: 24px;
            }

            .profile-image {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profil Header -->
        <div class="header">
            <div class="profile-image">AB</div>
            <h1>Abdullayev Baxrom</h1>
            <p>Senior Operator</p>
        </div>

        <!-- Profil Ma'lumotlari -->
        <div class="content">
            <!-- Asosiy Ma'lumotlar -->
            <div class="info-row">
                <div class="info-group">
                    <label >Email</label>
                    <p id="email-info" >abdullayev.baxrom@company.com</p>
                </div>
                <div class="info-group">
                    <label>Telefon Raqami</label>
                    <p id="phone-info">+998 90 123 45 67</p>
                </div>
            </div>

            


            <div class="divider"></div>

            <!-- Tugmalar -->
            <div class="button-group">
                <button id="backTo" class="btn-delete">Ortga</button>
                <!-- <button class="btn-edit">Tahrirlash</button>
                <button class="btn-delete">O'chirish</button> -->
            </div>

            <script>
                document.getElementById('backTo').addEventListener('click', function() {
                const role = window.currentUserRole; // globaldan olamiz
                    if (role === 'admin') {
                        window.location.href = '/admin/dashboard';
                    } else if (role === 'operation') {
                        window.location.href = '/attendance-dashboard';
                    } else if (role === 'driver') {
                        window.location.href = '/driver/dashboard';
                    } else {
                        window.location.href = '/';
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>
<script>
document.addEventListener("DOMContentLoaded", loadProfile);

async function loadProfile() {
    const token = localStorage.getItem('auth_token');

    if (!token) {
        alert("Token topilmadi. Iltimos qayta login qiling.");
        window.location.href = "/login";
        return;
    }

    try {
        const response = await fetch("https://izicrm.uz/api/profile", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            }
        });

        const data = await response.json();
        if (data.status !== "success") {
            alert("Profilni yuklashda xatolik.");
            return;
        }

        const user = data.user;
        const role = user.roles?.[0]?.name;
        window.currentUserRole = role;
        // console.log( user);

        // 🔹 HTML elementlariga joylash
        document.querySelector(".profile-image").innerHTML = getInitials(user.name);
        document.querySelector(".header h1").textContent = user.name;
        document.querySelector(".header p").textContent = "Operator"; // Agar rol bo'lsa dinamik qilamiz

        document.querySelector("#email-info").textContent = user.email;
        document.querySelector("#phone-info").textContent = user.phone_number ?? "-";

    } catch (error) {
        console.error(error);
        alert("Server bilan bog‘lanishda xatolik.");
    }
}

// 🔹 User ismidan bosh harflarni chiqarish (profile-image uchun)
function getInitials(name) {
    const words = name.split(" ");
    if (words.length >= 2) {
        return words[0][0] + words[1][0];
    }
    return name[0];
}
</script>
