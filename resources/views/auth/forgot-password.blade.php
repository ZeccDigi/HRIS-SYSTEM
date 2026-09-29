<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMW HRIS - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="relative min-h-screen flex items-center justify-center font-sans overflow-hidden">

    <div class="fixed inset-0 z-0">
       <img src="{{ asset('images/background-DMWHRIS.jpg') }}" class="w-full h-full object-cover"
             alt="Background">
            
    </div>

    <div class="relative z-10 container mx-auto flex flex-col lg:flex-row items-center justify-center gap-12 px-6">
        
        <div class="text-white max-w-lg text-center lg:text-left">
            <h1 class="text-5xl font-bold leading-tight">
                Department of <br> Migrant Workers
            </h1>
            <p class="mt-4 text-xl italic font-light opacity-90">
                Kagawaran ng Manggagawang Pandarayuhan
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-10 w-full max-w-md">
            <div class="flex flex-col items-center mb-8">
              <img src="{{ asset('images/logo-DMW.png') }}" alt="HRIS Logo" class="h-24 w-auto object-contain">
            </div>  

            <div class="mb-4 text-sm text-gray-600 text-center">
                {{ __('Forgot your password? Enter your HRIS Username and we will send a password reset link to your Recovery / OTP Email on file.') }}
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 text-center">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                
                <div class="mb-6">
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="HRIS Username, e.g. juan.delacruz@dmw.gov.ph"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-800 transition duration-200"
                           required autofocus>
                    @error('email')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" 
                        class="w-full bg-[#1e3a8a] text-white font-bold py-3 rounded-lg hover:bg-blue-900 transition duration-300 shadow-lg">
                    SEND PASSWORD RESET LINK
                </button>

                <div class="mt-8 text-center space-y-2">
                    <p class="text-gray-700 text-sm">
                        Remember your password? 
                        <a href="{{ route('login') }}" class="text-blue-700 font-semibold hover:underline">Sign in here</a>
                    </p>
                </div>

                <div class="mt-8 flex justify-center items-center gap-4">
                    <img src="https://www.gppb.gov.ph/wp-content/uploads/2023/07/Logo-Bagong-Pilipinas.png" 
                         alt="Bagong Pilipinas" class="h-10">
                    <img src="{{ asset('images/logo-DMW.png') }}" 
                         alt="DMW Seal" class="h-10">
                </div>
            </form>
        </div>
    </div>

</body>
</html>
