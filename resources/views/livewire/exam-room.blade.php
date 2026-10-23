@php
    $soal = $soalList[$nomorAktif - 1] ?? null;
    $terisi = $soal !== null && ($soal['tipe'] === 'urutan'
        ? (bool) ($urutanTersentuh[$soal['id']] ?? false)
        : ! blank($jawabanState[$soal['id']] ?? null));
@endphp

<div
    class="exam-shell"
    wire:poll.15s="syncTimer"
    x-data="{
        remaining: {{ (int) $sisaDetik }},
        submitted: false,
        lastViolation: 0,
        fontScale: Math.min(1.15, Math.max(0.9, Number(localStorage.getItem('exam-font-scale') || 1) || 1)),
        tick() {
            if (this.remaining > 0) {
                this.remaining--;
                return;
            }
            if (! this.submitted) {
                this.submitted = true;
                $wire.autoSubmit();
            }
        },
        record(jenis) {
            const now = Date.now();
            if (now - this.lastViolation < 1500) return;
            this.lastViolation = now;
            $wire.recordViolation(jenis);
        },
        setFont(scale) {
            this.fontScale = scale;
            localStorage.setItem('exam-font-scale', String(scale));
        }
    }"
    x-init="setInterval(() => tick(), 1000)"
    x-on:timer-sync.window="remaining = Math.max(0, Number($event.detail.seconds)); if (remaining <= 0 && ! submitted) { submitted = true; $wire.autoSubmit(); }"
    x-on:exam-expired.window="if (! submitted) { submitted = true; $wire.autoSubmit(); }"
    x-on:visibilitychange.document="if (document.hidden) record('pindah_tab')"
    x-on:blur.window="if (! document.hidden) record('alt_tab')"
    x-on:contextmenu.prevent="record('klik_kanan')"
    x-on:copy.prevent="record('copy_paste')"
    x-on:cut.prevent="record('copy_paste')"
    x-on:paste.prevent="record('copy_paste')"
    x-on:dragstart.prevent
    x-on:beforeunload.window="if (! submitted) { $event.preventDefault(); $event.returnValue = ''; }"
    x-on:keydown.window="
        const key = String($event.key || '').toLowerCase();
        if (($event.ctrlKey || $event.metaKey) && ['c', 'v', 'x', 'p', 's', 'u'].includes(key)) {
            $event.preventDefault();
            record('copy_paste');
        }
        if (key === 'f12' || key === 'printscreen' || ($event.ctrlKey && $event.shiftKey && ['i', 'j', 'c'].includes(key))) {
            $event.preventDefault();
            record('lainnya');
        }
    "
