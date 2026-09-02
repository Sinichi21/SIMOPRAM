<div class="space-y-6">

    
    <div>
        <h1
            class="text-2xl font-semibold
                   text-zinc-900 dark:text-white"
        >
            Data Pembina
        </h1>

        <p
            class="mt-1 text-sm
                   text-zinc-500"
        >
            Kelola data pembina Pramuka
            pada sekolah aktif.
        </p>
    </div>


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50 p-4
                   text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            <?php echo e(session('success')); ?>

        </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
        'coaches.create',
        'coaches.update'
    ])): ?>

        <form
            wire:submit="save"
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="mb-5 flex
                       items-center justify-between"
            >

                <div>
                    <h2 class="text-lg font-semibold">
                        <?php echo e($editingId
                            ? 'Edit Pembina'
                            : 'Tambah Pembina'); ?>

                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Masukkan identitas pembina
                        Pramuka.
                    </p>
                </div>

            </div>


            <div
                class="grid gap-4
                       md:grid-cols-2"
            >

                
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nama Pembina
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Nama lengkap"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            <?php echo e($message); ?>

                        </p>
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
                        NIP / NIK
                    </label>

                    <input
                        type="text"
                        wire:model="nip"
                        placeholder="Opsional"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nip'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            <?php echo e($message); ?>

                        </p>
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
                        Jenis Kelamin
                    </label>

                    <select
                        wire:model="gender"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        <option value="L">
                            Laki-laki
                        </option>

                        <option value="P">
                            Perempuan
                        </option>
                    </select>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            <?php echo e($message); ?>

                        </p>
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
                        Nomor Telepon
                    </label>

                    <input
                        type="text"
                        wire:model="phone"
                        placeholder="08xxxxxxxxxx"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            <?php echo e($message); ?>

                        </p>
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
                        Jabatan
                    </label>

                    <input
                        type="text"
                        wire:model="position"
                        placeholder="Contoh: Pembina Putra"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['position'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            <?php echo e($message); ?>

                        </p>
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
                        Nomor Sertifikat
                    </label>

                    <input
                        type="text"
                        wire:model="certificate_number"
                        placeholder="KMD / KML / lainnya"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['certificate_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            <?php echo e($message); ?>

                        </p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

            </div>


            <label
                class="mt-5 flex
                       items-center gap-2"
            >
                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                <span class="text-sm">
                    Pembina aktif
                </span>
            </label>


            <div
                class="mt-6 flex gap-2"
            >

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="rounded-lg
                           bg-zinc-900
                           px-4 py-2
                           text-sm font-medium
                           text-white
                           disabled:opacity-50
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    <span
                        wire:loading.remove
                        wire:target="save"
                    >
                        <?php echo e($editingId
                            ? 'Simpan Perubahan'
                            : 'Tambah Pembina'); ?>

                    </span>

                    <span
                        wire:loading
                        wire:target="save"
                    >
                        Menyimpan...
                    </span>
                </button>


                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg
                               border
                               border-zinc-300
                               px-4 py-2
                               text-sm
                               dark:border-zinc-700"
                    >
                        Batal
                    </button>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>

        </form>

    <?php endif; ?>


    
    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 flex
                   flex-col gap-3
                   md:flex-row
                   md:items-center
                   md:justify-between"
        >

            <div>
                <h2 class="text-lg font-semibold">
                    Daftar Pembina
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    <?php echo e($coaches->total()); ?>

                    pembina ditemukan.
                </p>
            </div>


            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari pembina..."
                class="rounded-lg border
                       border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>
                    <tr
                        class="border-b
                               border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">
                            Nama
                        </th>

                        <th class="p-3">
                            NIP/NIK
                        </th>

                        <th class="p-3">
                            L/P
                        </th>

                        <th class="p-3">
                            Jabatan
                        </th>

                        <th class="p-3">
                            Telepon
                        </th>

                        <th class="p-3">
                            Status
                        </th>

                        <th class="p-3">
                            Aksi
                        </th>
                    </tr>
                </thead>


                <tbody>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $coaches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coach): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'coach-'.e($coach->id).''; ?>wire:key="coach-<?php echo e($coach->id); ?>"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    <?php echo e($coach->name); ?>

                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
                                    $coach->certificate_number
                                ): ?>
                                    <div
                                        class="mt-1 text-xs
                                               text-zinc-500"
                                    >
                                        <?php echo e($coach->certificate_number); ?>

                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($coach->nip ?: '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($coach->gender ?: '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($coach->position ?: '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($coach->phone ?: '-'); ?>

                            </td>


                            <td class="p-3">

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($coach->is_active): ?>

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-green-700
                                               dark:bg-green-950
                                               dark:text-green-300"
                                    >
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-zinc-600
                                               dark:bg-zinc-800
                                               dark:text-zinc-300"
                                    >
                                        Nonaktif
                                    </span>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>


                            <td class="p-3">

                                <div class="flex gap-2">

                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coaches.update')): ?>

                                        <button
                                            type="button"
                                            wire:click="edit(<?php echo e($coach->id); ?>)"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>

                                    <?php endif; ?>


                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coaches.toggle')): ?>

                                        <button
                                            type="button"
                                            wire:click="
                                                toggleStatus(
                                                    <?php echo e($coach->id); ?>

                                                )
                                            "
                                            wire:confirm="
                                                <?php echo e($coach->is_active
                                                    ? 'Nonaktifkan pembina ini?'
                                                    : 'Aktifkan pembina ini?'); ?>

                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            <?php echo e($coach->is_active
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'); ?>

                                        </button>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                        <tr>
                            <td
                                colspan="7"
                                class="p-8
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada data pembina.
                            </td>
                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            <?php echo e($coaches->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\coaches\index.blade.php ENDPATH**/ ?>