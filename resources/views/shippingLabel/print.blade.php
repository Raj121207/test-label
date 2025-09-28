@extends('layouts.master')

@section('moduleName')
    Bulk shipping create
@endsection

@section('content')
    <div class="Polaris-Page">
        <div class="Polaris-Page-Header">
            <div class="Polaris-Page-Header__MainContent">
                <div class="Polaris-Page-Header__TitleActionMenuWrapper">
                    <div>
                        <div class="Polaris-Header-Title__TitleAndSubtitleWrapper">
                            <div class="Polaris-Header-Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Bulk Label Print</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="Polaris-Layout">
            <div class="Polaris-Layout__Section">
                <div class="Polaris-Layout">
                    <div class="Polaris-Layout__Section">
                        <div class="Polaris-Card">
                            <div class="Polaris-Card__Section">
                                @if (empty($downloadURL))
                                <p>One or more order's labels are not generated, please generate it first to print labels.</p>
                                @else
                                <p>Your download is ready, click <a download target="_blank" href="{{ $downloadURL }} ">here</a> to start downloading.</p>
                                <br>
                                <br>
                                <button id="redirect-page" class="Polaris-Banner__Button">Go to label listing</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $(document).on('click', '#redirect-page', function() {
                window.parent.location.href =
                    "https://admin.shopify.com/store/{{ explode('.', request('shop'))[0] }}/apps/{{ config('services.shopify-app.handle') }}/shipping-label/list";
            });
        });
    </script>
@endsection
