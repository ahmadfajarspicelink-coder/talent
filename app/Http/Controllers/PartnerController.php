<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PartnerController extends Controller
{
    /**
     * Tampilkan daftar seluruh Partner beserta nama, tipe, alamat, PIC, dan
     * status (R3.4).
     */
    public function index(): View
    {
        return view('partners.index', [
            'partners' => Partner::orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan Partner baru sesuai input tervalidasi (R3.1).
     */
    public function store(StorePartnerRequest $request): RedirectResponse
    {
        Partner::create($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Partner berhasil ditambahkan.');
    }

    /**
     * Perbarui data Partner sesuai input tervalidasi (R3.5).
     */
    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $partner->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Partner berhasil diperbarui.');
    }

    /**
     * Hapus Partner. Jika Partner masih terhubung dengan minimal satu Order
     * (sebagai provider maupun vendor), tolak penghapusan dan tampilkan pesan
     * bahwa Partner masih dipakai (R3.7). Jika tidak terhubung Order manapun,
     * hapus data Partner (R3.6).
     */
    public function destroy(Partner $partner): RedirectResponse
    {
        if ($partner->hasLinkedOrders()) {
            return redirect()
                ->back()
                ->with('error', 'Partner masih dipakai');
        }

        $partner->delete();

        return redirect()
            ->back()
            ->with('success', 'Partner berhasil dihapus.');
    }
}
