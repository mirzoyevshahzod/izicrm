<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirish - Driver Management</title>
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

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .remember-forgot a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.2s;
        }

        .remember-forgot a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .remember-forgot input[type="checkbox"] {
            width: auto;
            margin-right: 6px;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .signup-link a:hover {
            color: #764ba2;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h2>Qarzdorliklar Ro'yxatiga Kirish</h2>
        <p>Xodimlar Qarzdorliklar nazorati</p>
    </div>

    <form id="login-form" >
        <div class="form-group">
            <label for="email">Email yoki Telefon</label>
            <input type="text" id="email" placeholder="example@mail.com" required>
        </div>

        <div class="form-group">
            <label for="password">Parol</label>
            <input type="password" id="password" placeholder="Parolingizni kiriting" required>
        </div>

        <div class="remember-forgot">
            <label>
                <input type="checkbox"> Meni eslab qol
            </label>
        </div>

        <button type="submit" class="login-btn">Kirish</button>
    </form>
</div>
</body>
</html>
<script>
    document.getElementById('login-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('https://izicrm.uz/api/debtLogin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email,
                    password
                })
            });

            const data = await response.json();
            const roles = data.user.roles;

            if (response.ok) {
                // Tokenni saqlash
                localStorage.setItem('auth_token', data.access_token);
                console.log(roles.includes('admin'));
                // Logindan keyin redirect
                if (roles.includes('admin')) {
                    window.location.href = 'https://izicrm.uz/debt-dashboard';
                }else {
                    // Default redirect
                    window.location.href = 'https://izicrm.uz/debt-dashboard';
                }
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
