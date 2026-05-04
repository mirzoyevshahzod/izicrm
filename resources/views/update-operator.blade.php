<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operatorni Qo'shish - Admin Panel</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }

        .form-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .btn-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .form-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .form-body {
            padding: 30px 25px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background-color: #f8f9ff;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-cancel:hover {
            background: #e8e8e8;
            border-color: #d0d0d0;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            display: none;
            font-size: 14px;
        }

        .success-message.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .form-header {
                padding: 25px 20px;
            }

            .form-header h1 {
                font-size: 24px;
            }

            .form-body {
                padding: 25px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Yangi Operatorni Qo'shish</h1>
            <p>Operatorning to'liq ma'lumotlarini kiriting</p>
        </div>

        <div class="form-body">
            <div class="success-message" id="successMessage">
                ✓ Operator muvaffaqiyatli qo'shildi!
            </div>

            <form id="operatorForm">
                <div class="form-group">
                    <label for="fullname">To'liq Ism</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname"
                        placeholder="Masalan: Alisher Abdullayev"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Manzili</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="operator@example.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Telefon Raqami</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone"
                        placeholder="+998 (XX) XXX-XX-XX"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Parol</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        placeholder="Xavfsiz parol kiriting"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button type="reset" id="backTo" class="btn btn-delete">Ortga</button>
                    <button type="submit" class="btn btn-submit">Tahrirlash</button>
                    <button type="reset" class="btn btn-cancel">Tozalash</button>
                </div>
                 <script>
                 document.getElementById('backTo').addEventListener('click', function() {
                    // Bu yerga sahifa URLini yozing
                    window.location.href = '/operation-list';
                });
            </script>
            </form>
        </div>
    </div>

    <!-- <script>
        document.getElementById('operatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;

            console.log('Operator qo\'shildi:', { fullname, email, phone });

            const successMessage = document.getElementById('successMessage');
            successMessage.classList.add('show');

            this.reset();

            setTimeout(() => {
                successMessage.classList.remove('show');
            }, 4000);
        });
    </script> -->




    <script>






            document.addEventListener("DOMContentLoaded", () => {
                const id = window.location.pathname.split('/').pop();
                console.log("User ID:", id);
                 
                loadUser(id);
 
                document.getElementById('operatorForm').addEventListener('submit', async function(e) {
                e.preventDefault(); // Formani standart submit qilinishini to'xtatamiz

                // Formadan qiymatlarni olish
                const name = document.getElementById('fullname').value;
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                const password = document.getElementById('password').value;

                try {
                    const response = await fetch(`https://izicrm.uz/api/operator/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                        },
                        body: JSON.stringify({
                            name: name,
                            email: email,
                            phone_number: phone, // backendda phone_number column bo'lsa
                            password: password,
                            password_confirmation: password // agar backendda password_confirmation kerak bo'lsa
                        })
                    });

                    const data = await response.json();

                    if(response.ok){
                        console.log('User registered successfully:', data);
                        console.log('Operator muvaffaqiyatli qo\'shildi!');
                        // Formani tozalash
                        document.getElementById('operatorForm').reset();
                    } else {
                        console.error('Error:', data);
                        alert('Xatolik yuz berdi: ' + (data.message || 'Noma\'lum xato'));
                    }

                } catch (error) {
                    console.error('Network error:', error);
                    alert('Tarmoq xatosi yuz berdi.');
                }
            });


            });


            async function loadUser(id) {
                try {
                    // 🔹 API ga request
                    const response = await fetch(`https://izicrm.uz/api/operator/${id}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + localStorage.getItem('auth_token') // token bo'lsa
                        }
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success') { 

                        const op = data.operator;

                        // Inputlarga qiymatlarni joylashtiramiz
                        document.getElementById('fullname').value = op.name ?? '';
                        document.getElementById('email').value = op.email ?? '';
                        document.getElementById('phone').value = op.phone_number ?? '';

                        console.log("Operator loaded:", op);
                        
                    } else {
                        alert('Operatorlarni olishda xatolik yuz berdi.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Server bilan bog‘lanishda xatolik yuz berdi.');
                }
            }









</script>

</body>
</html>


 