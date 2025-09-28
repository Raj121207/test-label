<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.dashboard_view');
    }

    public function customersDataRequest(Request $request)
    {
        Log::info('-------------CUSTOMERS DATA REQUEST IN-------------');
    }

    public function customersRedact(Request $request)
    {
        Log::info('-------------CUSTOMERS REDACT IN-------------');
    }

    public function shopRedact(Request $request)
    {
        Log::info('-------------REDACT IN-------------');
    }
}
