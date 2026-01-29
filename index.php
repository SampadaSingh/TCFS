<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCFS - Travel Companion Finder System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            padding: 15px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white !important;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            margin: 0 10px;
        }

        .nav-link:hover {
            color: white !important;
        }

        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            text-align: center;
            color: white;
        }

        .hero-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: opacity 1s ease-in-out;
            opacity: 0;
            filter: brightness(0.6);
        }

        .hero-slide.active {
            opacity: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 20px;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        }

        .btn-primary-custom {
            background: white;
            color: #2A7B9B;
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 700;
            margin: 10px;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(90deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: #333;
            transform: translateY(-2px);
        }

        .features {
            padding: 80px 0;
        }

        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(87, 199, 133, 0.3);
        }

        .feature-icon {
            font-size: 40px;
            background: linear-gradient(45deg, #2A7B9B, #57C785, #EDDD53);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .feature-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .feature-text {
            color: #666;
            font-size: 14px;
        }

        .gallery {
            padding: 80px 0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .gallery-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            transition: transform 0.3s;
        }

        .gallery-card img:hover {
            transform: scale(1.05);
        }

        .how-it-works {
            padding: 80px 0;
        }

        .step {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .step:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(87, 199, 133, 0.3);
        }

        .step-icon {
            font-size: 40px;
            background: linear-gradient(45deg, #2A7B9B, #57C785, #EDDD53);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        footer {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
        }

        /* Responsive */
        @media(max-width:767px) {
            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 16px;
            }

            .btn-primary-custom {
                padding: 10px 30px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/">TCFS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="auth/register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="hero-slide active" style="background-image:url('assets/img/kori.jpg');"></div>
        <div class="hero-slide" style="background-image:url('assets/img/abc.jpg');"></div>
        <div class="hero-slide" style="background-image:url('assets/img/langtang.jpg');"></div>
        <div class="hero-slide" style="background-image:url('assets/img/panchpokhari.jpeg');"></div>
        <div class="hero-slide" style="background-image:url('assets/img/bandipur.jpg');"></div>
        <div class="hero-slide" style="background-image:url('assets/img/north_abc.jpg');"></div>

        <div class="hero-content">
            <h1>Find Your Perfect Travel Companion</h1>
            <p>Explore amazing destinations with like-minded travelers</p>
            <a href="auth/register.php" class="btn-primary-custom">Get Started</a>
            <a href="auth/login.php" class="btn-primary-custom" style="background: transparent; color:white; border:2px solid white;">Sign In</a>
        </div>
    </div>

    <div class="features">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-compass"></i></div>
                        <div class="feature-title">Discover Trips</div>
                        <div class="feature-text">Browse trips created by other travelers and find ones that match your style.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-users"></i></div>
                        <div class="feature-title">Find Companions</div>
                        <div class="feature-text">Our smart algorithm connects you with compatible travel buddies.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-map-pin"></i></div>
                        <div class="feature-title">Plan Together</div>
                        <div class="feature-text">Collaborate on accommodations, activities, and create unforgettable memories.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="gallery">
        <div class="container">
            <h2 class="text-center mb-5">Popular Destinations</h2>
            <div class="gallery-grid">
                <div class="gallery-card"><img src="assets/img/kori.jpg" alt="Kori"></div>
                <div class="gallery-card"><img src="assets/img/abc.jpg" alt="ABC Trek"></div>
                <div class="gallery-card"><img src="assets/img/langtang.jpg" alt="Langtang"></div>
                <div class="gallery-card"><img src="assets/img/bandipur.jpg" alt="Bandipur"></div>
                <div class="gallery-card"><img src="assets/img/north_abc.jpg" alt="North ABC"></div>
                <div class="gallery-card"><img src="assets/img/mardi.webp" alt="Mardi"></div>
                <div class="gallery-card"><img src="assets/img/champadevi.jpg" alt="Champadevi"></div>
                <div class="gallery-card"><img src="assets/img/panchpokhari.jpeg" alt="Panch Pokhari"></div>
            </div>
        </div>
    </div>

    <div class="how-it-works">
        <div id="about" class="container py-5">
            <h2 class="text-center mb-4">About Us</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="p-4 bg-white rounded-3 shadow-sm">
                        <p>Welcome to the Travel Companion Finding System (TCFS), your go-to platform for connecting with like-minded travelers. Whether you're an adventure seeker, a culture enthusiast, or simply looking for company on your next trip, TCFS is here to help you find the perfect travel companion.</p>
                        <p>Our mission is to make travel more enjoyable and accessible by fostering connections between travelers. With TCFS, you can create and join trips, share experiences, and build lasting friendships along the way.</p>
                        <p>Join our community today and start your journey towards unforgettable travel experiences!</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                        <h5>Sign Up</h5>
                        <p>Create your account and set your travel preferences.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-search"></i></div>
                        <h5>Discover Trips</h5>
                        <p>Find trips that match your interests and destinations.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-handshake"></i></div>
                        <h5>Connect & Plan</h5>
                        <p>Join trips with compatible companions and plan your adventure together.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2026 Travel Companion Finder System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const slides = document.querySelectorAll('.hero-slide');
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000);
    </script>

</body>

</html>