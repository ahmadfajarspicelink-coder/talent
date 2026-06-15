<?php

namespace App\Observers;

use App\Models\Order;

/**
 * Observer terdekopel untuk Order.
 *
 * Tanggung jawab: ketika sebuah Order mencapai status Complete,
 * Client pemiliknya otomatis ditandai berstatus 'active' (Requirement 4.5).
 * Logika ini sengaja dipisahkan dari OrderController agar tidak menambah
 * coupling pada lapisan HTTP.
 */
class OrderObserver
{
    /**
     * Status akhir Order yang memicu aktivasi Client.
     */
    private const COMPLETE_STATUS = 'Client_Aktif';

    /**
     * Status aktif untuk Client.
     */
    private const CLIENT_ACTIVE = 'active';

    /**
     * Dipanggil setelah Order dibuat atau diperbarui (created + updated).
     * Saat status Order = Complete, aktifkan Client terkait.
     */
    public function saved(Order $order): void
    {
        if ($order->status !== self::COMPLETE_STATUS) {
            return;
        }

        $client = $order->client;

        // Hanya tulis bila Client ada dan belum aktif (hindari penulisan redundan).
        if ($client !== null && $client->status !== self::CLIENT_ACTIVE) {
            $client->status = self::CLIENT_ACTIVE;
            $client->save();
        }
    }
}
