<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\ShippinglabelController;
use Osiset\ShopifyApp\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\CarrierController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    dd("Cache is cleared");
});

Route::get('/test', function () {
    echo "RUNNING TO Test PROJECT...";
});

Route::get('/migrate', function () {
    $exitCode = Artisan::call('migrate', [
        '--force' => true,
    ]);
});

/**
 * Mandatory webhooks
 */
Route::middleware(['shopify_custom'])->group(function () {
    Route::any('customers/data_request', [DashboardController::class, 'customersDataRequest'])->name('customers.data_request');
    Route::any('customers/redact', [DashboardController::class, 'customersRedact'])->name('customers.redact');
    Route::any('shop/redact', [DashboardController::class, 'shopRedact'])->name('shop.redact');
});

// Dummy rate response route
Route::post('/rates-callback', [CarrierController::class, 'ratesCallback'])->name('carrier.rates');

Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    // Dashboard Routes
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Configuration Routes
    Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
    Route::post('configuration/add', [ConfigurationController::class, 'add'])->name('configuration.add');
    Route::post('configuration/auth', [ConfigurationController::class, 'auth'])->name('configuration.auth');

    // Pickup Routes
    Route::get('pickup-accounts', [PickupController::class, 'index'])->name('pickup.index');
    Route::get('add-pickup', [PickupController::class, 'show_pickup'])->name('pickup.showpickup');
    Route::post('pickup/add', [PickupController::class, 'add'])->name('pickup.add');
    Route::get('pickup-accounts/{num}', [PickupController::class, 'edit_pickup'])->name('pickup.editpickup');
    Route::post('pickup/edit', [PickupController::class, 'edit'])->name('pickup.edit');
    Route::post('pickup-accounts/list', [PickupController::class, 'list'])->name('pickup.list');
    Route::post('pickup-accounts/default', [PickupController::class, 'default'])->name('pickup.default');
    Route::post('pickup-accounts/status', [PickupController::class, 'status'])->name('pickup.status');
    Route::post('pickup-accounts/delete', [PickupController::class, 'delete'])->name('pickup.delete');

    // Shipping Label Routes
    Route::get('shipping-label/list', [ShippinglabelController::class, 'index'])->name('shippinglabel.index');
    Route::get('shipping-label/create', [ShippinglabelController::class, 'create'])->name('shippinglabel.create');
    Route::post('shipping-label/store', [ShippinglabelController::class, 'store'])->name('shippinglabel.store');
    Route::get('shipping-label/view/{order_id}', [ShippinglabelController::class, 'view'])->name('shippinglabel.view');
    Route::get('shipping-label/edit/{order_id}', [ShippinglabelController::class, 'edit'])->name('shippinglabel.edit');
    Route::post('shipping-label/update', [ShippinglabelController::class, 'update'])->name('shippinglabel.update');
    Route::get('shipping-label/delete', [ShippinglabelController::class, 'delete'])->name('shippinglabel.delete');
    
    Route::match(['get', 'post'], 'shipping-label/bulk-create', [ShippinglabelController::class, 'bulkCreate'])
    ->name('shippinglabel.bulk-create');
    
    // Download and Print Labels
    Route::post('/shippinglabel/download-labels', [ShippinglabelController::class, 'downloadLabels'])->name('shippinglabel.download-labels');
    Route::post('/shippinglabel/print-labels', [ShippinglabelController::class, 'printLabels'])->name('shippinglabel.print-labels');
    Route::get('/shippinglabel/download-direct/{label_id}', [ShippinglabelController::class, 'downloadLabelDirect'])->name('shippinglabel.download-direct');
    Route::get('/shippinglabel/print-direct/{label_id}', [ShippinglabelController::class, 'printLabelDirect'])->name('shippinglabel.print-direct');
    
    // Legacy Bulk Print for Already-Generated Labels
    Route::get('shipping-label/bulk-print', [ShippinglabelController::class, 'bulkPrint'])->name('shippingLabel.bulkPrint');

    // Webhook Route
    Route::post('/webhooks', function () {
        return response()->json(['message' => 'Webhook received successfully']);
    });
});

// Dedicated POST endpoint to avoid re-auth redirect when generating labels
Route::match(['get', 'post'],'shipping-label/bulk-create/submit', [ShippinglabelController::class, 'bulkCreate'])
    ->name('shippinglabel.bulk-create.submit');