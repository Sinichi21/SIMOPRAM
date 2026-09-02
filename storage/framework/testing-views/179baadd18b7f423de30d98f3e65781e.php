<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Data Kelas
        </h1>

        <p class="text-sm text-zinc-500">
            Kelola kelas pada sekolah aktif.
        </p>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('classrooms.manage')): ?>

        <form
            wire:submit="save"
            class="rounded-xl border bg-white p-6 shadow-sm dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                <?php echo e($editingId ? 'Edit Kelas' : 'Tambah Kelas'); ?>

            </h2>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nama Kelas
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Contoh: VI A"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="text-sm text-red-500">
                            <?php echo e($message); ?>

                        </span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tingkat
                    </label>

                    <input
                        type="number"
                        wire:model="grade"
                        min="1"
                        max="12"
                        placeholder="Contoh: 6"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['grade'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="text-sm text-red-500">
                            <?php echo e($message); ?>

                        </span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

            </div>

            <div class="mt-4">

                <label class="mb-1 block text-sm font-medium">
                    Keterangan
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                ></textarea>

            </div>

            <label class="mt-4 flex items-center gap-2">
                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                Kelas aktif
            </label>

            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-white dark:bg-white dark:text-zinc-900"
                >
                    <?php echo e($editingId ? 'Simpan Perubahan' : 'Tambah'); ?>

                </button>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>
                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border px-4 py-2"
                    >
                        Batal
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>

        </form>

    <?php endif; ?>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-zinc-900">

        <div class="mb-4 flex items-center justify-between">

            <h2 class="text-lg font-semibold">
                Daftar Kelas
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kelas..."
                class="rounded-lg border px-3 py-2"
            >

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>
                    <tr class="border-b text-sm text-zinc-500">
                        <th class="p-3">Kelas</th>
                        <th class="p-3">Tingkat</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $classrooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $classroom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            class="border-b"
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'classroom-'.e($classroom->id).''; ?>wire:key="classroom-<?php echo e($classroom->id); ?>"
                        >

                            <td class="p-3 font-medium">
                                <?php echo e($classroom->name); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($classroom->grade ?? '-'); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($classroom->is_active ? 'Aktif' : 'Nonaktif'); ?>

                            </td>

                            <td class="p-3">

                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('classrooms.manage')): ?>

                                    <button
                                        wire:click="edit(<?php echo e($classroom->id); ?>)"
                                        class="mr-2 rounded border px-3 py-1"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="toggleStatus(<?php echo e($classroom->id); ?>)"
                                        class="rounded border px-3 py-1"
                                    >
                                        <?php echo e($classroom->is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'); ?>

                                    </button>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                        <tr>
                            <td
                                colspan="4"
                                class="p-6 text-center text-zinc-500"
                            >
                                Belum ada kelas.
                            </td>
                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            <?php echo e($classrooms->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\classrooms\index.blade.php ENDPATH**/ ?>