<!DOCTYPE html>
<html>
<head>
    <title>FoodBridge Pickup Management</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:#f4f6f9;
        }

        .sidebar{
            position:fixed;
            left:0;
            top:0;
            width:260px;
            height:100vh;
            background:#198754;
            color:white;
            overflow-y:auto;
        }

        .logo{
            padding:25px;
            text-align:center;
            font-size:30px;
            font-weight:bold;
            border-bottom:1px solid rgba(255,255,255,.2);
        }

        .sidebar a{
            display:block;
            color:white;
            text-decoration:none;
            padding:15px 25px;
            margin:8px;
            border-radius:10px;
        }

        .sidebar a:hover{
            background:rgba(255,255,255,.15);
            color:white;
        }

        .content{
            margin-left:260px;
            padding:30px;
        }

        .card{
            border:none;
            border-radius:15px;
            box-shadow:0 4px 12px rgba(0,0,0,.08);
        }

        .status-badge{
            padding:6px 10px;
            border-radius:20px;
            color:white;
        }

        .scheduled{
            background:#0d6efd;
        }

        .confirmed{
            background:#198754;
        }

        .completed{
            background:#6f42c1;
        }

        .cancelled{
            background:#dc3545;
        }

    </style>

</head>

<body>

<div class="sidebar">

    <div class="logo">
        🍽 FoodBridge
    </div>

    <a href="/">
        📊 Dashboard
    </a>

    <a href="/recipient-pickup">
        📅 Recipient Portal
    </a>

    <a href="/donor-pickup">
        🚚 Donor Portal
    </a>

    <a href="/pickup-history">
        📜 Pickup History
    </a>

</div>

<div class="content">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>