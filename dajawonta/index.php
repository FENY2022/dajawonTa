<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DajawonTa - Connect with Local Service Providers</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#e3f2fd',
                            100: '#bbdefb',
                            500: '#2196f3',
                            600: '#1e88e5',
                            700: '#1976d2',
                            800: '#1565c0',
                            900: '#0d47a1',
                        },
                        secondary: {
                            50: '#fffde7',
                            100: '#fff9c4',
                            500: '#ffeb3b',
                            600: '#fdd835',
                            700: '#fbc02d',
                            800: '#f9a825',
                            900: '#f57f17',
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'bounce-slow': 'bounce 2s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* Custom styles */
        .gradient-bg {
            background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
        }
        
        .service-card {
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(25, 118, 210, 0.1) 0%, rgba(13, 71, 161, 0.1) 100%);
            z-index: 0;
        }
        
        .testimonial-card {
            position: relative;
        }
        
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: -20px;
            left: 20px;
            font-size: 80px;
            color: #1976D2;
            opacity: 0.1;
            font-family: serif;
            line-height: 1;
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #1976D2;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(25, 118, 210, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #FFC107 0%, #F57F17 100%);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(255, 193, 7, 0.3);
        }
        
        /* Animation delays */
        .delay-100 {
            animation-delay: 100ms;
        }
        
        .delay-200 {
            animation-delay: 200ms;
        }
        
        .delay-300 {
            animation-delay: 300ms;
        }
        
        .delay-400 {
            animation-delay: 400ms;
        }
    </style>
