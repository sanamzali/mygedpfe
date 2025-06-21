<!DOCTYPE html>
<html>

<head>
    <title>Welcome to Our Application</title>
</head>

<body>
    <h1>Welcome {{ $user->name }},</h1>

    <p>Your account has been successfully created.</p>

    <p>Here are your login credentials:</p>
    <ul>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> {{ $plainPassword }}</li>
    </ul>

    <p>Please make sure to change your password after your first login.</p>

</body>

</html>
