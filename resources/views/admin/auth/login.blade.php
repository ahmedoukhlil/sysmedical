<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion Admin — Plateforme</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f5f7ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 16px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(30, 58, 138, 0.10);
            overflow: hidden;
        }
        .login-header {
            background: #1e3a8a;
            padding: 28px 24px 22px;
            text-align: center;
        }
        .login-header h1 {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        .login-body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #1e293b; }
        input[type=email], input[type=password] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background: #1e3a8a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .error { color: #dc2626; font-size: 13px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>Administration Plateforme</h1>
        </div>
        <div class="login-body">
            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>
