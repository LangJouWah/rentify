<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentify - Your Trusted Car Rental Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <!-- Header -->
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="#features" class="hover:underline">Features</a>
                <a href="#how-it-works" class="hover:underline">How It Works</a>
                <a href="#contact" class="hover:underline">Contact</a>
                <a href="login.php" class="bg-gray-900 text-gray-100 px-4 py-2 rounded-lg hover:bg-gray-700 transition">Log In</a>
                <a href="signup.php" class="bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-600 transition">Sign Up</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="bg-teal-700 text-gray-100 py-20">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Rent or List Your Car with Ease</h2>
            <p class="text-lg md:text-xl mb-8 text-gray-300">Join Rentify to book your perfect ride or earn money by listing your car. Simple, secure, and hassle-free.</p>
            <div class="flex justify-center space-x-4">
                <a href="signup.php?role=owner" class="bg-yellow-500 text-gray-900 px-6 py-3 rounded-lg hover:bg-yellow-600 transition text-lg">List Your Car</a>
                <a href="signup.php?role=driver" class="bg-gray-900 text-gray-100 px-6 py-3 rounded-lg hover:bg-gray-700 transition text-lg">Book a Car</a>
            </div>
            <img src="cars/Honda_city.jpg" alt="Car Rental Hero Image" class="mt-8 mx-auto w-full max-w-2xl rounded-lg shadow-lg">
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 bg-gray-800">
        <div class="container mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">Why Choose Rentify?</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1: Easy Booking -->
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg text-center border border-gray-700">
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h4 class="text-xl font-semibold mb-2">Easy Booking Process</h4>
                    <p class="text-gray-300">Find and book your ideal car in minutes. Choose from a wide range of vehicles with flexible dates.</p>
                </div>
                <!-- Feature 2: Secure Payments -->
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg text-center border border-gray-700">
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <h4 class="text-xl font-semibold mb-2">Secure Payments</h4>
                    <p class="text-gray-300">Enjoy peace of mind with our secure payment system for both renters and owners.</p>
                </div>
                <!-- Feature 3: Earn Money -->
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg text-center border border-gray-700">
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="text-xl font-semibold mb-2">Earn Money</h4>
                    <p class="text-gray-300">List your car and start earning today. Set your own prices and manage your bookings effortlessly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-16 bg-gray-900">
        <div class="container mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">How Rentify Works</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- For Renters -->
                <div>
                    <h4 class="text-2xl font-semibold mb-4">For Renters</h4>
                    <ol class="list-decimal list-inside space-y-4 text-gray-300">
                        <li><strong>Search for Cars:</strong> Browse a variety of cars in your area.</li>
                        <li><strong>Book Instantly:</strong> Select your dates and book with ease.</li>
                        <li><strong>Drive & Enjoy:</strong> Pick up your car and hit the road!</li>
                    </ol>
                    <a href="signup.php?role=driver" class="mt-6 inline-block bg-teal-600 text-gray-100 px-6 py-3 rounded-lg hover:bg-teal-700 transition">Get Started as a Renter</a>
                </div>
                <!-- For Owners -->
                <div>
                    <h4 class="text-2xl font-semibold mb-4">For Owners</h4>
                    <ol class="list-decimal list-inside space-y-4 text-gray-300">
                        <li><strong>List Your Car:</strong> Add your car details and set your price.</li>
                        <li><strong>Manage Bookings:</strong> Review and accept booking requests.</li>
                        <li><strong>Earn Money:</strong> Get paid securely after each rental.</li>
                    </ol>
                    <a href="signup.php?role=owner" class="mt-6 inline-block bg-teal-600 text-gray-100 px-6 py-3 rounded-lg hover:bg-teal-700 transition">Get Started as an Owner</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 bg-gray-800">
        <div class="container mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">What Our Users Say</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg border border-gray-700">
                    <p class="text-gray-300 italic mb-4">"Renting a car through Rentify was so easy! I found the perfect car for my trip in minutes."</p>
                    <p class="text-teal-400 font-semibold">Maria S., Renter</p>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg border border-gray-700">
                    <p class="text-gray-300 italic mb-4">"Listing my car on Rentify has been a game-changer. I earn extra income with zero hassle."</p>
                    <p class="text-teal-400 font-semibold">Juan P., Car Owner</p>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg border border-gray-700">
                    <p class="text-gray-300 italic mb-4">"The secure payment system gave me peace of mind. Highly recommend Rentify!"</p>
                    <p class="text-teal-400 font-semibold">Anna L., Renter</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-teal-600 text-gray-100 py-16">
        <div class="container mx-auto px-4 text-center">
            <h3 class="text-3xl font-bold mb-4">Ready to Get Started?</h3>
            <p class="text-lg mb-8 text-gray-300">Join Rentify today and experience the easiest way to rent or list a car.</p>
            <div class="flex justify-center space-x-4">
                <a href="signup.php?role=driver" class="bg-gray-900 text-gray-100 px-6 py-3 rounded-lg hover:bg-gray-700 transition">Rent a Car</a>
                <a href="signup.php?role=owner" class="bg-yellow-500 text-gray-900 px-6 py-3 rounded-lg hover:bg-yellow-600 transition">List Your Car</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-gray-900">
        <div class="container mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">Contact Us</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h4 class="text-xl font-semibold mb-4">Get in Touch</h4>
                    <p class="text-gray-300 mb-4">Have questions? We're here to help!</p>
                    <p class="text-gray-300">Email: <a href="mailto:support@rentify.com" class="text-teal-400 hover:underline">support@rentify.com</a></p>
                    <p class="text-gray-300">Phone: +63 123 456 7890</p>
                </div>
                <div>
                    <h4 class="text-xl font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="https://facebook.com/rentify" class="text-teal-400 hover:text-teal-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"></path>
                            </svg>
                        </a>
                        <a href="https://twitter.com/rentify" class="text-teal-400 hover:text-teal-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-2.717 0-4.92 2.203-4.92 4.917 0 .39.045.765.127 1.124-4.09-.205-7.719-2.165-10.141-5.144-.424.729-.666 1.574-.666 2.475 0 1.708.87 3.215 2.188 4.099-.807-.026-1.566-.248-2.228-.616v.061c0 2.385 1.698 4.374 3.946 4.827-.413.111-.849.171-1.296.171-.314 0-.615-.03-.916-.086.621 1.938 2.418 3.348 4.548 3.385-1.669 1.307-3.776 2.083-6.061 2.083-.394 0-.787-.023-1.175-.068 2.179 1.397 4.768 2.212 7.548 2.212 9.057 0 14.009-7.502 14.009-14.008 0-.213-.005-.426-.014-.637.961-.695 1.797-1.562 2.457-2.549z"></path>
                            </svg>
                        </a>
                        <a href="https://instagram.com/rentify" class="text-teal-400 hover:text-teal-300">
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
    <footer class="bg-gray-900 text-gray-100 text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
        <div class="mt-2">
            <a href="https://rentify.com/terms" class="text-gray-400 hover:text-gray-200 mx-2">Terms of Service</a>
            <a href="https://rentify.com/privacy" class="text-gray-400 hover:text-gray-200 mx-2">Privacy Policy</a>
        </div>
    </footer>
</body>
</html>