<?php

namespace App\Http\Controllers;

use Osiset\ShopifyApp\Http\Controllers\AuthController as BaseAuthController;
use Osiset\ShopifyApp\Traits\AuthController as AuthControllerTrait;
use Illuminate\Http\Request;
use Osiset\ShopifyApp\Actions\AuthenticateShop;
use Illuminate\Support\Facades\View;
use Osiset\ShopifyApp\Util;

class AuthController extends BaseAuthController
{
    use AuthControllerTrait;

    /**
     * Installing/authenticating a shop.
     *
     * @param Request $request
     * @param AuthenticateShop $authShop
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function authenticate(Request $request, AuthenticateShop $authShop)
    {
        if ($request->missing('shop') && !$request->user()) {
            throw new \Osiset\ShopifyApp\Exceptions\MissingShopDomainException('No authenticated user or shop domain');
        }

        // Get the shop domain
        $shopDomain = $request->has('shop')
            ? \Osiset\ShopifyApp\Objects\Values\ShopDomain::fromNative($request->get('shop'))
            : $request->user()->getDomain();

        // If the domain is obtained from $request->user()
        if ($request->missing('shop')) {
            $request['shop'] = $shopDomain->toNative();
        }

        // Run the action
        [$result, $status] = $authShop($request);

        if ($status === null) {
            throw new \Osiset\ShopifyApp\Exceptions\SignatureVerificationException('Invalid HMAC verification');
        } elseif ($status === false) {
            if (!$result['url']) {
                throw new \Osiset\ShopifyApp\Exceptions\MissingAuthUrlException('Missing auth url');
            }

            $shopDomain = $shopDomain->toNative();
            $shopOrigin = $shopDomain ?? $request->user()->name;

            return View::make(
                'shopify-app::auth.fullpage_redirect',
                [
                    'apiKey' => Util::getShopifyConfig('api_key', $shopOrigin),
                    'url' => $result['url'],
                    'host' => $request->get('host'),
                    'shopDomain' => $shopDomain,
                    'shopOrigin' => $shopOrigin,
                    'locale' => $request->get('locale'),
                ]
            );
        }

        // Go to home route
        return redirect()->route(
            Util::getShopifyConfig('route_names.home'),
            [
                'shop' => $shopDomain->toNative(),
                'host' => $request->get('host'),
                'locale' => $request->get('locale'),
            ]
        );
    }
} 