<html>
 
<head>
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils"></script>
    <title>@yield('moduleName') | {{ config('app.name') }}</title>
 
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="stylesheet" href="{{ asset('/css/polaris.min.css?' . time()) }}" type="text/css" />
    <link rel="stylesheet" href="{{ asset('/css/custom.css?' . time()) }}" type="text/css" />
</head>
 
<body>
    <div class="Polaris-Frame">
        <main class="Polaris-Frame__Main">
            <div class="Polaris-Frame__Content">
                <div class="Polaris-Page">
                    <div class="Polaris-Page-Header">
                        <div class="Polaris-Page-Header__MainContent">
                            <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                                <div>
                                    <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                                        <div class="Polaris-Header-Title">
                                            <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Welcome DHL
                                                eCommerce APAC </h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
 
                    <div class="Polaris-Page__Content">
                        <div class="Polaris-Layout">
                            <div class="Polaris-Layout__Section">
                                <div class="dashboard">
                                    <div class="card">
                                        <a href="#" class="redirect-link" redirect-url="shipping-label/list">
                                            <div class="card-title">Shipping Labels</div>
                                            <div class="card-description">Manage all your shopping labels</div>
                                        </a>
                                    </div>
                                    <div class="card">
                                        <a href="#" class="redirect-link" redirect-url="configuration">
                                            <div class="card-title">Configuration</div>
                                            <div class="card-description">Manage all configuration for the app</div>
                                        </a>
                                    </div>
                                    <div class="card">
                                        <a href="#" class="redirect-link" redirect-url="pickup-accounts">
                                            <div class="card-title">Pickup Accounts</div>
                                            <div class="card-description">Manage all you pickup accounts</div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".redirect-link").forEach(function (link) {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                var redirectUrl = this.getAttribute("redirect-url");
                if (window['app-bridge']) {
                    var AppBridge = window['app-bridge'];
                    var createApp = AppBridge.default || AppBridge;
                    var actions = AppBridge.actions;
                    var app = createApp({
                        apiKey: "{{ config('services.shopify-app.api_key') }}",
                        host: new URLSearchParams(window.location.search).get('host'),
                        forceRedirect: true,
                    });
                    var redirect = actions.Redirect.create(app);
                    redirect.dispatch(actions.Redirect.Action.APP, '/' + redirectUrl);
                } else {
                    window.location.pathname = '/' + redirectUrl;
                }
            });
        });
    });
</script>
 

</html>