</head>
<body class="font-sans text-gray-800 antialiased">

    <!-- Navigation -->
    <nav class="bg-white w-full fixed top-0 z-50 shadow-md py-3" x-data="{ isOpen: false, scrolled: false }" 
         @scroll.window="scrolled = window.pageYOffset > 10">
        <div :class="scrolled ? 'py-2' : 'py-3'" class="container mx-auto px-4 transition-all duration-300">
            <div class="flex justify-between items-center">
                <a href="#" class="flex items-center">
                    <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-10 w-auto">
                    <span class="ml-2 text-xl font-bold text-primary-700">Dajawon<span class="text-secondary-600">Ta</span></span>
                </a>

                <div class="hidden lg:flex items-center space-x-6">
                    <a href="#home" class="nav-link px-3 py-2 text-gray-600 font-medium hover:text-primary-700">Home</a>
                    <a href="#features" class="nav-link px-3 py-2 text-gray-600 font-medium hover:text-primary-700">Features</a>
                    <a href="#how-it-works" class="nav-link px-3 py-2 text-gray-600 font-medium hover:text-primary-700">How It Works</a>
                    <a href="#services" class="nav-link px-3 py-2 text-gray-600 font-medium hover:text-primary-700">Services</a>
                    <a href="#testimonials" class="nav-link px-3 py-2 text-gray-600 font-medium hover:text-primary-700">Testimonials</a>
                    <a href="#contact" class="nav-link px-3 py-2 text-gray-600 font-medium hover:text-primary-700">Contact</a>
                    <a href="login.php" class="ml-4 px-5 py-2 text-primary-700 border border-primary-700 rounded-lg font-medium hover:bg-primary-700 hover:text-white transition-colors duration-300">Login</a>
                    <a href="register.php" class="px-5 py-2 bg-primary-700 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors duration-300 shadow-md">Register</a>
                </div>

                <div class="lg:hidden">
                    <button @click="isOpen = !isOpen" class="text-gray-600 focus:outline-none p-2">
                        <svg class="w-6 h-6" :class="isOpen ? 'hidden' : 'block'" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                        <svg class="w-6 h-6" :class="isOpen ? 'block' : 'hidden'" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-4" 
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0" 
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="lg:hidden bg-white shadow-xl" style="display: none;">
            <div class="px-4 pt-2 pb-6 space-y-1">
                <a href="#home" class="block px-3 py-3 text-gray-600 font-medium hover:bg-primary-50 hover:text-primary-700 rounded-lg">Home</a>
                <a href="#features" class="block px-3 py-3 text-gray-600 font-medium hover:bg-primary-50 hover:text-primary-700 rounded-lg">Features</a>
                <a href="#how-it-works" class="block px-3 py-3 text-gray-600 font-medium hover:bg-primary-50 hover:text-primary-700 rounded-lg">How It Works</a>
                <a href="#services" class="block px-3 py-3 text-gray-600 font-medium hover:bg-primary-50 hover:text-primary-700 rounded-lg">Services</a>
                <a href="#testimonials" class="block px-3 py-3 text-gray-600 font-medium hover:bg-primary-50 hover:text-primary-700 rounded-lg">Testimonials</a>
                <a href="#contact" class="block px-3 py-3 text-gray-600 font-medium hover:bg-primary-50 hover:text-primary-700 rounded-lg">Contact</a>
                <div class="border-t border-gray-200 mt-4 pt-4 flex flex-col space-y-3">
                    <a href="login.php" class="w-full text-center px-6 py-3 text-primary-700 border border-primary-700 rounded-lg font-medium hover:bg-primary-700 hover:text-white transition-colors">Login</a>
                    <a href="register.php" class="w-full text-center px-6 py-3 bg-primary-700 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors shadow-md">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-16">
        <!-- Hero Section -->
        <section id="home" class="relative text-white gradient-bg overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="absolute top-0 right-0 -mr-40 -mt-40 w-80 h-80 bg-white opacity-10 rounded-full"></div>
                <div class="absolute bottom-0 left-0 -ml-40 -mb-40 w-80 h-80 bg-white opacity-10 rounded-full"></div>
            </div>
            
            <div class="container mx-auto px-4 py-28 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="text-center lg:text-left animate-fade-in">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">Connecting Local Clients with Trusted Service Providers</h1>
                        <p class="text-xl text-blue-100 mb-8 max-w-lg mx-auto lg:mx-0">DajawonTa makes it easy to find, book, and manage services from skilled professionals in Cantilan, Surigao del Sur.</p>
                        <div class="flex flex-wrap justify-center lg:justify-start gap-4">
                            <a href="#services" class="btn-secondary px-8 py-4 text-lg font-semibold rounded-lg shadow-lg flex items-center">
                                Find Services <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                            <a href="register.php" class="px-8 py-4 border-2 border-white text-white text-lg font-semibold rounded-lg hover:bg-white hover:text-primary-700 transition-all duration-300">
                                Become a Provider
                            </a>
                        </div>
                    </div>
                    <div class="hidden lg:block animate-slide-up delay-100">
                        <div class="relative">
                            <div class="absolute -inset-4 bg-white opacity-20 rounded-2xl transform rotate-3"></div>
                            <img src="https://images.unsplash.com/photo-1599643477891-552436dfac3d?ixlib=rb-4.0.3&auto=format&fit=crop&w=700&q=80" class="relative w-full h-auto rounded-2xl shadow-2xl" alt="Filipino Service Provider">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="absolute bottom-0 left-0 w-full hidden lg:block">
                <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="w-full h-16 text-white fill-current">
                    <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z"></path>
                </svg>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-24 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16 animate-fade-in">
                    <h2 class="text-4xl font-bold mb-4">Why Choose DajawonTa</h2>
                    <div class="w-20 h-1 bg-primary-600 mx-auto mb-6"></div>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">We provide the best platform for service booking and management with these amazing features</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center service-card animate-fade-in delay-100">
                        <div class="w-16 h-16 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-2xl mx-auto mb-5">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Easy Discovery</h4>
                        <p class="text-gray-600">Browse through categorized services and find exactly what you need with our intuitive platform.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center service-card animate-fade-in delay-200">
                        <div class="w-16 h-16 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-2xl mx-auto mb-5">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Simple Booking</h4>
                        <p class="text-gray-600">Book services with just a few clicks, select your preferred schedule, and get instant confirmation.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center service-card animate-fade-in delay-300">
                        <div class="w-16 h-16 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-2xl mx-auto mb-5">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Secure Platform</h4>
                        <p class="text-gray-600">Your data is protected with advanced security measures and encrypted transactions.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center service-card animate-fade-in delay-400">
                        <div class="w-16 h-16 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-2xl mx-auto mb-5">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Rating System</h4>
                        <p class="text-gray-600">Rate your experience and read reviews to ensure quality service from trusted providers.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-20 bg-white">
            <div class="container mx-auto px-4">
                 <div class="text-center mb-16 animate-fade-in">
                    <h2 class="text-4xl font-bold mb-4">How DajawonTa Works</h2>
                    <div class="w-20 h-1 bg-primary-600 mx-auto mb-6"></div>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">Getting started with our platform is simple and straightforward</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="bg-gray-50 rounded-xl p-8 text-center relative animate-fade-in delay-100">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-primary-700 text-white rounded-full flex items-center justify-center text-xl font-bold shadow-lg">1</div>
                        <div class="w-20 h-20 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Register an Account</h4>
                        <p class="text-gray-600">Sign up as a client or service provider with your basic information.</p>
                    </div>
                     <div class="bg-gray-50 rounded-xl p-8 text-center relative animate-fade-in delay-200">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-primary-700 text-white rounded-full flex items-center justify-center text-xl font-bold shadow-lg">2</div>
                        <div class="w-20 h-20 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Browse Services</h4>
                        <p class="text-gray-600">Explore various service categories and find what you need.</p>
                    </div>
                     <div class="bg-gray-50 rounded-xl p-8 text-center relative animate-fade-in delay-300">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-primary-700 text-white rounded-full flex items-center justify-center text-xl font-bold shadow-lg">3</div>
                        <div class="w-20 h-20 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Book a Service</h4>
                        <p class="text-gray-600">Select your preferred service, schedule, and confirm booking.</p>
                    </div>
                     <div class="bg-gray-50 rounded-xl p-8 text-center relative animate-fade-in delay-400">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-primary-700 text-white rounded-full flex items-center justify-center text-xl font-bold shadow-lg">4</div>
                        <div class="w-20 h-20 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Get Work Done</h4>
                        <p class="text-gray-600">Connect with the provider, get your service delivered, and provide feedback.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="py-24 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16 animate-fade-in">
                    <h2 class="text-4xl font-bold mb-4">Our Services</h2>
                    <div class="w-20 h-1 bg-primary-600 mx-auto mb-6"></div>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">Explore our available service categories to find the right professional for your needs</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col service-card animate-fade-in delay-100">
                        <div class="h-48 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1558611848-73f7eb4001a1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" alt="Plumbing Service">
                        </div>
                        <div class="p-6 flex-grow flex flex-col">
                            <h4 class="text-xl font-semibold mb-2">Tubero (Plumbing)</h4>
                            <p class="text-gray-600 mb-4 flex-grow">Para sa mga sirang gripo, tubo, at water system.</p>
                            <a href="#" class="self-start btn-secondary px-5 py-2 text-sm rounded-lg font-medium">Explore</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col service-card animate-fade-in delay-200">
                        <div class="h-48 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" alt="Electrical Service">
                        </div>
                        <div class="p-6 flex-grow flex flex-col">
                            <h4 class="text-xl font-semibold mb-2">Electrician</h4>
                            <p class="text-gray-600 mb-4 flex-grow">Para sa kuryente, wiring, at electrical repair.</p>
                            <a href="#" class="self-start btn-secondary px-5 py-2 text-sm rounded-lg font-medium">Explore</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col service-card animate-fade-in delay-300">
                        <div class="h-48 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1595033994301-33778558273a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" alt="Carpenter Service">
                        </div>
                        <div class="p-6 flex-grow flex flex-col">
                            <h4 class="text-xl font-semibold mb-2">Carpenter</h4>
                            <p class="text-gray-600 mb-4 flex-grow">Gawa o ayos ng kahoy, furniture, at bahay.</p>
                            <a href="#" class="self-start btn-secondary px-5 py-2 text-sm rounded-lg font-medium">Explore</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col service-card animate-fade-in delay-100">
                        <div class="h-48 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1581193635722-1350a41c1a40?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" alt="Mason Service">
                        </div>
                        <div class="p-6 flex-grow flex flex-col">
                            <h4 class="text-xl font-semibold mb-2">Mason</h4>
                            <p class="text-gray-600 mb-4 flex-grow">Para sa construction at semento-related works.</p>
                            <a href="#" class="self-start btn-secondary px-5 py-2 text-sm rounded-lg font-medium">Explore</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col service-card animate-fade-in delay-200">
                        <div class="h-48 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1629895359203-12503ff2bddb?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" alt="Welder Service">
                        </div>
                        <div class="p-6 flex-grow flex flex-col">
                            <h4 class="text-xl font-semibold mb-2">Welder</h4>
                            <p class="text-gray-600 mb-4 flex-grow">Steel gates, grills, at iba pang welding works.</p>
                            <a href="#" class="self-start btn-secondary px-5 py-2 text-sm rounded-lg font-medium">Explore</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col service-card animate-fade-in delay-300">
                        <div class="h-48 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1598435889981-12f5516c5598?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" alt="Tech Repair Service">
                        </div>
                        <div class="p-6 flex-grow flex flex-col">
                            <h4 class="text-xl font-semibold mb-2">Computer/Phone Repair</h4>
                            <p class="text-gray-600 mb-4 flex-grow">Hardware and software troubleshooting.</p>
                            <a href="#" class="self-start btn-secondary px-5 py-2 text-sm rounded-lg font-medium">Explore</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="text-white text-center gradient-bg py-20 relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-40 h-40 bg-white opacity-10 rounded-full"></div>
                <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-40 h-40 bg-white opacity-10 rounded-full"></div>
            </div>
            
            <div class="container mx-auto px-4 relative z-10">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="stat-card p-6 rounded-lg animate-fade-in delay-100">
                        <div class="text-4xl md:text-5xl font-bold mb-2">500+</div>
                        <p class="text-blue-100">Registered Service Providers</p>
                    </div>
                    <div class="stat-card p-6 rounded-lg animate-fade-in delay-200">
                        <div class="text-4xl md:text-5xl font-bold mb-2">2,000+</div>
                        <p class="text-blue-100">Happy Clients</p>
                    </div>
                    <div class="stat-card p-6 rounded-lg animate-fade-in delay-300">
                        <div class="text-4xl md:text-5xl font-bold mb-2">3,500+</div>
                        <p class="text-blue-100">Completed Jobs</p>
                    </div>
                    <div class="stat-card p-6 rounded-lg animate-fade-in delay-400">
                        <div class="text-4xl md:text-5xl font-bold mb-2">4.8/5</div>
                        <p class="text-blue-100">Average Rating</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="py-24 bg-white">
            <div class="container mx-auto px-4">
                 <div class="text-center mb-16 animate-fade-in">
                    <h2 class="text-4xl font-bold mb-4">What Our Users Say</h2>
                     <div class="w-20 h-1 bg-primary-600 mx-auto mb-6"></div>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">Hear from our satisfied clients and service providers</p>
                </div>
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="testimonial-card bg-gray-50 rounded-xl p-8 animate-fade-in delay-100">
                        <div class="flex items-center mb-4">
                            <img src="https://images.unsplash.com/photo-1599643477891-552436dfac3d?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User Maria Santos" class="w-16 h-16 rounded-full object-cover mr-4 shadow-md">
                            <div>
                                <h5 class="font-bold">Maria Santos</h5>
                                <small class="text-gray-500">Homeowner</small>
                                <div class="flex text-yellow-400 mt-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600">"DajawonTa helped me find a reliable plumber within hours. The booking process was smooth, and the service was excellent!"</p>
                    </div>
                    <div class="testimonial-card bg-gray-50 rounded-xl p-8 animate-fade-in delay-200">
                        <div class="flex items-center mb-4">
                            <img src="https://images.unsplash.com/photo-1568482270994-a1a799370b3d?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User Juan Dela Cruz" class="w-16 h-16 rounded-full object-cover mr-4 shadow-md">
                            <div>
                                <h5 class="font-bold">Juan Dela Cruz</h5>
                                <small class="text-gray-500">Electrician</small>
                                <div class="flex text-yellow-400 mt-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600">"Since joining DajawonTa, I've connected with more clients than ever before. The platform is easy to use and helps grow my business."</p>
                    </div>
                    <div class="testimonial-card bg-gray-50 rounded-xl p-8 animate-fade-in delay-300">
                        <div class="flex items-center mb-4">
                            <img src="https://images.unsplash.com/photo-1604904612715-42bf7d2062a8?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User Sofia Reyes" class="w-16 h-16 rounded-full object-cover mr-4 shadow-md">
                            <div>
                                <h5 class="font-bold">Sofia Reyes</h5>
                                <small class="text-gray-500">Business Owner</small>
                                <div class="flex text-yellow-400 mt-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600">"The rating system ensures I only hire quality providers. DajawonTa has simplified how I find services for my business and home."</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section id="contact" class="py-24 text-center bg-gray-50">
            <div class="container mx-auto px-4 animate-fade-in">
                <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg p-12">
                    <h2 class="text-4xl font-bold mb-6">Ready to Get Started?</h2>
                    <p class="text-xl text-gray-600 mb-10">Join hundreds of satisfied users in Cantilan today</p>
                    <div class="flex flex-wrap justify-center gap-6">
                        <a href="register.php" class="btn-primary px-8 py-4 text-lg font-semibold rounded-lg shadow-lg">Sign Up as Client</a>
                        <a href="register.php" class="px-8 py-4 border-2 border-primary-700 text-primary-700 text-lg font-semibold rounded-lg hover:bg-primary-700 hover:text-white transition-all duration-300">Become a Provider</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div class="mb-8 lg:mb-0">
                    <div class="flex items-center mb-4">
                        <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-8 w-auto">
                        <span class="ml-2 text-xl font-bold text-white">Dajawon<span class="text-secondary-500">Ta</span></span>
                    </div>
                    <p class="text-gray-400 mb-6">Connecting local clients with trusted service providers in Cantilan, Surigao del Sur.</p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div>
                    <h5 class="text-lg font-semibold mb-4">Quick Links</h5>
                    <ul class="space-y-3">
                        <li><a href="#home" class="text-gray-400 hover:text-white hover:underline transition-colors">Home</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white hover:underline transition-colors">Features</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white hover:underline transition-colors">Services</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white hover:underline transition-colors">How It Works</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white hover:underline transition-colors">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h5 class="text-lg font-semibold mb-4">Services</h5>
                    <ul class="space-y-3">
                        <li><a href="#services" class="text-gray-400 hover:text-white hover:underline transition-colors">Plumbing</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white hover:underline transition-colors">Electrical</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white hover:underline transition-colors">Carpenter</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white hover:underline transition-colors">Welder</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white hover:underline transition-colors">More Services</a></li>
                    </ul>
                </div>
                
                <div>
                     <h5 class="text-lg font-semibold mb-4">Contact Us</h5>
                     <ul class="space-y-3 text-gray-400">
                         <li class="flex items-start">
                             <i class="fas fa-map-marker-alt mt-1 mr-3 text-primary-500"></i> 
                             <span>Cantilan, Surigao del Sur, Philippines</span>
                         </li>
                         <li class="flex items-start">
                             <i class="fas fa-phone mt-1 mr-3 text-primary-500"></i> 
                             <span>+63 912 345 6789</span>
                         </li>
                         <li class="flex items-start">
                             <i class="fas fa-envelope mt-1 mr-3 text-primary-500"></i> 
                             <span>info@dajawonta.com</span>
                         </li>
                     </ul>
                </div>
            </div>
            <hr class="border-t border-gray-700 my-8">
            <div class="text-center text-gray-500 pt-4">
                <p>&copy; 2025 DajawonTa. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>