<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Workflow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: var(--bg-color);
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            /* Prevent scrollbar flickering */
        }

        select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #555;
            background: #2a2a2a;
            color: #fff;
            font-size: 16px;
            appearance: none;
            /* simple Reset */
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: var(--bg-color);
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-card {
            background: rgba(40, 40, 40, 0.95);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--node-border);
            text-align: center;
        }

        .auth-card h2 {
            margin-bottom: 20px;
            color: var(--text-color);
            font-weight: 600;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .auth-input {
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #555;
            background: #2a2a2a;
            color: #fff;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .auth-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .auth-btn {
            padding: 12px;
            border-radius: 6px;
            border: none;
            background: var(--accent-color);
            color: white;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .auth-btn:hover {
            background: #4da3ff;
        }

        .toggle-text {
            margin-top: 15px;
            color: #aaa;
            font-size: 14px;
        }

        .toggle-link {
            color: var(--accent-color);
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
        }

        .toggle-link:hover {
            text-decoration: underline;
        }

        .hidden {
            display: none;
        }

        .error-msg {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #ff6666;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 15px;
            display: none;
        }

        .success-msg {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 15px;
            display: none;
        }
    </style>
</head>

<body>

    <div class="auth-card">
        <!-- Login Form -->
        <div id="login-form">
            <h2>Welcome Back</h2>
            <div id="login-error" class="error-msg"></div>
            <div id="login-success" class="success-msg"></div>
            <form class="auth-form" onsubmit="handleLogin(event)">
                <input type="text" id="login-username" class="auth-input" placeholder="Username" required>
                <input type="password" id="login-password" class="auth-input" placeholder="Password" required>
                <button type="submit" class="auth-btn">Log In</button>
            </form>
            <p class="toggle-text">
                Don't have an account? <span class="toggle-link" onclick="toggleForm()">Register</span>
            </p>
        </div>

        <!-- Register Form -->
        <div id="register-form" class="hidden">
            <h2>Create Account</h2>
            <div id="register-error" class="error-msg"></div>
            <form class="auth-form" onsubmit="handleRegister(event)">
                <input type="text" id="reg-emp-id" class="auth-input" placeholder="Employee ID" required>
                <input type="text" id="reg-username" class="auth-input" placeholder="Username" required>
                <input type="email" id="reg-email" class="auth-input" placeholder="Email" required>
                <input type="password" id="reg-password" class="auth-input" placeholder="Password" required>

                <select id="reg-position" class="auth-input" required>
                    <option value="" disabled selected>Select Position</option>
                </select>

                <select id="reg-dept" class="auth-input" required>
                    <option value="" disabled selected>Select Department</option>
                </select>

                <button type="submit" class="auth-btn">Register</button>
            </form>
            <p class="toggle-text">
                Already have an account? <span class="toggle-link" onclick="toggleForm()">Log In</span>
            </p>
        </div>
    </div>

    <script>
        async function toggleForm() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');

            // Clear messages
            document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.success-msg').forEach(el => el.style.display = 'none');

            if (loginForm.classList.contains('hidden')) {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                await loadMetaData();
            }
        }

        async function loadMetaData() {
            try {
                const res = await fetch('api.php?action=get_meta_data');
                const data = await res.json();

                const posSelect = document.getElementById('reg-position');
                const deptSelect = document.getElementById('reg-dept');

                // Keep first option
                posSelect.innerHTML = '<option value="" disabled selected>Select Position</option>';
                deptSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';

                if (data.positions) {
                    data.positions.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.innerText = p.name;
                        posSelect.appendChild(opt);
                    });
                }

                if (data.departments) {
                    data.departments.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.innerText = d.name;
                        deptSelect.appendChild(opt);
                    });
                }

            } catch (e) {
                console.error("Failed to load metadata", e);
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;
            const errorDiv = document.getElementById('login-error');

            try {
                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'flowBilder.php';
                } else {
                    errorDiv.textContent = data.error || 'Login failed';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Network error occurred';
                errorDiv.style.display = 'block';
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const emp_id = document.getElementById('reg-emp-id').value;
            const username = document.getElementById('reg-username').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;
            const position_id = document.getElementById('reg-position').value;
            const dept_id = document.getElementById('reg-dept').value;

            const errorDiv = document.getElementById('register-error');
            const successDiv = document.getElementById('login-success');

            try {
                const response = await fetch('api.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        emp_id, username, email, password, position_id, dept_id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    toggleForm();
                    successDiv.textContent = 'Registration successful! Please log in.';
                    successDiv.style.display = 'block';
                    document.getElementById('reg-username').value = '';
                    document.getElementById('reg-password').value = '';
                } else {
                    errorDiv.textContent = data.error || 'Registration failed';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Network error occurred';
                errorDiv.style.display = 'block';
            }
        }
    </script>
</body>

</html>