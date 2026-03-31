<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT202 M4 Todos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .scenario-list {
            list-style: none;
            padding: 0;
        }
        .scenario-list li {
            margin-bottom: 15px;
        }
        .scenario-list a {
            display: block;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #0066cc;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .scenario-list a:hover {
            background-color: #0066cc;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .description {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>IT202 Module 4 - Todos</h1>
    <ul class="scenario-list">
        <li>
            <a href="/M4/todos/create.php">Create</a>
            <div class="description">Add a new todo item</div>
        </li>
        <li>
            <a href="/M4/todos/pending.php">Pending</a>
            <div class="description">View and manage incomplete todo items</div>
        </li>
        <li>
            <a href="/M4/todos/completed.php">Completed</a>
            <div class="description">View completed todo items</div>
        </li>
    </ul>
</body>
</html>