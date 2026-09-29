<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'DMW HRIS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:300,400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Custom Animations scoped to Welcome Page */
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes pulseGlow {
                0%, 100% { box-shadow: 0 0 0 0 rgba(252, 209, 22, 0.4); }
                50% { box-shadow: 0 0 0 15px rgba(252, 209, 22, 0); }
            }
            .animate-fade-up { animation: fadeUp 0.8s ease-out forwards; }
            .delay-100 { animation-delay: 100ms; }
            .delay-200 { animation-delay: 200ms; }
            .delay-300 { animation-delay: 300ms; }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-gray-900 dark:text-gray-100 selection:bg-[#0038a8] selection:text-white flex flex-col min-h-screen">
        
        <!-- Header/Nav -->
        <header class="w-full absolute top-0 left-0 right-0 z-50 p-6">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4 max-w-7xl mx-auto">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-medium text-gray-600 hover:text-[#0038a8] dark:text-gray-400 dark:hover:text-white transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-gray-600 hover:text-[#0038a8] dark:text-gray-400 dark:hover:text-white transition">Log in</a>
                    @endauth
                </nav>
            @endif
        </header>

        <!-- Main Content -->
        <main class="flex-grow flex items-center justify-center relative overflow-hidden">
            <!-- Decorative Background Elements -->
            <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
                <!-- Blue Gradient Blob -->
                <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-[#0038a8] opacity-5 dark:opacity-20 blur-3xl mix-blend-multiply"></div>
                <!-- Red Gradient Blob -->
                <div class="absolute -bottom-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-[#ce1126] opacity-5 dark:opacity-20 blur-3xl mix-blend-multiply"></div>
                <!-- Yellow Gradient Blob -->
                <div class="absolute top-[20%] right-[20%] w-[30%] h-[30%] rounded-full bg-[#fcd116] opacity-5 dark:opacity-10 blur-3xl mix-blend-multiply"></div>
            </div>

            <div class="relative z-10 w-full max-w-7xl mx-auto px-6 lg:px-8 py-12 lg:py-24 flex flex-col lg:flex-row items-center justify-between gap-12 lg:gap-16">
                
                <!-- Text Section -->
                <div class="w-full lg:w-1/2 flex flex-col justify-center text-center lg:text-left">
                    <div class="inline-flex items-center justify-center lg:justify-start space-x-3 mb-6 opacity-0 animate-fade-up">
                        <span class="w-12 h-1 bg-[#fcd116] rounded-full"></span>
                        <span class="text-sm font-bold uppercase tracking-widest text-[#0038a8] dark:text-[#fcd116]">Official HRIS Portal</span>
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight mb-6 opacity-0 animate-fade-up delay-100 leading-tight">
                        Department of <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0038a8] to-[#ce1126]">Migrant Workers</span>
                    </h1>
                    
                    <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto lg:mx-0 opacity-0 animate-fade-up delay-200">
                        Human Resource Information System. Empowering and protecting the workforce behind our modern-day heroes.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 opacity-0 animate-fade-up delay-300">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto px-8 py-4 bg-[#0038a8] text-white font-semibold rounded-xl hover:bg-[#002a7a] transition shadow-lg hover:shadow-xl hover:-translate-y-1 transform flex items-center justify-center gap-2">
                                    Go to Dashboard
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-4 bg-[#0038a8] text-white font-semibold rounded-xl hover:bg-[#002a7a] transition shadow-lg hover:shadow-xl hover:-translate-y-1 transform flex items-center justify-center gap-2">
                                    Access Portal
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>

                <!-- Image/Logo Section -->
                <div class="w-full lg:w-1/2 flex justify-center lg:justify-end relative opacity-0 animate-fade-up delay-200">
                    <div class="relative w-64 h-64 sm:w-80 sm:h-80 lg:w-[450px] lg:h-[450px]">
                        <!-- Decorative spinning ring -->
                        <div class="absolute inset-0 rounded-full border-2 border-dashed border-[#0038a8]/20 dark:border-[#fcd116]/20 animate-[spin_60s_linear_infinite]"></div>
                        <!-- Inner solid ring -->
                        <div class="absolute inset-4 rounded-full border-2 border-[#ce1126]/10 dark:border-[#ce1126]/20"></div>
                        
                        <!-- Logo Container -->
                        <div class="absolute inset-6 rounded-full bg-white shadow-2xl flex items-center justify-center overflow-hidden z-10" style="animation: pulseGlow 4s infinite ease-in-out;">
                            <img src="{{ asset('images/dmw_logo.png') }}" alt="Department of Migrant Workers Philippines Logo" class="w-full h-full object-cover rounded-full transition-transform duration-700 hover:scale-105">
                        </div>
                    </div>
                </div>

            </div>
        </main>
        
        <footer class="w-full py-8 text-center text-sm text-gray-500 dark:text-gray-400 relative z-20">
            &copy; {{ date('Y') }} Department of Migrant Workers Philippines. All rights reserved.
        </footer>
    </body>
</html>
