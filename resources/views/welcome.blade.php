<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .container {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* <CHANGE> Background with truck image and dark overlay */
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://egsgroupapp.uz/storage/images/bg.jpg');
            background-size: cover;
            background-position: center;
            z-index: 1;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2;
        }

        /* Content wrapper */
        .content {
            position: relative;
            z-index: 3;
            text-align: center;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3rem;
            padding: 2rem;
        }

        /* Welcome text section */
        .welcome-section {
            animation: fadeIn 0.8s ease-out;
        }

        h1 {
            font-size: clamp(2.5rem, 8vw, 4rem);
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .subtitle {
            font-size: clamp(1.1rem, 4vw, 1.5rem);
            color: #fecaca;
            font-weight: 500;
        }

        /* Buttons container */
        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            min-width: 280px;
        }

        @media (min-width: 640px) {
            .buttons-container {
                flex-direction: row;
                gap: 2rem;
            }
        }

        /* Button styles */
        .btn {
            padding: 1rem 3rem;
            font-size: 1.125rem;
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }

        /* Login button */
        .btn-login {
            background-color: #ef4444;
            color: white;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        }

        .btn-login:hover {
            background-color: #dc2626;
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(239, 68, 68, 0.5);
        }

        /* Register button */
        .btn-register {
            background-color: transparent;
            color: white;
            border: 2px solid white;
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.1);
        }

        .btn-register:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(255, 255, 255, 0.2);
        }

        /* Bottom text */
        .footer-text {
            position: absolute;
            bottom: 2rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
            z-index: 3;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive design */
        @media (max-width: 640px) {
            .content {
                gap: 2rem;
            }

            h1 {
                margin-bottom: 0.5rem;
            }

            .btn {
                min-width: 160px;
                padding: 0.875rem 2rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="background"></div>
    <div class="overlay"></div>

    <div class="content">
        <div class="welcome-section">
            <h1>Welcome to Operator</h1>
            <p class="subtitle">Yuk tashishni boshqarish uchun eng yaxshi platforma</p>
        </div>

        <div class="buttons-container">
            <a href="{{ route('login') }}" class="btn btn-login">Login</a>
        </div>
    </div>

    <div class="footer-text">
        <p>Minglab foydalanuvchi bizga ishonadi</p>
    </div>
</div>
</body>
</html>
