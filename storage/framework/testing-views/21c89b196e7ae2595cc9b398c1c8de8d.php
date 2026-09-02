<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Tahun Ajaran
        </h1>

        <p class="text-sm text-zinc-500">
            Kelola tahun ajaran sekolah aktif.
        </p>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('success')): ?>
        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-green-700"
        >
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('error')): ?>
        <div
            class="rounded-lg border border-red-200
                   bg-red-50 p-4 text-red-700"
        >
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('academic_years.manage')): ?>

        <form
            wire:submit="save"
            class="rounded-xl border
                   bg-white p-6 shadow-sm
                   dark:bg-zinc-900"
        >

            <div class="mb-5">
                <h2 class="text-lg font-semibold">
                    <?php echo e($editingId
                        ? 'Edit Tahun Ajaran'
                        : 'Tambah Tahun Ajaran'); ?>

                </h2>
            </div>

            <div
                class="grid gap-4
                       md:grid-cols-3"
            >

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tahun Ajaran
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="2026/2027"
                        class="w-full rounded-lg border
                               px-3 py-2
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span
                            class="text-sm text-red-500"
                        >
                            <?php echo e($message); ?>

                        </span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        wire:model="start_date"
                        class="w-full rounded-lg border
                               px-3 py-2
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span
                            class="text-sm text-red-500"
                        >
                            <?php echo e($message); ?>

                        </span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Selesai
                    </label>

                    <input
                        type="date"
                        wire:model="end_date"
                        class="w-full rounded-lg border
                               px-3 py-2
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span
                            class="text-sm text-red-500"
                        >
                            <?php echo e($message); ?>

                        </span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

            </div>

            <div class="mt-4">
                <label
                    class="flex items-center gap-2"
                >

                    <input
                        type="checkbox"
                        wire:model="is_active"
                    >

                    <span>
                        Jadikan tahun ajaran aktif
                    </span>

                </label>
            </div>

            <div
                class="mt-6 flex gap-2"
            >

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-white
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    <?php echo e($editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah'); ?>

                </button>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               px-4 py-2"
                    >
                        Batal
                    </button>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>

        </form>

    <?php endif; ?>

    <div
        class="rounded-xl border
               bg-white p-6 shadow-sm
               dark:bg-zinc-900"
    >

        <div
            class="mb-4 flex
                   items-center justify-between"
        >

            <h2 class="text-lg font-semibold">
                Daftar Tahun Ajaran
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari..."
                class="rounded-lg border
                       px-3 py-2"
            >

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>
                    <tr
                        class="border-b
                               text-sm text-zinc-500"
                    >
                        <th class="p-3">
                            Tahun Ajaran
                        </th>

                        <th class="p-3">
                            Mulai
                        </th>

                        <th class="p-3">
                            Selesai
                        </th>

                        <th class="p-3">
                            Status
                        </th>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('academic_years.manage')): ?>
                            <th class="p-3">
                                Aksi
                            </th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $academicYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $academicYear): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            class="border-b"
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'academic-year-'.e($academicYear->id).''; ?>wire:key="academic-year-<?php echo e($academicYear->id); ?>"
                        >

                            <td class="p-3 font-medium">
                                <?php echo e($academicYear->name); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($academicYear
                                        ->start_date
                                        ->format('d-m-Y')); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($academicYear
                                        ->end_date
                                        ->format('d-m-Y')); ?>

                            </td>

                            <td class="p-3">

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
                                    $academicYear
                                        ->is_active
                                ): ?>

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-3 py-1
                                               text-xs
                                               text-green-700"
                                    >
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-3 py-1
                                               text-xs"
                                    >
                                        Tidak Aktif
                                    </span>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>

                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('academic_years.manage')): ?>

                                <td class="p-3">

                                    <div class="flex gap-2">

                                        <button
                                            type="button"
                                            wire:click="edit(<?php echo e($academicYear->id); ?>)"
                                            class="rounded
                                                   border
                                                   px-3 py-1"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onclick="
                                                if (
                                                    !confirm(
                                                        'Hapus tahun ajaran ini?'
                                                    )
                                                ) {
                                                    event.stopImmediatePropagation();
                                                }
                                            "
                                            wire:click="delete(<?php echo e($academicYear->id); ?>)"
                                            class="rounded
                                                   border
                                                   px-3 py-1
                                                   text-red-600"
                                        >
                                            Hapus
                                        </button>

                                    </div>

                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                        <tr>
                            <td
                                colspan="5"
                                class="p-6
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada tahun ajaran.
                            </td>
                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            <?php echo e($academicYears->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\academic-years\index.blade.php ENDPATH**/ ?>