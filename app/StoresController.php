<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoresController extends Controller
{
    public function create(Request $request)
    {
        if (!$request->user()) {
            abort(401);
        }

        // show any stores user already has
        $stores = $request->user()
            ->stores()
            ->select('stores.id', 'stores.store_id', 'stores.store_name')
            ->orderBy('stores.id')
            ->get();

        return Inertia::render('stores/Add', [
            'stores' => $stores,
            'active_store_id' => (int) $request->session()->get('active_store_id', 0),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'store_id' => ['required', 'string', 'max:64'],
        ]);

        $publicStoreId = trim($validated['store_id']);

        $store = Store::where('store_id', $publicStoreId)->first();
        if (!$store) {
            return back()->with('error', 'Store not found. Pair the website first, then paste the Store ID here.');
        }

        Membership::firstOrCreate(
            ['user_id' => $user->id, 'store_id' => $store->id],
            ['role' => 'owner']
        );

        // Make this store active
        $request->session()->put('active_store_id', (int) $store->id);

        return redirect('/app')->with('success', 'Store added successfully.');
    }


    public function switch(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'store_pk' => ['required', 'integer'],
        ]);

        $storePk = (int) $validated['store_pk'];

        $hasMembership = $user->memberships()->where('store_id', $storePk)->exists();
        if (!$hasMembership) {
            abort(403);
        }

        $request->session()->put('active_store_id', $storePk);

        return back()->with('success', 'Switched store.');
    }


    public function switchPage(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $stores = $user->stores()
            ->select('stores.id', 'stores.store_id', 'stores.store_name')
            ->orderBy('stores.id')
            ->get();

        return Inertia::render('stores/Switch', [
            'stores' => $stores,
            'active_store_id' => (int) $request->session()->get('active_store_id', 0),
        ]);
    }

    public function switchStore(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'store_pk' => ['required', 'integer'],
        ]);

        $storePk = (int) $validated['store_pk'];

        $hasMembership = $user->memberships()->where('store_id', $storePk)->exists();
        if (!$hasMembership) {
            abort(403, 'You do not have access to this store.');
        }

        $request->session()->put('active_store_id', $storePk);

        return redirect('/app/orders')->with('success', 'Switched store.');
    }
}
