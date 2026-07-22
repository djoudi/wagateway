<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — WaGateway</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-gray-50 flex items-center justify-center font-[Inter]">

<div class="w-full max-w-sm px-4">

    <div class="flex items-center justify-center gap-2.5 mb-8">
        <div class="w-9 h-9 bg-[#25D366] rounded-xl flex items-center justify-center shadow-sm">
            <svg class="w-5 h-5 fill-white" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.558 4.118 1.532 5.845L.057 23.04a.5.5 0 0 0 .611.61l5.275-1.461A11.938 11.938 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
            </svg>
        </div>
        <div>
            <div class="text-lg font-bold text-gray-900 leading-none">WaGateway</div>
            <div class="text-xs text-gray-400 mt-0.5">Create your account</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-7">
        <h1 class="text-base font-semibold text-gray-900 mb-5">Get started for free</h1>

        @if (request()->query('plan') === 'pro' || request()->query('plan') === 'business')
            <div class="mb-4 flex items-center gap-2 p-3 bg-green-50 border border-green-100 rounded-lg text-xs text-green-700">
                <i class="ti ti-circle-check"></i>
                سننشئ حسابك الآن، ثم نوجّهك مباشرة لتفعيل خطة
                <strong class="mx-1">{{ request()->query('plan') === 'pro' ? 'Pro' : 'Business' }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            @if (request()->query('plan'))
                <input type="hidden" name="plan" value="{{ request()->query('plan') }}" />
            @endif

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 transition-all
                              @error('name') border-red-300 @enderror" />
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Email address</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 transition-all
                              @error('email') border-red-300 @enderror" />
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Password</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 transition-all
                              @error('password') border-red-300 @enderror" />
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 transition-all" />
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-[#25D366] hover:bg-green-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                Create account
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-500 mt-4">
        Already have an account?
        <a href="{{ route('login') }}" class="text-[#25D366] font-medium hover:underline">Sign in</a>
    </p>
</div>
</body>
</html>
