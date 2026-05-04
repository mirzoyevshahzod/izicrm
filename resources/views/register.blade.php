<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ro'yxatdan O'tish - Driver Management</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .register-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .terms {
            font-size: 12px;
            color: #666;
            margin-top: 15px;
            text-align: center;
        }

        .terms a {
            color: #667eea;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .login-link a:hover {
            color: #764ba2;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }

            .register-header h1 {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <h1>📝 Ro'yxatdan O'tish</h1>
        <p>Driver hisobingizni yarating</p>
    </div>

    <form id="register-form">
        <div class="form-group">
            <label for="phone">To'liq Ismingiz</label>
            <input type="text" id="phone" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" placeholder="example@mail.com" required>
        </div>


        <div class="form-group">
            <label for="password">Parol</label>
            <input type="password" id="password" placeholder="Quvvat parol yarating" required>
        </div>

        <div class="form-group">
            <label for="confirm-password">Parolni tasdiqlang</label>
            <input type="password" id="confirm-password" placeholder="Parolni takrorlang" required>
        </div>

        <button type="submit" class="register-btn">Ro'yxatdan O'tish</button>

        <div class="terms">
            Ro'yxatdan o'tish orqali siz <a href="#">Foydalanish Shartlari</a> ga razilashingizni tasdiqlaysiz
        </div>

        <div class="login-link">
            Allaqachon akkauntingiz bormi? <a href="login.html">Kirish</a>
        </div>
    </form>
</div>
</body>
</html>

<script>
    document.getElementById('register-form').addEventListener('submit', async function(e) {
        e.preventDefault(); // formani standart submit qilinishini to'xtatamiz

        const name = document.getElementById('phone').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const password_confirmation = document.getElementById('confirm-password').value;

        try {
            const response = await fetch('https://izicrm.uz/api/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name,
                    email,
                    password,
                    password_confirmation
                })
            });

            const data = await response.json();

            if (response.ok) {
                window.location.href = 'https://izicrm.uz/drivers';
                // Optional: tokenni localStorage ga saqlash
                localStorage.setItem('auth_token', data.access_token);
                // Optional: boshqa sahifaga yo'naltirish
                // window.location.href = 'dashboard.html';
            } else {
                let errors = '';
                if (data.errors) {
                    for (let key in data.errors) {
                        errors += data.errors[key].join(', ') + '\n';
                    }
                } else if (data.message) {
                    errors = data.message;
                }
                alert('Xatolik:\n' + errors);
            }
        } catch (err) {
            console.error(err);
            alert('Server bilan bog‘lanishda xatolik yuz berdi.');
        }
    });
</script>
