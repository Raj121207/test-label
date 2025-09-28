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
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Bulk label analysis</h1>
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
                                @if (!empty($downloadURL))
                                    <p>Your download is ready, click <a download target="_blank" href="{{ $downloadURL }}">here</a> to start downloading.</p>
                                    <br>
                                    <br>
                                @endif

                                @if (isset($error))
                                    <div class="Polaris-Banner Polaris-Banner--statusCritical Polaris-Banner--withinPage"
                                         tabindex="0" role="alert" aria-live="polite" aria-labelledby="PolarisBannerErrorHeading"
                                         aria-describedby="PolarisBannerErrorContent">
                                        <div class="Polaris-Banner__Ribbon">
                                            <span class="Polaris-Icon Polaris-Icon--colorCritical Polaris-Icon--applyColor">
                                                <span class="Polaris-VisuallyHidden"></span>
                                                <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                          d="M10 0c-5.514 0-10 4.486-10 10s4.486 10 10 10 10-4.486 10-10-4.486-10-10-10zm-1 6a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0v-4zm1 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2z">
                                                    </path>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="Polaris-Banner__ContentWrapper">
                                            <div class="Polaris-Banner__Heading" id="PolarisBannerErrorHeading">
                                                <p class="Polaris-Heading">Error</p>
                                            </div>
                                            <div class="Polaris-Banner__Content" id="PolarisBannerErrorContent">
                                                <p>{{ $error }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                @endif

                                <div class="Polaris-Banner Polaris-Banner--statusWarning Polaris-Banner--withinPage"
                                     tabindex="0" role="alert" aria-live="polite" aria-labelledby="PolarisBanner1Heading"
                                     aria-describedby="PolarisBanner1Content">
                                    <div class="Polaris-Banner__Ribbon">
                                        <span class="Polaris-Icon Polaris-Icon--colorWarning Polaris-Icon--applyColor">
                                            <span class="Polaris-VisuallyHidden"></span>
                                            <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false"
                                                 aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                      d="M10 0c-5.514 0-10 4.486-10 10s4.486 10 10 10 10-4.486 10-10-4.486-10-10-10zm-1 6a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0v-4zm1 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2z">
                                                </path>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="Polaris-Banner__ContentWrapper">
                                        <div class="Polaris-Banner__Heading" id="PolarisBanner1Heading">
                                            <p class="Polaris-Heading">Bulk label analysis</p>
                                        </div>
                                        <div class="Polaris-Banner__Content" id="PolarisBanner1Content">
                                            <ul class="Polaris-List">
                                                @if (isset($bulk_response) && !empty($bulk_response))
                                                    @foreach ($bulk_response as $response)
                                                        <li class="Polaris-List__Item"><b>{{ $response['order_id'] }}</b> : {{ $response['message'] }}</li>
                                                    @endforeach
                                                @else
                                                    <li class="Polaris-List__Item">No orders processed.</li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection