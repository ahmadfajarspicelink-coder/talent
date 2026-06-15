<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Modul Paket Internet (master data) — CRUD sederhana id + nama.
 * Menjadi rujukan dropdown "Nama Paket" pada Modul Order.
 */
class PackageController extends Controller
{
    public function index(): View
    {
        return view('packages.index', [
            'packages' => Package::withCount('orders')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        Package::create($request->validated());

        return redirect()->route('packages.index')->with('success', 'Paket berhasil ditambahkan.');
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $package->update($request->validated());

        return redirect()->route('packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        // Tolak hapus bila masih dipakai Order agar referensi tetap valid.
        if ($package->orders()->exists()) {
            return redirect()->route('packages.index')->with('error', 'Paket masih dipakai Order.');
        }

        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Paket berhasil dihapus.');
    }
}