>
    <header class="exam-top">
        <div class="exam-brand">
            <div class="exam-mark">CBT</div>
            <div style="min-width:0;">
                <div class="exam-title">{{ $peserta->jadwalUjian?->paketSoal?->nama ?: $peserta->jadwalUjian?->nama }}</div>
                <div class="exam-sub">{{ $peserta->jadwalUjian?->paketSoal?->mapel?->nama ?: 'Ujian' }} · {{ $peserta->jadwalUjian?->paketSoal?->jenisUjian?->nama ?: 'CBT' }}</div>
            </div>
        </div>
        <div class="exam-clock">
            <div class="clock-box">
                <div class="clock-label">Sisa Waktu</div>
                <div class="clock-value" :class="{ low: remaining <= 300 }"
                     x-text="String(Math.floor(remaining / 60)).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0')"></div>
            </div>
            <div class="exam-user">
                <strong>{{ $peserta->siswa?->nama ?: auth('siswa')->user()->nama }}</strong><br>
                {{ $peserta->siswa?->kelas?->nama ?: auth('siswa')->user()->kelas?->nama }}
            </div>
        </div>
    </header>

    <div class="exam-body">
        <aside class="exam-nav">
            <h2>Navigasi Soal</h2>
            <p>{{ count($soalList) }} soal · jawaban tersimpan otomatis</p>

            <div class="nomor-grid">
                @foreach ($soalList as $butir)
                    @php
                        $terisi = $butir['tipe'] === 'urutan'
                            ? (bool) ($urutanTersentuh[$butir['id']] ?? false)
                            : ! blank($jawabanState[$butir['id']] ?? null);
                        $ragu = (bool) ($raguState[$butir['id']] ?? false);
                    @endphp
                    <button type="button"
                            wire:click="pindahSoal({{ $butir['nomor'] }})"
                            class="nomor-btn {{ $nomorAktif === $butir['nomor'] ? 'active' : '' }} {{ $terisi ? 'answered' : '' }} {{ $ragu ? 'ragu' : '' }}"
                            title="Soal {{ $butir['nomor'] }}{{ $ragu ? ' (ragu-ragu)' : '' }}">
                        {{ $butir['nomor'] }}
                    </button>
                @endforeach
            </div>

            <div class="legend">
                <span><i class="dot answered"></i> Sudah dijawab</span>
                <span><i class="dot ragu"></i> Ragu-ragu</span>
            </div>

            <div class="alert warning" style="margin: 16px 0 0; font-size: 11px;">
                Pelanggaran: <strong>{{ $peserta->jumlah_pelanggaran }}</strong>
                @if ($peserta->jumlah_pelanggaran > 0)
                    <br>Jangan berpindah tab atau menyalin soal.
                @endif
            </div>
        </aside>

        <main class="exam-main" :style="{ '--exam-font-scale': fontScale }">
            @if ($errorMessage)
                <div class="alert danger">{{ $errorMessage }}</div>
            @endif

            @if ($expired)
                <div class="alert warning">
                    Waktu ujian telah habis. Jawaban yang sudah tersimpan akan dinilai.
                </div>
            @endif

            @if ($soal === null)
                <div class="alert danger">Soal tidak ditemukan. Hubungi pengawas.</div>
            @else
                <div class="exam-meta">
                    <div>
                        <h1>Soal {{ $soal['nomor'] }}</h1>
                        <span>dari {{ count($soalList) }} soal</span>
                    </div>
                    <div class="exam-meta-right">
                        <div class="font-tools" aria-label="Ukuran huruf soal">
                            <button type="button" class="font-button" x-on:click="setFont(0.9)"
                                    x-bind:class="{ 'active': fontScale === 0.9 }" title="Perkecil huruf">A</button>
                            <button type="button" class="font-button" x-on:click="setFont(1)"
                                    x-bind:class="{ 'active': fontScale === 1 }" title="Ukuran normal">A+</button>
                            <button type="button" class="font-button" x-on:click="setFont(1.15)"
                                    x-bind:class="{ 'active': fontScale === 1.15 }" title="Perbesar huruf">A++</button>
                        </div>
                        <span class="badge {{ ($raguState[$soal['id']] ?? false) ? 'warning' : ($terisi ? 'success' : '') }}">
                            {{ ($raguState[$soal['id']] ?? false) ? 'Ragu-ragu' : ($terisi ? 'Sudah dijawab' : 'Belum dijawab') }}
                        </span>
                    </div>
                </div>

                @if ($soal['audio'])
                    <div class="audio-box" wire:key="audio-box-{{ $soal['id'] }}">
                        <div class="audio-heading">
                            <strong>Audio soal</strong>
                            <span>
                                Diputar {{ $audioPlayCounts[$soal['id']] ?? 0 }} kali
                                @if ($soal['audio_maks_putar'])
                                    dari maksimal {{ $soal['audio_maks_putar'] }} kali
                                @endif
                            </span>
                        </div>
                        <audio class="question-audio" controls preload="metadata"
                               controlsList="nodownload noplaybackrate"
                               wire:ignore
                               x-on:play="$wire.catatPutarAudio({{ $soal['id'] }})">
                            <source src="{{ $soal['audio'] }}">
                        </audio>
                    </div>
                @endif

                <div class="question-text">{!! $soal['pertanyaan'] !!}</div>

                @if ($soal['gambar'])
                    <img src="{{ $soal['gambar'] }}" alt="Gambar soal {{ $soal['nomor'] }}" class="question-image">
                @endif

                @if (in_array($soal['tipe'], ['pg', 'benar_salah'], true))
                    <div class="options">
                        @foreach ($soal['opsi'] as $opsi)
                            <label class="option {{ (string) ($jawabanState[$soal['id']] ?? '') === (string) $opsi['id'] ? 'selected' : '' }}"
                                   wire:key="opsi-{{ $soal['id'] }}-{{ $opsi['id'] }}">
                                <input type="radio"
                                       name="jawaban-{{ $soal['id'] }}"
                                       value="{{ $opsi['id'] }}"
                                       wire:model.live.debounce.300ms="jawabanState.{{ $soal['id'] }}"
                                       @if($expired) disabled @endif>
                                <span class="option-label">{{ $opsi['label'] }}</span>
                                <span class="option-text">
                                    {{ $opsi['teks'] }}
                                    @if ($opsi['gambar'])
                                        <img src="{{ $opsi['gambar'] }}" alt="Gambar opsi {{ $opsi['label'] }}" class="option-image">
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @elseif ($soal['tipe'] === 'pg_kompleks')
                    <p class="answer-hint">Pilih satu atau lebih jawaban yang benar.</p>
                    <div class="options">
                        @foreach ($soal['opsi'] as $opsi)
                            <label class="option {{ in_array((int) $opsi['id'], array_map('intval', (array) ($jawabanState[$soal['id']] ?? [])), true) ? 'selected' : '' }}"
                                   wire:key="opsi-{{ $soal['id'] }}-{{ $opsi['id'] }}">
                                <input type="checkbox"
                                       value="{{ $opsi['id'] }}"
                                       wire:model.live.debounce.300ms="jawabanState.{{ $soal['id'] }}"
                                       @if($expired) disabled @endif>
                                <span class="option-label">{{ $opsi['label'] }}</span>
                                <span class="option-text">
                                    {{ $opsi['teks'] }}
                                    @if ($opsi['gambar'])
                                        <img src="{{ $opsi['gambar'] }}" alt="Gambar opsi {{ $opsi['label'] }}" class="option-image">
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @elseif ($soal['tipe'] === 'isian_singkat')
                    <label class="field-label" for="isian-{{ $soal['id'] }}">Jawaban singkat</label>
                    <input id="isian-{{ $soal['id'] }}" type="text" class="text-answer"
                           wire:model.live.debounce.900ms="jawabanState.{{ $soal['id'] }}"
                           placeholder="Tulis jawaban singkat..."
                           autocomplete="off"
                           @if($expired) disabled @endif>
                @elseif ($soal['tipe'] === 'essay')
                    <label class="field-label" for="essay-{{ $soal['id'] }}">Jawaban uraian</label>
                    <textarea id="essay-{{ $soal['id'] }}"
                        wire:model.live.debounce.900ms="jawabanState.{{ $soal['id'] }}"
                        placeholder="Tulis jawaban Anda di sini..."
                        @if($expired) disabled @endif
                    ></textarea>
                @elseif ($soal['tipe'] === 'menjodohkan')
                    <p class="answer-hint">Jodohkan setiap pernyataan di sebelah kiri dengan pasangan yang tepat.</p>
                    <div class="match-list">
                        @foreach ($soal['opsi'] as $opsi)
                            <div class="match-row" wire:key="match-{{ $soal['id'] }}-{{ $opsi['id'] }}">
                                <div class="match-left">
                                    <span class="option-label">{{ $opsi['label'] }}</span>
                                    <span>{{ $opsi['teks'] }}</span>
                                </div>
                                <select class="match-select"
                                        wire:model.live.debounce.300ms="jawabanState.{{ $soal['id'] }}.{{ $opsi['id'] }}"
                                        aria-label="Pasangan untuk {{ $opsi['label'] }}"
                                        @if($expired) disabled @endif>
                                    <option value="">Pilih pasangan</option>
                                    @foreach ($soal['pilihan_pasangan'] as $pasangan)
                                        <option value="{{ $pasangan }}">{{ $pasangan }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                @elseif ($soal['tipe'] === 'benar_salah_pernyataan')
                    <p class="answer-hint">Tentukan Benar atau Salah untuk setiap pernyataan.</p>
                    <div class="statement-list">
                        @foreach ($soal['opsi'] as $opsi)
                            @php($nilaiPernyataan = $jawabanState[$soal['id']][$opsi['id']] ?? null)
                            @php($pernyataanBenar = in_array($nilaiPernyataan, [true, 1, '1'], true))
                            @php($pernyataanSalah = in_array($nilaiPernyataan, [false, 0, '0'], true))
                            <div class="statement-row" wire:key="pernyataan-{{ $soal['id'] }}-{{ $opsi['id'] }}">
                                <div class="statement-text">
                                    <span class="option-label">{{ $opsi['label'] }}</span>
                                    <span>{{ $opsi['teks'] }}</span>
                                </div>
                                <div class="statement-choices">
                                    <label class="{{ $pernyataanBenar ? 'selected' : '' }}">
                                        <input type="radio"
                                               name="pernyataan-{{ $soal['id'] }}-{{ $opsi['id'] }}"
                                               value="1"
                                               wire:model.live.debounce.300ms="jawabanState.{{ $soal['id'] }}.{{ $opsi['id'] }}"
                                               @checked($pernyataanBenar)
                                               @if($expired) disabled @endif>
                                        Benar
                                    </label>
                                    <label class="{{ $pernyataanSalah ? 'selected' : '' }}">
                                        <input type="radio"
                                               name="pernyataan-{{ $soal['id'] }}-{{ $opsi['id'] }}"
                                               value="0"
                                               wire:model.live.debounce.300ms="jawabanState.{{ $soal['id'] }}.{{ $opsi['id'] }}"
                                               @checked($pernyataanSalah)
                                               @if($expired) disabled @endif>
                                        Salah
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($soal['tipe'] === 'urutan')
                    <p class="answer-hint">Susun opsi berikut sesuai urutan yang benar menggunakan tombol naik/turun.</p>
                    @php($opsiById = collect($soal['opsi'])->keyBy('id'))
                    @php($urutanAktif = array_values((array) ($jawabanState[$soal['id']] ?? [])))
                    <div class="order-list">
                        @foreach ($urutanAktif as $indeks => $opsiId)
                            @php($opsi = $opsiById->get($opsiId))
                            @if ($opsi)
                                <div class="order-row" wire:key="urutan-{{ $soal['id'] }}-{{ $opsiId }}">
                                    <span class="order-number">{{ $indeks + 1 }}</span>
                                    <span class="order-text">
                                        <span class="option-label">{{ $opsi['label'] }}</span>
                                        {{ $opsi['teks'] }}
                                    </span>
                                    <span class="order-buttons">
                                        <button type="button" class="btn secondary small"
                                                wire:click="pindahUrutan({{ $soal['id'] }}, {{ $indeks }}, -1)"
                                                title="Naikkan"
                                                @if($indeks === 0 || $expired) disabled @endif>&uarr;</button>
                                        <button type="button" class="btn secondary small"
                                                wire:click="pindahUrutan({{ $soal['id'] }}, {{ $indeks }}, 1)"
                                                title="Turunkan"
                                                @if($indeks === count($urutanAktif) - 1 || $expired) disabled @endif>&darr;</button>
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="alert warning">Tipe soal ini belum didukung.</div>
                @endif

                <div class="exam-actions">
                    <button type="button" class="btn secondary" wire:click="soalSebelumnya"
                            @if($nomorAktif <= 1) disabled @endif>
                        &larr; Sebelumnya
                    </button>

                    <div class="exam-actions-right">
                        <span class="save-state" wire:loading wire:target="jawabanState,toggleRagu">Menyimpan...</span>
                        <span class="save-state">{{ $saveStatus }}</span>
                        <button type="button" class="btn warn" wire:click="toggleRagu({{ $soal['id'] }})">
                            {{ ($raguState[$soal['id']] ?? false) ? 'Hapus Ragu' : 'Tandai Ragu' }}
                        </button>
                        @if ($nomorAktif < count($soalList))
                            <button type="button" class="btn" wire:click="soalBerikutnya">Berikutnya &rarr;</button>
                        @else
                            <button type="button" class="btn danger"
                                    x-on:click="if (confirm('Yakin menyelesaikan ujian? Jawaban yang tersimpan akan dinilai.')) $wire.submitExam()">
                                Selesaikan Ujian
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>
