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
        .task-details {
            background-color: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 3px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        .status-in_progress {
            background-color: #3498db;
            color: white;
        }
        .status-done {
            background-color: #27ae60;
            color: white;
        }
        .status-todo {
            background-color: #e74c3c;
            color: white;
        }
        .priority-high {
            color: #c0392b;
            font-weight: bold;
        }
        .priority-medium {
            color: #f39c12;
            font-weight: bold;
        }
        .priority-low {
            color: #27ae60;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Task Progress Update</h1>
        </div>
        <div class="content">
            <p>Hello {{ $assigneeName }},</p>
            
            <p>There has been an update on one of your assigned tasks:</p>
            
            <div class="task-details">
                <h2>{{ $taskTitle }}</h2>
                <p><strong>Project:</strong> {{ $projectName }}</p>
                <p><strong>Status:</strong> <span class="status-badge status-{{ str_replace(' ', '_', strtolower($taskStatus)) }}">{{ ucfirst($taskStatus) }}</span></p>
                <p><strong>Priority:</strong> <span class="priority-{{ strtolower($priority) }}">{{ ucfirst($priority) }}</span></p>
                @if ($dueDate)
                    <p><strong>Due Date:</strong> {{ $dueDate->format('M d, Y') }}</p>
                @endif
            </div>
            
            <p>Please review the task details and take any necessary action. If you have any questions or concerns about this task, please reach out to your team lead or project manager.</p>
            
            <p>Best regards,<br>The Project Management Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Project Management. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
