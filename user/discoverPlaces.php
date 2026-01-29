<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$destinations = [
    ['name' => 'Pokhara', 'category' => 'City', 'type' => 'Leisure'],
    ['name' => 'Bandipur', 'category' => 'Culture', 'type' => 'Leisure'],
    ['name' => 'Annapurna Base Camp', 'category' => 'Adventure', 'type' => 'Trek'],
    ['name' => 'Mardi Himal Trek', 'category' => 'Adventure', 'type' => 'Trek'],
    ['name' => 'Ama Yangri', 'category' => 'Nature', 'type' => 'Hike'],
    ['name' => 'Langtang Valley', 'category' => 'Adventure', 'type' => 'Trek'],
    ['name' => 'Rara Lake', 'category' => 'Nature', 'type' => 'Leisure'],
    ['name' => 'Lumbini', 'category' => 'Spiritual', 'type' => 'Culture'],
    ['name' => 'Puri, Odisha', 'category' => 'Spiritual', 'type' => 'City'],
    ['name' => 'Darjeeling', 'category' => 'Nature', 'type' => 'City'],
    ['name' => 'Manali', 'category' => 'Adventure', 'type' => 'City'],
    ['name' => 'Kasol', 'category' => 'Nature', 'type' => 'Leisure']
];

$user_interests = [];
$res = $conn->query("
    SELECT i.interest_name 
    FROM user_interests ui 
    JOIN interests i ON ui.interest_id = i.id 
    WHERE ui.user_id = $user_id
");

while ($row = $res->fetch_assoc()) {
    $user_interests[] = $row['interest_name'];
}

$recommended = [];

foreach ($destinations as $place) {
    if (in_array($place['category'], $user_interests)) {
        $recommended[] = $place;
    }
}

if (empty($recommended)) {
    $recommended = array_slice($destinations, 0, 6);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Discover Destinations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #E8F4F8;
            font-family: Segoe UI
        }

        .main-content {
            margin-left: 250px;
            padding: 40px
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px
        }

        .places-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px
        }

        .place-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .05);
            transition: .3s
        }

        .place-card:hover {
            transform: translateY(-6px)
        }

        .place-image {
            height: 180px;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 40px;
            position: relative
        }

        .place-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #fff;
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600
        }

        .place-content {
            padding: 20px
        }

        .place-name {
            font-size: 18px;
            font-weight: 700
        }

        .explore-btn {
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            border: none;
            color: #fff;
            border-radius: 8px;
            font-weight: 600
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap
        }

        .filter-btn {
            padding: 8px 18px;
            border-radius: 20px;
            border: 2px solid #D4EDE1;
            background: #E8F4F8;
            color: #57C785;
            font-weight: 600
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: #57C785;
            color: #fff
        }

        @media(max-width:768px) {
            .main-content {
                margin-left: 0;
                padding: 20px
            }
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <h1 class="mb-2">Discover Places</h1>

        <h2 class="section-title">Recommended For You</h2>

        <div class="filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="Adventure">Adventure</button>
            <button class="filter-btn" data-filter="Nature">Nature</button>
            <button class="filter-btn" data-filter="Culture">Culture</button>
            <button class="filter-btn" data-filter="Spiritual">Spiritual</button>
        </div>

        <div class="places-grid" id="placesGrid">
            <?php foreach ($recommended as $place): ?>
                <div class="place-card place-item" data-category="<?= $place['category'] ?>">
                    <div class="place-image">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span class="place-badge"><?= $place['category'] ?></span>
                    </div>
                    <div class="place-content">
                        <div class="place-name"><?= htmlspecialchars($place['name']) ?></div>
                        <p class="text-muted"><?= $place['type'] ?> destination</p>
                        <button class="explore-btn">Explore</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="section-title mt-5">Popular Nearby Places</h2>

        <div class="places-grid">
            <?php foreach (array_slice($destinations, 0, 6) as $place): ?>
                <div class="place-card">
                    <div class="place-image">
                        <i class="bi bi-compass-fill"></i>
                        <span class="place-badge"><?= $place['category'] ?></span>
                    </div>
                    <div class="place-content">
                        <div class="place-name"><?= htmlspecialchars($place['name']) ?></div>
                        <p class="text-muted">High companion demand</p>
                        <button class="explore-btn">View Trips</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script>
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                let f = btn.dataset.filter;
                document.querySelectorAll('.place-item').forEach(card => {
                    card.style.display = (f === 'all' || card.dataset.category === f) ? 'block' : 'none';
                });
            }
        });
    </script>

</body>

</html>