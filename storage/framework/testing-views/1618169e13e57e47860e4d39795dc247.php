<div class="space-y-6">

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700"
        >
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    <div>
        <h1 class="text-2xl font-semibold">
            Agenda / Kegiatan
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Kelola agenda kegiatan Pramuka
            pada sekolah aktif.
        </p>
    </div>


    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
        'activities.create',
        'activities.update'
    ])): ?>

        <form
            wire:submit="save"
            class="rounded-xl border
                   border-zinc-200 bg-white
                   p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                <?php echo e($editingId
                    ? 'Edit Agenda'
                    : 'Tambah Agenda'); ?>

            </h2>


            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Judul Kegiatan
                    </label>

                    <input
                        type="text"
                        wire:model="title"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-500">
                            <?php echo e($message); ?>

                        </p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Jenis Kegiatan
                    </label>

                    <select
                        wire:model="activity_type"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="regular">
                            Latihan Rutin
                        </option>

                        <option value="training">
                            Pelatihan
                        </option>

                        <option value="ceremony">
                            Upacara
                        </option>

                        <option value="camp">
                            Perkemahan
                        </option>

                        <option value="competition">
                            Lomba
                        </option>

                        <option value="service">
                            Bakti Sosial
                        </option>

                        <option value="other">
                            Lainnya
                        </option>
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model.live="academic_year_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $academicYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($year->id); ?>">
                                <?php echo e($year->name); ?>

                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Semester
                    </label>

                    <select
                        wire:model="semester_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $semesters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $semester): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($semester->id); ?>">
                                <?php echo e($semester->name); ?>

                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Mulai
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="start_at"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Selesai
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="end_at"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Lokasi
                    </label>

                    <input
                        type="text"
                        wire:model="location"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="draft">
                            Draft
                        </option>

                        <option value="published">
                            Dipublikasikan
                        </option>

                        <option value="completed">
                            Selesai
                        </option>

                        <option value="cancelled">
                            Dibatalkan
                        </option>
                    </select>
                </div>

            </div>


            <div class="mt-4">

                <label class="mb-1 block text-sm font-medium">
                    Deskripsi
                </label>

                <textarea
                    wire:model="description"
                    rows="4"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            <div class="mt-4">

                <label class="mb-2 block text-sm font-medium">
                    Pembina
                </label>

                <div class="grid gap-2 md:grid-cols-3">

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $coaches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coach): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <label
                            class="flex items-center gap-2
                                   rounded-lg border
                                   border-zinc-200 p-3
                                   dark:border-zinc-700"
                        >

                            <input
                                type="checkbox"
                                wire:model="coach_ids"
                                value="<?php echo e($coach->id); ?>"
                            >

                            <span class="text-sm">
                                <?php echo e($coach->name); ?>

                            </span>

                        </label>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                </div>

            </div>


            <label class="mt-5 flex items-center gap-2">

                <input
                    type="checkbox"
                    wire:model="is_public"
                >

                <span class="text-sm">
                    Tampilkan agenda ini pada halaman publik
                </span>

            </label>


            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white dark:bg-white
                           dark:text-zinc-900"
                >
                    <?php echo e($editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah Agenda'); ?>

                </button>


                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               border-zinc-300
                               px-4 py-2 text-sm"
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
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 flex flex-col gap-3
                   md:flex-row md:justify-between"
        >

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari agenda..."
                class="rounded-lg border
                       border-zinc-300 px-3 py-2"
            >

            <select
                wire:model.live="filterStatus"
                class="rounded-lg border
                       border-zinc-300 px-3 py-2"
            >
                <option value="">
                    Semua Status
                </option>

                <option value="draft">
                    Draft
                </option>

                <option value="published">
                    Dipublikasikan
                </option>

                <option value="completed">
                    Selesai
                </option>

                <option value="cancelled">
                    Dibatalkan
                </option>
            </select>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>
                    <tr class="border-b text-zinc-500">
                        <th class="p-3">Agenda</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Lokasi</th>
                        <th class="p-3">Pembina</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'activity-'.e($activity->id).''; ?>wire:key="activity-<?php echo e($activity->id); ?>"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">
                                <div class="font-medium">
                                    <?php echo e($activity->title); ?>

                                </div>

                                <div class="text-xs text-zinc-500">
                                    <?php echo e($activity->academicYear?->name); ?>

                                </div>
                            </td>


                            <td class="p-3">
                                <?php echo e($activity->start_at->format('d-m-Y H:i')); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($activity->location ?: '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($activity->coaches
                                    ->pluck('name')
                                    ->join(', ')
                                    ?: '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e(ucfirst($activity->status)); ?>

                            </td>


                            <td class="p-3">

                                <div class="flex gap-2">

                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activities.update')): ?>

                                        <button
                                            type="button"
                                            wire:click="edit(<?php echo e($activity->id); ?>)"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5"
                                        >
                                            Edit
                                        </button>

                                    <?php endif; ?>


                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activities.cancel')): ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
                                            $activity->status !==
                                            'cancelled'
                                        ): ?>

                                            <button
                                                type="button"
                                                wire:click="
                                                    cancelActivity(
                                                        <?php echo e($activity->id); ?>

                                                    )
                                                "
                                                wire:confirm="
                                                    Batalkan agenda ini?
                                                "
                                                class="rounded-lg
                                                       border
                                                       border-red-300
                                                       px-3 py-1.5
                                                       text-red-600"
                                            >
                                                Batalkan
                                            </button>

                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                        <tr>
                            <td
                                colspan="6"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada agenda kegiatan.
                            </td>
                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            <?php echo e($activities->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\activities\index.blade.php ENDPATH**/ ?>