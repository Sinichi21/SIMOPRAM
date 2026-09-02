<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Gugus Depan
        </h1>

        <p class="text-sm text-zinc-500">
            Kelola profil Gugus Depan sekolah.
        </p>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('gudep.manage')): ?>

        <form
            wire:submit="save"
            class="rounded-xl border bg-white p-6 shadow-sm dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                <?php echo e($editingId ? 'Edit Gugus Depan' : 'Tambah Gugus Depan'); ?>

            </h2>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nama Gugus Depan
                    </label>

                    <input
                        wire:model="name"
                        class="w-full rounded-lg border px-3 py-2"
                        placeholder="Gugus Depan SD Negeri ..."
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tanggal Peresmian
                    </label>

                    <input
                        type="date"
                        wire:model="inauguration_date"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nomor Gudep Putra
                    </label>

                    <input
                        wire:model="male_number"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nomor Gudep Putri
                    </label>

                    <input
                        wire:model="female_number"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kwarran
                    </label>

                    <input
                        wire:model="kwarran"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kwarcab
                    </label>

                    <input
                        wire:model="kwarcab"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kwarda
                    </label>

                    <input
                        wire:model="kwarda"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kamabigus
                    </label>

                    <input
                        wire:model="kamabigus_name"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Pembina / Ketua Gudep
                    </label>

                    <input
                        wire:model="head_coach_name"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                </div>

            </div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium">
                    Alamat Sekretariat
                </label>

                <textarea
                    wire:model="secretariat_address"
                    rows="3"
                    class="w-full rounded-lg border px-3 py-2"
                ></textarea>
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium">
                    Deskripsi
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg border px-3 py-2"
                ></textarea>
            </div>

            <label class="mt-4 flex items-center gap-2">
                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                Gugus Depan aktif
            </label>

            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-white"
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

        <h2 class="mb-4 text-lg font-semibold">
            Data Gugus Depan
        </h2>

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>
                    <tr class="border-b">
                        <th class="p-3">Nama</th>
                        <th class="p-3">Putra</th>
                        <th class="p-3">Putri</th>
                        <th class="p-3">Kwarran</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $scoutGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gudep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'gudep-'.e($gudep->id).''; ?>wire:key="gudep-<?php echo e($gudep->id); ?>"
                            class="border-b"
                        >

                            <td class="p-3">
                                <?php echo e($gudep->name); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($gudep->male_number ?: '-'); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($gudep->female_number ?: '-'); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($gudep->kwarran ?: '-'); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($gudep->is_active ? 'Aktif' : 'Nonaktif'); ?>

                            </td>

                            <td class="p-3">

                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('gudep.manage')): ?>

                                    <button
                                        wire:click="edit(<?php echo e($gudep->id); ?>)"
                                        class="mr-2 rounded border px-3 py-1"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="toggleStatus(<?php echo e($gudep->id); ?>)"
                                        class="rounded border px-3 py-1"
                                    >
                                        <?php echo e($gudep->is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'); ?>

                                    </button>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                        <tr>
                            <td
                                colspan="6"
                                class="p-6 text-center text-zinc-500"
                            >
                                Belum ada data Gugus Depan.
                            </td>
                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            <?php echo e($scoutGroups->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\scout-groups\index.blade.php ENDPATH**/ ?>