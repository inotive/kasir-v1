<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $title ?? 'HIPPI KASIR' }}</title>

    @php
        $ogSetting = \App\Models\Setting::current();
        $ogStoreName = (string) ($ogSetting->store_name ?? config('app.name'));
        $ogPublicStorageUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        $ogLogoPath = trim((string) ($ogSetting->store_logo ?? ''), '/');
        $ogImageUrl = $ogLogoPath !== '' && $ogPublicStorageUrl !== '' ? $ogPublicStorageUrl.'/'.$ogLogoPath : null;
        $ogImageHost = $ogImageUrl ? (string) (parse_url($ogImageUrl, PHP_URL_HOST) ?? '') : '';
        if (in_array($ogImageHost, ['127.0.0.1', 'localhost'], true)) {
            $ogImageUrl = null;
        }
        $faviconUrl = $ogLogoPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($ogLogoPath).'?v='.$ogSetting->updated_at?->timestamp
            : asset('images/logo/pngtree-pools-icon-logo-design-activity-beach-summer-vector-png-image_12898075.png');
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}" type="image/png">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? $ogStoreName }}">
    <meta property="og:description" content="Struk pembayaran dari {{ $ogStoreName }}">
    @if ($ogImageUrl)
        <meta property="og:image" content="{{ $ogImageUrl }}">
    @endif
</head>

<body class="{{ $class ?? '' }} mx-auto max-w-md min-h-screen font-poppins bg-gray-50 text-gray-900">
    {{ $slot }}

    <script>
        if (typeof window.livewireCartRefreshInitialized === 'undefined') {
            window.livewireCartRefreshInitialized = true;
            document.addEventListener('livewire:navigated', () => {
                Livewire.dispatch('check-cart-updates');
            });
        }
    </script>
</body>

</html>
