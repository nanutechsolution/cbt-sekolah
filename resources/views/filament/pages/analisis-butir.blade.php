<x-filament-panels::page>
    @php
        $jadwal = $this->getJadwalTerpilih();
        $hasil = $this->getHasilAnalisis();
        $opsiJadwal = $this->getJadwalOptions();
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="w-full md:max-w-xl">
                    <label for="jadwal_ujian_id" class="mb-2 block text-sm font-semibold text-gray-950 dark:text-white">
                        Pilih Jadwal Ujian
                    </label>
                    <select
                        id="jadwal_ujian_id"
                        wire:model.live="jadwal_ujian_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">— Pilih jadwal —</option>
                        @foreach ($opsiJadwal as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Analisis dihitung dari peserta yang sudah menyelesaikan ujian.
                    </p>
                </div>

                @if ($jadwal)
                    <a href="{{ route('analisis.export', $jadwal) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                        Download Excel
                    </a>
                @endif
            </div>
        </div>

        @if (! $jadwal)
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-white/10 dark:bg-gray-900">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Pilih jadwal untuk memulai analisis</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tingkat kesukaran, daya beda, dan efektivitas pengecoh akan ditampilkan per butir soal.
                </p>
            </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $jadwal->paketSoal?->nama ?: $jadwal->nama }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $jadwal->paketSoal?->mapel?->nama ?: '-' }} ·
                            {{ $jadwal->paketSoal?->jenisUjian?->nama ?: 'Ujian' }} ·
                            {{ $jadwal->mulai?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                        {{ $hasil['ringkasan']['jumlah_selesai'] }} peserta selesai
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Rata-rata Nilai', $hasil['ringkasan']['rata_rata']],
                        ['Nilai Tertinggi', $hasil['ringkasan']['tertinggi']],
                        ['Nilai Terendah', $hasil['ringkasan']['terendah']],
                        ['Jumlah Butir', count($hasil['butir'])],
                    ] as [$label, $value])
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</div>
                            <div class="mt-1 text-xl font-bold text-gray-950 dark:text-white">
                                {{ is_numeric($value) ? number_format((float) $value, 2) : ($value ?? '-') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @forelse ($hasil['butir'] as $butir)
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex flex-col gap-3 border-b border-gray-200 p-5 dark:border-white/10 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-gray-700 dark:bg-white/10 dark:text-gray-200">Soal {{ $butir['nomor'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $butir['tipe_label'] }}</span>
                            </div>
                            <p class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $butir['pertanyaan']), 220) }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $butir['kategori_kesukaran'] === 'Mudah' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : ($butir['kategori_kesukaran'] === 'Sulit' ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400') }}">
                                {{ $butir['kategori_kesukaran'] }}
                            </span>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                Daya beda: {{ $butir['kategori_daya_beda'] }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tingkat Kesukaran</div>
                            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ $butir['tingkat_kesukaran'] !== null ? number_format((float) $butir['tingkat_kesukaran'], 3) : '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $butir['kategori_kesukaran'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Daya Beda</div>
                            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ $butir['daya_beda'] !== null ? number_format((float) $butir['daya_beda'], 3) : '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $butir['kategori_daya_beda'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Menjawab</div>
                            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ $butir['jumlah_menjawab'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $butir['jumlah_benar'] !== null ? $butir['jumlah_benar'].' benar' : 'Soal non-PG' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bobot</div>
                            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ number_format((float) $butir['bobot'], 2) }}</div>
                        </div>
                    </div>

                    @if ($butir['opsi'] !== [])
                        <div class="border-t border-gray-200 p-5 dark:border-white/10">
                            <h3 class="mb-3 text-sm font-bold text-gray-950 dark:text-white">Efektivitas Pengecoh</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                                            <th class="px-2 py-2 font-semibold">Opsi</th>
                                            <th class="px-2 py-2 font-semibold">Teks</th>
                                            <th class="px-2 py-2 text-center font-semibold">Dipilih</th>
                                            <th class="px-2 py-2 text-center font-semibold">%</th>
                                            <th class="px-2 py-2 text-center font-semibold">Atas</th>
                                            <th class="px-2 py-2 text-center font-semibold">Bawah</th>
                                            <th class="px-2 py-2 text-center font-semibold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($butir['opsi'] as $opsi)
                                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                                <td class="px-2 py-2 font-bold text-gray-950 dark:text-white">{{ $opsi['label'] }}</td>
                                                <td class="px-2 py-2 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit(strip_tags((string) $opsi['teks']), 80) }}</td>
                                                <td class="px-2 py-2 text-center text-gray-700 dark:text-gray-300">{{ $opsi['dipilih'] }}</td>
                                                <td class="px-2 py-2 text-center text-gray-700 dark:text-gray-300">{{ number_format((float) $opsi['persentase'], 2) }}%</td>
                                                <td class="px-2 py-2 text-center text-gray-700 dark:text-gray-300">{{ $opsi['atas'] }}</td>
                                                <td class="px-2 py-2 text-center text-gray-700 dark:text-gray-300">{{ $opsi['bawah'] }}</td>
                                                <td class="px-2 py-2 text-center">
                                                    @if ($opsi['is_benar'])
                                                        <span class="rounded-full bg-success-50 px-2 py-1 font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-400">Kunci</span>
                                                    @elseif ($opsi['efektif'])
                                                        <span class="rounded-full bg-primary-50 px-2 py-1 font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">Efektif</span>
                                                    @else
                                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">Kurang</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400">
                    Paket soal ini belum memiliki butir soal.
                </div>
            @endforelse
        @endif
    </div>
</x-filament-panels::page>
