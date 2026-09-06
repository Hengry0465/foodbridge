<!DOCTYPE html>
<html>

<head>
    <title>FoodBridge Pickup Management</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: #198754;
            color: white;
            overflow-y: auto;
        }

        .logo {
            padding: 25px;
            text-align: center;
            font-size: 30px;
            font-weight: bold;
            border-bottom: 1px solid rgba(255, 255, 255, .2);
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            margin: 8px;
            border-radius: 10px;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, .15);
            color: white;
        }

        .content {
            margin-left: 260px;
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 20px;
            color: white;
        }

        .scheduled {
            background: #0d6efd;
        }

        .confirmed {
            background: #198754;
        }

        .completed {
            background: #6f42c1;
        }

        .cancelled {
            background: #dc3545;
        }

        .expired_pickup {
            background: #6c757d;
        }
    </style>

</head>

<body>

    @php
        $dashboardRoute = match (auth()->user()->role ?? null) {
            'donor' => 'donor.dashboard',
            'recipient' => 'recipient.dashboard',
            default => 'home',
        };
    @endphp

    <div class="sidebar">

        <div class="logo">
            🍽 FoodBridge
        </div>

        <a href="{{ route($dashboardRoute) }}">
            📊 Dashboard
        </a>

        @if ((auth()->user()->role ?? null) === 'recipient')
            <a href="{{ route('recipient.pickups') }}">
                📅 Recipient Portal
            </a>
            <a href="{{ route('recipient.pickups.history') }}">
                📜 Pickup History
            </a>
        @endif

        @if ((auth()->user()->role ?? null) === 'donor')
            <a href="{{ route('donor.pickups') }}">
                🚚 Donor Portal
            </a>
            <a href="{{ route('donor.pickups.history') }}">
                📜 Pickup History
            </a>
        @endif

    </div>

    <div class="content">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
