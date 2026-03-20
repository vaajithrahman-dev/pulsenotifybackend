<?php

use App\CouponsController;
use App\EventsController;
use App\Http\Middleware\EnsureStoreMembership;
use App\MetricsController;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\MagicLinkController;
use App\NotificationsController;
use App\QrLoginController;
use App\OrderActionsController;
use App\OrdersController;
use App\StoresController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware('guest')->group(function () {
    Route::get('/magic-link', [MagicLinkController::class, 'show'])->name('magic-link.show');
    Route::post('/magic-link', [MagicLinkController::class, 'send'])->name('magic-link.send');
});

Route::get('/magic-link/{token}', [MagicLinkController::class, 'consume'])->name('magic-link.consume');
Route::get('/qr-login/{token}', [QrLoginController::class, 'consume'])->name('qr-login.consume');

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', EnsureStoreMembership::class])->group(function () {
    Route::get('/qr-login', [QrLoginController::class, 'show'])->name('qr-login.show');
});


Route::get('/debug/active-store', function () {
    return response()->json([
        'user_id' => auth()->id(),
        'active_store_id' => session('active_store_id'),
    ]);
});


Route::get('/debug/login', function () {
    $user = User::where('email', 'dev@pulsenotify.local')->firstOrFail();
    Auth::login($user);
    request()->session()->regenerate();

    return redirect('/debug/active-store');
});


// Route::post('/debug/switch-store', function (Request $request) {
//     $request->validate([
//         'store_id' => ['required', 'integer'],
//     ]);

//     $user = $request->user();

//     if (!$user) {
//         abort(401);
//     }

//     $storePk = (int) $request->input('store_id');

//     // Verify user belongs to this store
//     $hasMembership = $user->memberships()->where('store_id', $storePk)->exists();

//     if (!$hasMembership) {
//         abort(403, 'You do not have access to this store');
//     }

//     $request->session()->put('active_store_id', $storePk);

//     return response()->json([
//         'ok' => true,
//         'active_store_id' => $storePk,
//     ]);
// });


Route::get('/debug/switch-to/{store}', function (Request $request, int $store) {
    $user = $request->user();
    if (!$user) abort(401);

    $hasMembership = $user->memberships()->where('store_id', $store)->exists();
    if (!$hasMembership) abort(403);

    $request->session()->put('active_store_id', $store);

    return redirect('/debug/active-store');
});


Route::get('/debug/my-stores', function (Request $request) {
    $user = $request->user();
    if (!$user) abort(401);

    return response()->json([
        'active_store_id' => session('active_store_id'),
        'stores' => $user->stores()->select('stores.id', 'stores.store_id', 'stores.store_name')->get(),
    ]);
});

Route::get('/debug/orders-count', function () {
    return response()->json([
        'stores' => Store::query()->select('id','store_id','store_name')->orderBy('id')->get(),
        'order_counts' => Order::query()
            ->selectRaw('store_id, COUNT(*) as cnt')
            ->groupBy('store_id')
            ->orderBy('store_id')
            ->get(),
    ]);
});


// Route::post('/app/stores/switch', [StoresController::class, 'switch']);

Route::get('/app/stores/switch', [StoresController::class, 'switchPage']);
Route::post('/app/stores/switch', [StoresController::class, 'switchStore']);

Route::middleware([EnsureStoreMembership::class])->group(function () {
    Route::get('/app', function (Request $request) {
        return response()->json([
            'ok' => true,
            'user_id' => $request->user()->id,
            'active_store_id' => $request->attributes->get('active_store_id'),
        ]);
    });

    Route::get('/app/orders', [OrdersController::class, 'index']);
    // Route::get('/app/orders/show/{id}', [OrdersController::class, 'showById']);
    Route::get('/app/orders/{order_id}', [OrdersController::class, 'show']);
    Route::get('/app/orders/feed', [OrdersController::class, 'feed']);
    Route::get('/app/events', [EventsController::class, 'index']);
    Route::get('/app/coupons', [CouponsController::class, 'index']);
    Route::get('/app/metrics', [MetricsController::class, 'index']);
    Route::post('/app/orders/{orderId}/status', [OrderActionsController::class, 'updateStatus']);
    Route::post('/app/orders/{orderId}/notes', [OrderActionsController::class, 'addNote']);
    Route::get('/app/notifications', [NotificationsController::class, 'index']);


    Route::get('/app/stores/add', [StoresController::class, 'create']);
    Route::post('/app/stores/add', [StoresController::class, 'store']);
});



// Route::middleware(['auth', 'verified', 'set_active_store', 'ensure_store_membership'])->group(function () {
//     Route::get('/orders', [\App\OrdersController::class, 'index'])->name('orders.index');
// });


require __DIR__.'/settings.php';
