<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>TravelSync - Business Management System</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <!-- Styles -->
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                line-height: 1.6;
                color: #1f2937;
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                min-height: 100vh;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 1rem;
            }

            /* Header */
            .header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                position: sticky;
                top: 0;
                z-index: 50;
            }

            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 0;
            }

            .logo {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
            }
            
            .logo img {
                height: 4rem;
                width: auto;
            }
            
            .logo-text {
                font-size: 1.5rem;
                font-weight: 700;
                color: #1e3a8a;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
                display: inline-block;
            }
            
            .btn-primary:hover {
                background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(30, 58, 138, 0.3);
            }

            .nav-links {
                display: flex;
                gap: 2rem;
                align-items: center;
            }

            .nav-link {
                color: #6b7280;
                text-decoration: none;
                font-weight: 500;
                transition: color 0.2s;
            }

            .nav-link:hover {
                color: #2563eb;
            }

            .btn-primary {
                background: #2563eb;
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.2s;
                border: none;
                cursor: pointer;
            }

            .btn-primary:hover {
                background: #1d4ed8;
                transform: translateY(-1px);
            }

            .btn-secondary {
                background: transparent;
                color: #2563eb;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                border: 2px solid #2563eb;
                transition: all 0.2s;
            }

            .btn-secondary:hover {
                background: #2563eb;
                color: white;
            }

            /* Hero Section */
            .hero {
                padding: 4rem 0;
                text-align: center;
            }

            .hero h1 {
                font-size: 3.5rem;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 1.5rem;
                line-height: 1.2;
            }

            .hero p {
                font-size: 1.25rem;
                color: #6b7280;
                margin-bottom: 2rem;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            /* Features Section */
            .features {
                padding: 4rem 0;
                background: white;
            }

            .section-title {
                text-align: center;
                font-size: 2.5rem;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 3rem;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin-top: 3rem;
            }

            .feature-card {
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(0, 0, 0, 0.05);
                transition: all 0.3s;
            }

            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }

            .feature-icon {
                width: 3rem;
                height: 3rem;
                background: #dbeafe;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1rem;
                color: #2563eb;
                font-size: 1.5rem;
            }

            .feature-card h3 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 0.75rem;
            }

            .feature-card p {
                color: #6b7280;
                line-height: 1.6;
            }

            /* Stats Section */
            .stats {
                padding: 4rem 0;
                background: #f8fafc;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 2rem;
                text-align: center;
            }

            .stat-item h3 {
                font-size: 2.5rem;
                font-weight: 700;
                color: #2563eb;
                margin-bottom: 0.5rem;
            }

            .stat-item p {
                color: #6b7280;
                font-weight: 500;
            }

            /* CTA Section */
            .cta {
                padding: 4rem 0;
                background: #2563eb;
                color: white;
                text-align: center;
            }

            .cta h2 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
            }

            .cta p {
                font-size: 1.25rem;
                margin-bottom: 2rem;
                opacity: 0.9;
            }

            .cta-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn-white {
                background: white;
                color: #2563eb;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.2s;
            }

            .btn-white:hover {
                background: #f8fafc;
                transform: translateY(-1px);
            }

            /* Footer */
            .footer {
                background: #1f2937;
                color: white;
                padding: 2rem 0;
                text-align: center;
            }

            .footer p {
                opacity: 0.8;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .hero h1 {
                    font-size: 2.5rem;
                }

                .hero p {
                    font-size: 1.1rem;
                }

                .section-title {
                    font-size: 2rem;
                }

                .nav-links {
                    gap: 1rem;
                }

                .hero-buttons {
                    flex-direction: column;
                    align-items: center;
                }

                .cta-buttons {
                    flex-direction: column;
                    align-items: center;
                }
            }

            /* Icons */
            .icon {
                width: auto;
                height: 4rem;
                fill: currentColor;
            }
        </style>
    </head>
    <body>
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <a href="#" class="logo">
                        <img src="/logo.jpg" alt="TravelSync Logo" />                        
                    </a>
                    <nav class="nav-links">
                        <a href="#features" class="nav-link">Features</a>
                        <a href="#about" class="nav-link">About</a>
                        @auth
                            <a href="{{ url('/admin') }}" class="btn-primary">Dashboard</a>
                        @else
                            <a href="{{ url('/admin/login') }}" class="btn-primary">Staff Login</a>
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <h1>Streamline Your Business Operations</h1>
                <p>TravelSync is a comprehensive business management system that helps you manage leads, customers, invoices, and operations efficiently. Take control of your business with our powerful tools.</p>
                <div class="hero-buttons">
                    <a href="#features" class="btn-secondary">Learn More</a>
                    @auth
                        <a href="{{ url('/admin') }}" class="btn-primary">Go to Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="btn-primary">Get Started</a>
                    @endauth
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="container">
                <h2 class="section-title">Powerful Features for Your Business</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <h3>Lead Management</h3>
                        <p>Track and manage your sales leads from initial contact to conversion. Organize leads by status, priority, and service type.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <h3>Customer Management</h3>
                        <p>Maintain comprehensive customer profiles, track interactions, and manage customer relationships effectively.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                            </svg>
                        </div>
                        <h3>Invoice & Billing</h3>
                        <p>Create professional invoices, track payments, and manage vendor bills. Streamline your financial operations.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                        </div>
                        <h3>Leave Management</h3>
                        <p>Manage employee leave requests, track time off, and maintain organized HR processes for your team.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3>Permission System</h3>
                        <p>Role-based access control with granular permissions. Secure your data while giving users appropriate access levels.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3>Analytics & Reports</h3>
                        <p>Comprehensive dashboards and reports to track your business performance and make data-driven decisions.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3>100%</h3>
                        <p>Secure & Reliable</p>
                    </div>
                    <div class="stat-item">
                        <h3>24/7</h3>
                        <p>System Availability</p>
                    </div>
                    <div class="stat-item">
                        <h3>Easy</h3>
                        <p>User Interface</p>
                    </div>
                    <div class="stat-item">
                        <h3>Fast</h3>
                        <p>Performance</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="container">
                <h2>Ready to Transform Your Business?</h2>
                <p>Join businesses that are already using TravelSync to streamline their operations and increase productivity.</p>
                <div class="cta-buttons">
                    @auth
                        <a href="{{ url('/admin') }}" class="btn-white">Access Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="btn-white">Start Now</a>
                    @endauth
                    <a href="#features" class="btn-secondary">Learn More</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p>&copy; {{ date('Y') }} TravelSync. All rights reserved. | Professional Business Management System</p>
            </div>
        </footer>
    </body>
</html>
