<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentify - Car Rental Made Easy</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, #000000, #ff4500); /* Black to orange */
            --text-color: #333;
            --white-space: 5rem;
        }

        body {
            font-family: 'Arial', sans-serif;
            color: var(--text-color);
            background-color: #f8f9fa;
        }

        .navbar {
            background: var(--primary-gradient);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-link {
            color: #fff !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #ffd700 !important; /* Gold hover for contrast */
        }

        .hero {
            background: var(--primary-gradient);
            color: #fff;
            padding: var(--white-space) 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://via.placeholder.com/1920x600?text=Hero+Image') no-repeat center center/cover;
            opacity: 0.5;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .hero h1:hover {
            transform: scale(1.05);
        }

        .search-form {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.2);
            padding: 2rem;
            border-radius: 10px;
            transition: box-shadow 0.3s ease;
        }

        .search-form:hover {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .car-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .car-card img {
            height: 200px;
            object-fit: cover;
        }

        .section-padding {
            padding: var(--white-space) 0;
        }

        .why-us {
            background-color: #fff;
        }

        .footer {
            background: var(--primary-gradient);
            color: #fff;
            padding: 2rem 0;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .search-form {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Rentify</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Cars</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Signup</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Rent the Perfect Car for Your Journey</h1>
            <p>Discover a wide range of vehicles at affordable prices. Book now and hit the road!</p>
            <form class="search-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Pickup Location">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" placeholder="Pickup Date">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" placeholder="Return Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(to right, #ff4500, #ffd700);">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Featured Cars Section -->
    <section class="section-padding">
        <div class="container">
            <h2 class="text-center mb-4">Featured Cars</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card car-card">
                        <img src="https://via.placeholder.com/400x200?text=Car+1" class="card-img-top" alt="Car 1">
                        <div class="card-body">
                            <h5 class="card-title">Tesla Model 3</h5>
                            <p class="card-text">Electric, 300mi range, $50/day</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card car-card">
                        <img src="https://via.placeholder.com/400x200?text=Car+2" class="card-img-top" alt="Car 2">
                        <div class="card-body">
                            <h5 class="card-title">Toyota Camry</h5>
                            <p class="card-text">Sedan, Fuel-efficient, $40/day</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card car-card">
                        <img src="https://via.placeholder.com/400x200?text=Car+3" class="card-img-top" alt="Car 3">
                        <div class="card-body">
                            <h5 class="card-title">Jeep Wrangler</h5>
                            <p class="card-text">SUV, Off-road, $60/day</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-us section-padding">
        <div class="container">
            <h2 class="text-center mb-4">Why Choose Rentify?</h2>
            <div class="row text-center">
                <div class="col-md-4">
                    <h4>Affordable Rates</h4>
                    <p>Competitive pricing with no hidden fees.</p>
                </div>
                <div class="col-md-4">
                    <h4>Wide Selection</h4>
                    <p>From economy to luxury vehicles.</p>
                </div>
                <div class="col-md-4">
                    <h4>Easy Booking</h4>
                    <p>Streamlined process inspired by the best in the industry.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Rentify. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>