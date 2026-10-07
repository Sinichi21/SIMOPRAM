{{-- v2.7.6.1: isolated A4 preview to avoid Blade directive nesting/parser conflicts. --}}
<?php
                            $previewAddress = trim((string) ($previewScoutGroup?->secretariat_address ?: $previewSchool?->address));
                            $previewPostal = trim((string) ($previewSchool?->postal_code ?? ''));

                            if (
                                $previewPostal !== ''
                                && ! preg_match('/(?:^|\D)'.preg_quote($previewPostal, '/').'(?:\D|$)/u', $previewAddress)
                            ) {
                                $previewAddress = trim($previewAddress.' '.$previewPostal);
                            }

                            $previewAttachmentCount = count($attachments);
                            if ($editingId) {
                                $previewAttachmentCount += \App\Models\LetterAttachment::query()
                                    ->where('letter_id', $editingId)
                                    ->count();
                            }

                            $previewRecipientLocation = trim((string) ($templateData['recipient_location'] ?? ''));
                            $previewRecipientLocation = $previewRecipientLocation !== ''
                                ? $previewRecipientLocation
                                : 'Tempat';
                        ?>

                        <div
                            class="md:col-span-2 rounded-xl border border-zinc-300 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-950"
                            x-data="{
                                previewMode: 'edit',
                                overflow: false,
                                checkOverflow() {
                                    this.$nextTick(() => {
                                        if (! this.$refs.a4page) {
                                            this.overflow = false;
                                            return;
                                        }

                                        this.overflow = this.$refs.a4page.scrollHeight > this.$refs.a4page.clientHeight + 2;
                                    });
                                }
                            }"
                            x-init="checkOverflow()"
                        >
                            <style>
                                .letter-real-preview {
                                    font-family: "Times New Roman", Times, serif;
                                    font-size: 12pt;
                                    line-height: 1.35;
                                }

                                .letter-real-preview p,
                                .letter-real-preview div { margin: 0 0 2.6mm; }

                                .letter-real-preview ul,
                                .letter-real-preview ol {
                                    margin: 0 0 2.6mm 7mm;
                                    padding-left: 5mm;
                                }

                                .letter-real-preview table {
                                    border-collapse: collapse;
                                    margin: 1.5mm 0 3mm;
                                    max-width: 100%;
                                }

                                .letter-real-preview td,
                                .letter-real-preview th {
                                    padding: 1.3mm 1.8mm;
                                }

                                .letter-real-preview td:not([style]),
                                .letter-real-preview th:not([style]) {
                                    border: .6px solid #555;
                                }

                                .letter-real-preview blockquote {
                                    border-left: 1.5px solid #777;
                                    margin: 2mm 0 2.6mm 6mm;
                                    padding-left: 4mm;
                                }

                                .letter-real-preview hr {
                                    border: 0;
                                    border-top: .7px solid #777;
                                    margin: 3mm 0;
                                }
                            </style>

                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Preview Surat Sebelum Terbit</div>
                                    <div class="mt-1 text-sm font-medium">{{ $selectedTemplate->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">
                                        Preview tidak mengambil nomor urut. Nomor resmi baru dialokasikan setelah validasi saat surat diterbitkan.
                                    </div>
                                </div>

                                <div class="inline-flex rounded-lg border border-zinc-300 p-1 dark:border-zinc-700">
                                    <button
                                        type="button"
                                        x-on:click="previewMode = 'edit'; overflow = false"
                                        x-bind:class="previewMode === 'edit' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 dark:text-zinc-300'"
                                        class="rounded-md px-3 py-1.5 text-xs font-medium"
                                    >
                                        Edit Mode
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="previewMode = 'real'; checkOverflow()"
                                        x-bind:class="previewMode === 'real' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 dark:text-zinc-300'"
                                        class="rounded-md px-3 py-1.5 text-xs font-medium"
                                    >
                                        Real A4
                                    </button>
                                </div>
                            </div>

                            <div x-show="previewMode === 'edit'">
                                <div
                                    class="letter-preview text-zinc-800 dark:text-zinc-200"
                                    style="font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.35;"
                                >{!! $previewBody !!}</div>

                                <p class="mt-4 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
                                    Edit Mode memperbesar isi agar mudah dibaca. Gunakan Real A4 untuk mengecek posisi kop, isi, ruang tanda tangan, dan footer verifikasi.
                                </p>
                            </div>

                            <div x-show="previewMode === 'real'" x-on:resize.window="checkOverflow()" class="overflow-x-auto pb-3">
                                <div class="mx-auto w-max">
                                    <div
                                        x-ref="a4page"
                                        class="relative overflow-hidden bg-white text-black shadow-xl ring-1 ring-zinc-300"
                                        style="width: 210mm; height: 297mm; padding: 12mm 16mm 27mm; zoom: .72;"
                                    >
                                        <header
                                            style="position: relative; min-height: 22mm; border-bottom: 1.6px solid #000; margin-bottom: 4mm; padding: 0 2mm 2.5mm; text-align: center;"
                                        >
                                            <img
                                                src="{{ asset('images/reports/logo-pramuka.png') }}"
                                                alt="Logo Gerakan Pramuka"
                                                style="position: absolute; top: 0; left: 1mm; height: 21mm; max-width: 23mm; object-fit: contain;"
                                            >
                                            <img
                                                src="{{ asset('images/reports/logo-wosm.png') }}"
                                                alt="Logo WOSM"
                                                style="position: absolute; top: 0; right: 1mm; height: 21mm; max-width: 23mm; object-fit: contain;"
                                            >

                                            <div style="margin: 0 23mm; font-size: 14pt; font-weight: bold; line-height: 1.05;">
                                                {{ $previewAdministrationProfile?->letterhead_title ?: 'GERAKAN PRAMUKA' }}<br>
                                                @if (filled($previewAdministrationProfile?->letterhead_subtitle))
                                                    {{ $previewAdministrationProfile->letterhead_subtitle }}<br>
                                                @else
                                                    @if ($administration_type === 'male')
                                                        @if ($previewDocumentSetting?->gudep_male_number)
                                                            GUGUSDEPAN {{ strtoupper($previewSchool?->city ?: '') }} {{ $previewDocumentSetting->gudep_male_number }}<br>
                                                        @endif
                                                    @elseif ($administration_type === 'female')
                                                        @if ($previewDocumentSetting?->gudep_female_number)
                                                            GUGUSDEPAN {{ strtoupper($previewSchool?->city ?: '') }} {{ $previewDocumentSetting->gudep_female_number }}<br>
                                                        @endif
                                                    @else
                                                        @if ($previewDocumentSetting?->gudep_male_number)
                                                            GUGUSDEPAN {{ strtoupper($previewSchool?->city ?: '') }} {{ $previewDocumentSetting->gudep_male_number }}<br>
                                                        @endif
                                                        @if ($previewDocumentSetting?->gudep_female_number)
                                                            GUGUSDEPAN {{ strtoupper($previewSchool?->city ?: '') }} {{ $previewDocumentSetting->gudep_female_number }}<br>
                                                        @endif
                                                    @endif
                                                    PANGKALAN {{ strtoupper($previewSchool?->name ?: 'SEKOLAH') }}
                                                @endif
                                            </div>

                                            <div style="margin: .8mm 23mm 0; font-size: 10pt; line-height: 1.1;">
                                                {{ $previewAdministrationProfile?->letterhead_address ?: $previewAddress }}
                                            </div>
                                        </header>

                                        <?php if ($previewFlexibleLayout): ?>
                                            <div class="letter-real-preview">{!! $previewBody !!}</div>
                                        <?php else: ?>
                                            <table style="width: 100%; border-collapse: collapse; margin: 0 0 3mm; font-family: 'Times New Roman', Times, serif; font-size: 12pt;">
                                                <tr><td style="width: 22mm; padding: .25mm 0;">Nomor</td><td style="width: 4mm; text-align: center;">:</td><td>{{ $letter_number ?: '(nomor otomatis saat terbit)' }}</td></tr>
                                                <tr><td style="padding: .25mm 0;">Lampiran</td><td style="text-align: center;">:</td><td>{{ $previewAttachmentCount > 0 ? $previewAttachmentCount.' berkas' : '-' }}</td></tr>
                                                <tr><td style="padding: .25mm 0;">Perihal</td><td style="text-align: center;">:</td><td><strong>{{ $subject }}</strong></td></tr>
                                            </table>

                                            <?php if (filled($recipient)): ?>
                                                <div style="margin: 2mm 0 3mm; font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.25;">
                                                    Yth. {{ $recipient }}<br>
                                                    di {{ $previewRecipientLocation }}
                                                </div>
                                            <?php endif; ?>

                                            <div class="letter-real-preview">{!! $previewBody !!}</div>

                                            <div style="width: 48%; margin: 6mm 0 0 auto; page-break-inside: avoid; text-align: center; font-family: 'Times New Roman', Times, serif; font-size: 12pt;">
                                                {{ $signatory_position ?: 'Pembina Gugusdepan' }},
                                                <div style="height: 14mm;"></div>
                                                <div style="font-weight: bold; text-decoration: underline;">{{ $signatory_name ?: '........................' }}</div>
                                                <?php if ($signatory_identity): ?>
                                                    <div>{{ $signatory_identity }}</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div
                                            style="position: absolute; left: 16mm; right: 16mm; bottom: 6mm; height: 17mm; border-top: .6px solid #888; padding-top: 2mm; color: #333; font-family: 'Times New Roman', Times, serif; font-size: 8pt;"
                                        >
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <tr>
                                                    <td style="width: 22mm; padding: 0; vertical-align: middle; border: 0;">
                                                        <div style="height: 18mm; width: 18mm; border: 1px dashed #888; display: flex; align-items: center; justify-content: center; font-size: 6.5pt; text-align: center;">
                                                            QR<br>setelah<br>terbit
                                                        </div>
                                                    </td>
                                                    <td style="padding: 0; vertical-align: middle; border: 0;">
                                                        <strong>Verifikasi Dokumen SIMPRAM</strong><br>
                                                        <span style="font-size: 7.5pt; line-height: 1.15;">
                                                            QR dan kode verifikasi dibuat saat dokumen resmi diterbitkan.
                                                        </span><br>
                                                        <span style="font-size: 7.5pt;">Kode: PRATINJAU — belum diterbitkan</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    <div
                                        x-show="overflow"
                                        class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                                    >
                                        Konten terdeteksi melebihi tinggi satu halaman A4. Rapikan template/spacing sebelum menerbitkan, atau pastikan memang diinginkan menjadi lebih dari satu halaman.
                                    </div>
                                </div>

                                <p class="mt-4 text-xs text-zinc-500">
                                    Real A4 memakai ukuran 210 × 297 mm, margin PDF surat 12 mm / 16 mm / 27 mm, Times New Roman 12 pt, kop 14 pt, alamat 10 pt, serta ruang footer verifikasi. Nomor dan QR masih placeholder sehingga tidak menghabiskan nomor surat.
                                </p>
                            </div>
                        </div>
