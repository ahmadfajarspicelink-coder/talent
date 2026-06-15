@props([
    // Persentase_Progress (float 0..100) yang dihitung dari Status_Order.
    'percent' => 0,
])

{{--
    Komponen Indikator_Progress (R6.8, R6.9, R6.10).

    Bar progres bersudut membulat (rounded-full). Warna bergradasi mengikuti
    persentase: 0% = merah, 50% ≈ kuning, 100% = hijau (via HSL hue 0→120).
    Lebar bagian terisi = nilai persen, selalu konsisten dengan Status_Order
    terkini (R6.10). Label persentase ikut diberi warna yang sama.
--}}
@php
    $value = max(0, min(100, (float) $percent));
    $hue = (int) round($value * 1.2); // 0 = merah, 120 = hijau
    $color = "hsl({$hue}, 75%, 45%)";
    $label = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <div class="relative h-2.5 flex-1 overflow-hidden rounded-full bg-gray-200"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ (int) round($value) }}">
        <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-300"
            style="width: {{ $value }}%; background-color: {{ $color }};"></div>
    </div>
    <span class="w-10 shrink-0 text-right text-xs font-semibold tabular-nums"
        style="color: {{ $color }};">
        {{ $label }}%
    </span>
</div>
