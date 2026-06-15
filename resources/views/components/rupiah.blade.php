@props([
    // Nilai nominal (int/float) atau null bila belum tersedia.
    'value' => null,
    // Teks yang ditampilkan saat nilai null.
    'unavailable' => 'tidak tersedia',
])

@if (is_null($value))
    <span class="text-gray-400 italic">{{ $unavailable }}</span>
@else
    {{ ($value < 0 ? '-' : '') . 'Rp ' . number_format(abs($value), 0, ',', '.') }}
@endif
