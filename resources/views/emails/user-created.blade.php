<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .credentials {
            background-color: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 3px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Project Management!</h1>
        </div>
        <div class="content">
            <p>Hello {{ $userName }},</p>
            
            <p>Your account has been successfully created. You can now log in to the Project Management system.</p>
            
            <h2>Your Login Credentials</h2>
            <div class="credentials">
                <p><strong>Email:</strong> {{ $email }}</p>
                @if ($temporaryPassword)
                    <p><strong>Temporary Password:</strong> <code>{{ $temporaryPassword }}</code></p>
                    <p><em>Note: This is a temporary password. You will be required to change it upon first login.</em></p>
                @endif
            </div>
            
            <p>You can now access the project management system and start collaborating with your team.</p>
            
            <h2>Next Steps</h2>
            <ul>
                <li>Log in with your credentials</li>
                <li>Set up your profile</li>
                <li>View your workspaces and projects</li>
                <li>Start tracking tasks and progress</li>
            </ul>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>The Project Management Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Project Management. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
