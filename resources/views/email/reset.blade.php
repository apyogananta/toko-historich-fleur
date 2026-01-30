<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
</head>
<body>
    <p>Halo, {{ $user->name }}</p>
    <p>Kami menerima permintaan reset password untuk akun Anda.</p>
    <p>Klik tautan berikut untuk mereset password Anda:</p>
    <p>
        <a href="{{ $resetLink }}">{{ $resetLink }}</a>
    </p>
    <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
</body>
</html>
