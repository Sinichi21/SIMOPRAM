<div class="space-y-6">

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    <div>
        <h1 class="text-2xl font-semibold">
            Absensi
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            <?php echo e($activity->title); ?>

        </p>
    </div>


    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('attendance_sessions.manage')): ?>

        <form
            wire:submit="saveSession"
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800 dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                <?php echo e($editingSessionId
                    ? 'Edit Sesi Absensi'
                    : 'Buat Sesi Absensi'); ?>

            </h2>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nama Sesi
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Peserta
                    </label>

                    <select
                        wire:model.live="participant_scope"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="all">
                            Semua Siswa Aktif
                        </option>

                        <option value="classroom">
                            Berdasarkan Kelas
                        </option>

                        <option value="scout_unit">
                            Berdasarkan Regu / Barung
                        </option>
                    </select>
                </div>


                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($participant_scope === 'classroom'): ?>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Kelas
                        </label>

                        <select
                            wire:model="participant_scope_id"
                            class="w-full rounded-lg border
                                   border-zinc-300 px-3 py-2"
                        >
                            <option value="">
                                -- Pilih Kelas --
                            </option>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $classrooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $classroom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($classroom->id); ?>">
                                    <?php echo e($classroom->name); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                <?php elseif($participant_scope === 'scout_unit'): ?>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Regu / Barung
                        </label>

                        <select
                            wire:model="participant_scope_id"
                            class="w-full rounded-lg border
                                   border-zinc-300 px-3 py-2"
                        >
                            <option value="">
                                -- Pilih --
                            </option>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $scoutUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($unit->id); ?>">
                                    <?php echo e($unit->name); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Dibuka
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="open_at"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Batas Terlambat
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="late_after"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Ditutup
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="close_at"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2"
                    >
                </div>

            </div>


            <div class="mt-5 flex flex-wrap gap-5">

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        wire:model="allow_manual"
                    >

                    <span class="text-sm">
                        Absensi Manual
                    </span>
                </label>

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        wire:model.live="allow_self_checkin"
                    >

                    <span class="text-sm">
                        Absensi Mandiri GPS
                    </span>
                </label>

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        wire:model="is_active"
                    >

                    <span class="text-sm">
                        Sesi Aktif
                    </span>
                </label>

            </div>


            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($allow_self_checkin): ?>

                <div
                    class="mt-6 grid gap-4
                           border-t border-zinc-200
                           pt-6 md:grid-cols-4"
                >

                    <div>
                        <label class="mb-1 block text-sm">
                            Latitude
                        </label>

                        <input
                            wire:model="latitude"
                            type="number"
                            step="0.0000001"
                            class="w-full rounded-lg border
                                   border-zinc-300 px-3 py-2"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm">
                            Longitude
                        </label>

                        <input
                            wire:model="longitude"
                            type="number"
                            step="0.0000001"
                            class="w-full rounded-lg border
                                   border-zinc-300 px-3 py-2"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm">
                            Radius (meter)
                        </label>

                        <input
                            wire:model="radius_m"
                            type="number"
                            class="w-full rounded-lg border
                                   border-zinc-300 px-3 py-2"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm">
                            Akurasi Maks. GPS
                        </label>

                        <input
                            wire:model="max_accuracy_m"
                            type="number"
                            class="w-full rounded-lg border
                                   border-zinc-300 px-3 py-2"
                        >
                    </div>

                </div>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white dark:bg-white
                           dark:text-zinc-900"
                >
                    <?php echo e($editingSessionId
                        ? 'Simpan Perubahan'
                        : 'Buat Sesi'); ?>

                </button>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingSessionId): ?>

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               border-zinc-300 px-4 py-2"
                    >
                        Batal
                    </button>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>

        </form>

    <?php endif; ?>


    
    <div
        class="rounded-xl border border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800 dark:bg-zinc-900"
    >

        <h2 class="mb-4 text-lg font-semibold">
            Sesi Absensi
        </h2>

        <div class="space-y-3">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                <div
                    class="flex flex-col gap-3
                           rounded-lg border border-zinc-200
                           p-4 md:flex-row
                           md:items-center md:justify-between"
                >

                    <div>
                        <div class="font-semibold">
                            <?php echo e($session->name); ?>

                        </div>

                        <div class="mt-1 text-sm text-zinc-500">
                            <?php echo e($session->open_at->format('d-m-Y H:i')); ?>

                            -
                            <?php echo e($session->close_at->format('H:i')); ?>


                            ·

                            <?php echo e($session->participants_count); ?>

                            peserta
                        </div>
                    </div>


                    <div class="flex gap-2">

                        <button
                            type="button"
                            wire:click="selectSession(<?php echo e($session->id); ?>)"
                            class="rounded-lg bg-zinc-900
                                   px-3 py-2 text-sm text-white"
                        >
                            Kelola Kehadiran
                        </button>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('attendance_sessions.manage')): ?>

                            <button
                                type="button"
                                wire:click="editSession(<?php echo e($session->id); ?>)"
                                class="rounded-lg border
                                       border-zinc-300 px-3 py-2 text-sm"
                            >
                                Edit
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                <p class="text-sm text-zinc-500">
                    Belum ada sesi absensi.
                </p>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        </div>

    </div>


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedSession): ?>

        <div
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800 dark:bg-zinc-900"
        >

            <div class="mb-5">
                <h2 class="text-lg font-semibold">
                    Kehadiran:
                    <?php echo e($selectedSession->name); ?>

                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Klik status untuk mencatat
                    atau memperbarui kehadiran.
                </p>
            </div>


            <input
                type="search"
                wire:model.live.debounce.300ms="participantSearch"
                placeholder="Cari siswa..."
                class="mb-4 rounded-lg border
                       border-zinc-300 px-3 py-2"
            >


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead>
                        <tr class="border-b text-zinc-500">
                            <th class="p-3">Siswa</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $participants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $participant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <?php
                                $attendance =
                                    $attendanceByStudent->get(
                                        $participant->student_id
                                    );
                            ?>

                            <tr class="border-b">

                                <td class="p-3">
                                    <div class="font-medium">
                                        <?php echo e($participant->student->name); ?>

                                    </div>

                                    <div class="text-xs text-zinc-500">
                                        NIS:
                                        <?php echo e($participant->student->nis); ?>

                                    </div>
                                </td>


                                <td class="p-3">
                                    <?php echo e($attendance?->status ?? '-'); ?>

                                </td>


                                <td class="p-3">
                                    <?php echo e($attendance?->checked_in_at
                                        ?->format('H:i:s')
                                        ?? '-'); ?>

                                </td>


                                <td class="p-3">

                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('attendances.manual')): ?>

                                        <div class="flex flex-wrap gap-1">

                                            <button
                                                wire:click="mark(
                                                    <?php echo e($participant->student_id); ?>,
                                                    'present'
                                                )"
                                                class="rounded border px-2 py-1"
                                            >
                                                Hadir
                                            </button>

                                            <button
                                                wire:click="mark(
                                                    <?php echo e($participant->student_id); ?>,
                                                    'late'
                                                )"
                                                class="rounded border px-2 py-1"
                                            >
                                                Terlambat
                                            </button>

                                            <button
                                                wire:click="mark(
                                                    <?php echo e($participant->student_id); ?>,
                                                    'sick'
                                                )"
                                                class="rounded border px-2 py-1"
                                            >
                                                Sakit
                                            </button>

                                            <button
                                                wire:click="mark(
                                                    <?php echo e($participant->student_id); ?>,
                                                    'excused'
                                                )"
                                                class="rounded border px-2 py-1"
                                            >
                                                Izin
                                            </button>

                                            <button
                                                wire:click="mark(
                                                    <?php echo e($participant->student_id); ?>,
                                                    'absent'
                                                )"
                                                class="rounded border px-2 py-1"
                                            >
                                                Alpa
                                            </button>

                                        </div>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\attendances\manage.blade.php ENDPATH**/ ?>