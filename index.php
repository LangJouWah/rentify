<?php
// Start session and include auth
session_start();
include 'auth.php';

// Check if user is already logged in
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);

if ($user) {
    // User is already logged in, redirect based on role
    if ($user['role'] == 'customer' || $user['role'] == 'driver') {
        header("Location: customer_dashboard.php");
    } elseif ($user['role'] == 'owner') {
        header("Location: owner_dashboard.php");
    } elseif ($user['role'] == 'admin') {
        header("Location: admin_dashboard.php");
    }
    exit;
}

// Add security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentify - Your Trusted Car Rental Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
    :root {
        --primary-teal: #0d9488;
        --primary-dark: #111827;
        --secondary-dark: #1f2937;
        --border-gray: #374151;
        --text-light: #f3f4f6;
        --text-muted: #9ca3af;
        --white-space: 5rem;
    }

    body {
        font-family: 'Arial', sans-serif;
        color: var(--text-light);
        background-color: var(--primary-dark);
    }

    .navbar {
        background: var(--primary-dark);
        border-bottom: 1px solid var(--border-gray);
        transition: all 0.3s ease;
    }

    .navbar-brand {
        color: var(--primary-teal) !important;
        font-weight: bold;
        font-size: 1.5rem;
    }

    .nav-link {
        color: var(--text-light) !important;
        transition: color 0.3s ease;
    }

    .nav-link:hover {
        color: var(--primary-teal) !important;
    }

    .hero {
        background: var(--secondary-dark);
        color: var(--text-light);
        padding: var(--white-space) 0;
        text-align: center;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid var(--border-gray);
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('https://via.placeholder.com/1920x600?text=Hero+Image') no-repeat center center/cover;
        opacity: 0.1;
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
        color: var(--text-light);
    }

    .hero h1:hover {
        transform: scale(1.05);
    }

    .search-form {
        max-width: 800px;
        margin: 0 auto;
        background: var(--primary-dark);
        padding: 2rem;
        border-radius: 10px;
        transition: box-shadow 0.3s ease;
        border: 1px solid var(--border-gray);
    }

    .search-form:hover {
        box-shadow: 0 0 20px rgba(13, 148, 136, 0.3);
    }

    .search-form input {
        background-color: var(--secondary-dark);
        color: var(--text-light);
        border: 1px solid var(--border-gray);
    }

    .search-form input::placeholder {
        color: var(--text-muted);
    }

    .search-form input:focus {
        background-color: var(--secondary-dark);
        color: var(--text-light);
        border-color: var(--primary-teal);
        box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
    }

    .car-card, .feature-card, .testimonial-card {
        background: var(--primary-dark);
        border: 1px solid var(--border-gray);
        border-radius: 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 2rem;
        color: var(--text-light);
    }

    .car-card:hover, .feature-card:hover, .testimonial-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2);
        border-color: var(--primary-teal);
    }

    .car-card img {
        height: 200px;
        object-fit: cover;
        border-radius: 10px 10px 0 0;
    }

    .section-padding {
        padding: var(--white-space) 0;
    }

    .btn-primary {
        background: var(--primary-teal);
        border: none;
        color: white;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background: #0f766e;
    }

    .btn-secondary {
        background-color: var(--secondary-dark);
        color: var(--text-light);
        border: 1px solid var(--primary-teal);
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: var(--primary-teal);
        border-color: var(--primary-teal);
        color: white;
    }

    .feature-card svg, .testimonial-card p.text-primary {
        color: var(--primary-teal);
    }

    .section-light {
        background-color: var(--secondary-dark);
        border-top: 1px solid var(--border-gray);
        border-bottom: 1px solid var(--border-gray);
    }

    .bg-dark {
        background-color: var(--primary-dark) !important;
        border-top: 1px solid var(--border-gray);
    }

    .footer {
        background: var(--primary-dark);
        color: var(--text-light);
        padding: 2rem 0;
        text-align: center;
        border-top: 1px solid var(--border-gray);
    }

    .footer a {
        color: var(--primary-teal);
        transition: color 0.3s ease;
        text-decoration: none;
    }

    .footer a:hover {
        color: #99f6e4;
    }

    /* Card body text colors */
    .card-body {
        color: var(--text-light);
    }

    .card-text {
        color: var(--text-muted);
    }

    /* List group items */
    .list-group-item {
        color: var(--text-light);
    }

    /* Text muted adjustments */
    .text-muted {
        color: var(--text-muted) !important;
    }

    /* Social icons */
    .text-primary {
        color: var(--primary-teal) !important;
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
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Log In</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-primary text-white px-4 py-2" href="signup.php">Sign Up</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Rent or List Your Car with Ease</h1>
            <p class="lead mb-4">Join Rentify to book your perfect ride or earn money by listing your car. Simple, secure, and hassle-free.</p>
            <form class="search-form mb-4">
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
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
            <div class="d-flex justify-content-center gap-3">
                <a href="signup.php?role=owner" class="btn btn-primary px-4 py-2">List Your Car</a>
                <a href="signup.php?role=driver" class="btn btn-secondary px-4 py-2">Book a Car</a>
            </div>
            <img src="cars/Honda_city.jpg" alt="Car Rental Hero Image" class="mt-4 mx-auto w-100" style="max-width: 600px; border-radius: 10px;">
        </div>
    </section>

    <!-- Featured Cars Section -->
    <section class="section-padding section-light">
        <div class="container">
            <h2 class="text-center mb-4">Featured Cars</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card car-card">
                        <img src="https://via.placeholder.com/400x200?text=Car+1" class="card-img-top" alt="Car 1">
                        <div class="card-body">
                            <h5 class="card-title">Tesla Model 3</h5>
                            <p class="card-text">Electric, 300mi range, $50/day</p>
                            <a href="signup.php?role=driver" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card car-card">
                        <img src="https://via.placeholder.com/400x200?text=Car+2" class="card-img-top" alt="Car 2">
                        <div class="card-body">
                            <h5 class="card-title">Toyota Camry</h5>
                            <p class="card-text">Sedan, Fuel-efficient, $40/day</p>
                            <a href="signup.php?role=driver" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card car-card">
                        <img src="https://via.placeholder.com/400x200?text=Car+3" class="card-img-top" alt="Car 3">
                        <div class="card-body">
                            <h5 class="card-title">Jeep Wrangler</h5>
                            <p class="card-text">SUV, Off-road, $60/day</p>
                            <a href="signup.php?role=driver" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section-padding">
        <div class="container">
            <h2 class="text-center mb-4">Why Choose Rentify?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h4 class="h5 mb-2">Easy Booking Process</h4>
                        <p class="text-muted">Find and book your ideal car in minutes. Choose from a wide range of vehicles with flexible dates.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <h4 class="h5 mb-2">Secure Payments</h4>
                        <p class="text-muted">Enjoy peace of mind with our secure payment system for both renters and owners.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h4 class="h5 mb-2">Earn Money</h4>
                        <p class="text-muted">List your car and start earning today. Set your own prices and manage your bookings effortlessly.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="section-padding section-light">
        <div class="container">
            <h2 class="text-center mb-4">How Rentify Works</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <h4 class="h5 mb-3">For Renters</h4>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item bg-transparent border-0">Search for Cars: Browse a variety of cars in your area.</li>
                        <li class="list-group-item bg-transparent border-0">Book Instantly: Select your dates and book with ease.</li>
                        <li class="list-group-item bg-transparent border-0">Drive & Enjoy: Pick up your car and hit the road!</li>
                    </ol>
                    <a href="signup.php?role=driver" class="btn btn-primary">Get Started as a Renter</a>
                </div>
                <div class="col-md-6">
                    <h4 class="h5 mb-3">For Owners</h4>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item bg-transparent border-0">List Your Car: Add your car details and set your price.</li>
                        <li class="list-group-item bg-transparent border-0">Manage Bookings: Review and accept booking requests.</li>
                        <li class="list-group-item bg-transparent border-0">Earn Money: Get paid securely after each rental.</li>
                    </ol>
                    <a href="signup.php?role=owner" class="btn btn-primary">Get Started as an Owner</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section-padding">
        <div class="container">
            <h2 class="text-center mb-4">What Our Users Say</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="text-muted fst-italic mb-3">"Renting a car through Rentify was so easy! I found the perfect car for my trip in minutes."</p>
                        <p class="fw-bold text-primary">Maria S., Renter</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="text-muted fst-italic mb-3">"Listing my car on Rentify has been a game-changer. I earn extra income with zero hassle."</p>
                        <p class="fw-bold text-primary">Juan P., Car Owner</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="text-muted fst-italic mb-3">"The secure payment system gave me peace of mind. Highly recommend Rentify!"</p>
                        <p class="fw-bold text-primary">Anna L., Renter</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-padding bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Get Started?</h2>
            <p class="lead mb-4">Join Rentify today and experience the easiest way to rent or list a car.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="signup.php?role=driver" class="btn btn-secondary px-4 py-2">Rent a Car</a>
                <a href="signup.php?role=owner" class="btn btn-primary px-4 py-2">List Your Car</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section-padding section-light">
        <div class="container">
            <h2 class="text-center mb-4">Contact Us</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <h4 class="h5 mb-3">Get in Touch</h4>
                    <p class="text-muted">Have questions? We're here to help!</p>
                    <p class="text-muted">Email: <a href="mailto:support@rentify.com" class="text-primary">support@rentify.com</a></p>
                    <p class="text-muted">Phone: +63 123 456 7890</p>
                </div>
                <div class="col-md-6">
                    <h4 class="h5 mb-3">Follow Us</h4>
                    <div class="d-flex gap-3">
                        <a href="https://facebook.com/rentify" class="text-primary">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"></path>
                            </svg>
                        </a>
                        <a href="https://twitter.com/rentify" class="text-primary">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-2.717 0-4.92 2.203-4.92 4.917 0 .39.045.765.127 1.124-4.09-.205-7.719-2.165-10.141-5.144-.424.729-.666 1.574-.666 2.475 0 1.708.87 3.215 2.188 4.099-.807-.026-1.566-.248-2.228-.616v.061c0 2.385 1.698 4.374 3.946 4.827-.413.111-.849.171-1.296.171-.314 0-.615-.03-.916-.086.621 1.938 2.418 3.348 4.548 3.385-1.669 1.307-3.776 2.083-6.061 2.083-.394 0-.787-.023-1.175-.068 2.179 1.397 4.768 2.212 7.548 2.212 9.057 0 14.009-7.502 14.009-14.008 0-.213-.005-.426-.014-.637.961-.695 1.797-1.562 2.457-2.549z"></path>
                            </svg>
                        </a>
                        <a href="https://instagram.com/rentify" class="text-primary">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.326 3.608 1.301.975.975 1.24 2.242 1.301 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.326 2.633-1.301 3.608-.975.975-2.242 1.24-3.608 1.301-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.326-3.608-1.301-.975-.975-1.24-2.242-1.301-3.608-.058-1.266-.07-1.646-.07-4.85s.012-3.584.07-4.85c.062-1.366.326-2.633 1.301-3.608.975-.975 2.242-1.24 3.608-1.301 1.266-.058 1.646-.07 4.85-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-1.627.074-3.083.393-4.243 1.553-1.16 1.16-1.479 2.616-1.553 4.243-.058 1.28-.072 1.688-.072 4.947s.014 3.667.072 4.947c.074 1.627.393 3.083 1.553 4.243 1.16 1.16 2.616 1.479 4.243 1.553 1.28.058 1.688.072 4.947.072s3.667-.014 4.947-.072c1.627-.074 3.083-.393 4.243-1.553 1.16-1.16 1.479-2.616 1.553-4.243.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.074-1.627-.393-3.083-1.553-4.243-1.16-1.16-2.616-1.479-4.243-1.553-1.28-.058-1.688-.072-4.947-.072zm0 5.838c-3.313 0-6 2.687-6 6s2.687 6 6 6 6-2.687 6-6-2.687-6-6-6zm0 10c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.441s.645 1.441 1.441 1.441 1.441-.645 1.441-1.441-.645-1.441-1.441-1.441z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Rentify. All rights reserved.</p>
            <div class="mt-2">
                <a href="https://rentify.com/terms" class="mx-2">Terms of Service</a>
                <a href="https://rentify.com/privacy" class="mx-2">Privacy Policy</a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Session Check Script -->
    <script>
        // Client-side session check as additional security
        function checkClientSession() {
            // Check if we have a JWT token in cookies
            const cookies = document.cookie.split(';');
            const jwtCookie = cookies.find(cookie => cookie.trim().startsWith('jwt_token='));
            
            if (jwtCookie) {
                // If token exists, redirect to dashboard
                window.location.href = 'dashboard.php';
            }
        }

        // Run check on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkClientSession();
        });

        // Also check when page becomes visible (user navigates back)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkClientSession();
            }
        });

        // Prevent back navigation after logout
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.go(1);
        };
    </script>
</body>
</html>
