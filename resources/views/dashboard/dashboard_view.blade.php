@extends('layouts.master')

@section('moduleName')
    Dashboard
@endsection

@section('content')
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Welcome DHL eCommerce APAC</h1>
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
                            <a href="#" class="redirect-link" data-url="shipping-label/list">
                                <div class="card-title">Shipping Labels</div>
                                <div class="card-description">Manage all your shipping labels</div>
                            </a>
                        </div>
                        <div class="card">
                            <a href="#" class="redirect-link" data-url="configuration">
                                <div class="card-title">Configuration</div>
                                <div class="card-description">Manage all configuration for the app</div>
                            </a>
                        </div>
                        <div class="card">
                            <a href="#" class="redirect-link" data-url="pickup-accounts">
                                <div class="card-title">Pickup Accounts</div>
                                <div class="card-description">Manage all your pickup accounts</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const AppBridge = window["app-bridge"];
        const actions = AppBridge.actions;
        const createApp = AppBridge.default;

        const app = createApp({
            apiKey: "{{ config('services.shopify-app.api_key') }}",
            host: new URLSearchParams(location.search).get("host"),
            forceRedirect: true,
        });

        const redirect = actions.Redirect.create(app);

        document.querySelectorAll(".redirect-link").forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                const path = this.getAttribute("data-url");
                if (path) {
                    redirect.dispatch(actions.Redirect.Action.APP, `/${path}`);
                }
            });
        });
    });
</script>
@endsection
