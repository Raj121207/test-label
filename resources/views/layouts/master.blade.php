<html>
<head>
    <title>@yield('moduleName') | {{ config('app.name') }}</title>

    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <link rel="stylesheet" href="{{ asset('/css/polaris.min.css?' . time()) }}" type="text/css" />
    <link rel="stylesheet" href="{{ asset('/css/custom.css?' . time()) }}" type="text/css" />

    <!-- Shopify App Bridge v3 -->
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils@3"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    @yield('style')
</head>

<body>
    <div class="Polaris-Frame">
        <main class="Polaris-Frame__Main">
            <div class="Polaris-Frame__Content">
                @yield('content')
            </div>
        </main>
    </div>

    @if (\Osiset\ShopifyApp\Util::getShopifyConfig('appbridge_enabled'))
        <script>
            const AppBridge = window["app-bridge"];
            const actions = AppBridge.actions;
            const utils = window["app-bridge-utils"];
            const createApp = AppBridge.default;

            const app = createApp({
                apiKey: "{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name) }}",
                shopOrigin: "{{ $shopDomain ?? Auth::user()->name }}",
                host: "{{ \Request::get('host') }}",
                forceRedirect: true,
            });

            const AppLink = actions.AppLink;
            const NavigationMenu = actions.NavigationMenu;

            const link1 = AppLink.create(app, {
                label: "Shipping Labels",
                destination: "/shipping-label/list",
            });

            const link2 = AppLink.create(app, {
                label: "Configuration",
                destination: "/configuration",
            });

            const link3 = AppLink.create(app, {
                label: "Pickup Accounts",
                destination: "/pickup-accounts",
            });

            const navigationMenu = NavigationMenu.create(app, {
                items: [link1, link2, link3],
                active: link1,
            });
        </script>

        @include('shopify-app::partials.token_handler')
        @include('shopify-app::partials.flash_messages')
    @endif

    <script src="{{ asset('/js/jquery-3.7.0.min.js') }}"></script>
    <script src="{{ asset('/js/dhl-custom.js?' . time()) }}"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>

    <script>
        function showToast(type, message) {
            Swal.fire({
                toast: true,
                icon: type,
                title: message,
                position: 'bottom',
                showConfirmButton: false,
                timer: 3000
            });
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        window.appHandle = "{{ config('services.shopify-app.handle') }}";
    </script>

    @yield('script')
</body>
</html>
