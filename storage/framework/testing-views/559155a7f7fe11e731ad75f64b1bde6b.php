<div class="space-y-6">

    
    <div class="flex items-start justify-between gap-4">

        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
                Data Sekolah
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Kelola sekolah yang menggunakan SIMOPRAM.
            </p>
        </div>

    </div>


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>

        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700
                   dark:border-green-900 dark:bg-green-950
                   dark:text-green-300"
        >
            <?php echo e(session('success')); ?>

        </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
        'schools.create',
        'schools.update'
    ])): ?>

        <form
            wire:submit="save"
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">

                <?php echo e($editingId
                    ? 'Edit Sekolah'
                    : 'Tambah Sekolah'); ?>


            </h2>


            <div
                class="grid gap-4
                       md:grid-cols-2"
            >

                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        NPSN
                    </label>

                    <input
                        type="text"
                        wire:model="npsn"
                        placeholder="Contoh: 50103123"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['npsn'];
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Nama Sekolah
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="SD Negeri 16 Pemecutan"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Jenjang
                    </label>

                    <select
                        wire:model="level"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            Pilih jenjang
                        </option>

                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="SMK">SMK</option>

                        <option value="MI">MI</option>
                        <option value="MTs">MTs</option>
                        <option value="MA">MA</option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['level'];
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Telepon
                    </label>

                    <input
                        type="text"
                        wire:model="phone"
                        placeholder="0361..."
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        wire:model="email"
                        placeholder="sekolah@example.com"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Website
                    </label>

                    <input
                        type="url"
                        wire:model="website"
                        placeholder="https://..."
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['website'];
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Desa / Kelurahan
                    </label>

                    <input
                        type="text"
                        wire:model="village"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Kecamatan
                    </label>

                    <input
                        type="text"
                        wire:model="district"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Kabupaten / Kota
                    </label>

                    <input
                        type="text"
                        wire:model="city"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Provinsi
                    </label>

                    <input
                        type="text"
                        wire:model="province"
                        value="Bali"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Kode Pos
                    </label>

                    <input
                        type="text"
                        wire:model="postal_code"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Zona Waktu
                    </label>

                    <select
                        wire:model="timezone"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="Asia/Jakarta">
                            WIB
                        </option>

                        <option value="Asia/Makassar">
                            WITA
                        </option>

                        <option value="Asia/Jayapura">
                            WIT
                        </option>

                    </select>

                </div>

            </div>


            
            <div class="mt-4">

                <label
                    class="mb-1 block text-sm font-medium"
                >
                    Alamat
                </label>

                <textarea
                    wire:model="address"
                    rows="3"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            
            <label
                class="mt-4 flex items-center gap-2"
            >

                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                <span class="text-sm">
                    Sekolah aktif
                </span>

            </label>


            
            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    <?php echo e($editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah Sekolah'); ?>

                </button>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               border-zinc-300
                               px-4 py-2 text-sm
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
               border-zinc-200 bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 flex flex-col
                   justify-between gap-3
                   md:flex-row md:items-center"
        >

            <div>
                <h2 class="text-lg font-semibold">
                    Daftar Sekolah
                </h2>

                <p class="text-sm text-zinc-500">
                    Pilih Kelola untuk masuk ke tenant sekolah.
                </p>
            </div>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari sekolah / NPSN..."
                class="rounded-lg border
                       border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>

                    <tr
                        class="border-b
                               border-zinc-200
                               text-sm text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">Sekolah</th>
                        <th class="p-3">NPSN</th>
                        <th class="p-3">Jenjang</th>
                        <th class="p-3">Kab/Kota</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $schools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $school): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'school-'.e($school->id).''; ?>wire:key="school-<?php echo e($school->id); ?>"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    <?php echo e($school->name); ?>

                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
                                    (int) session('active_school_id')
                                    ===
                                    (int) $school->id
                                ): ?>

                                    <div
                                        class="mt-1 text-xs
                                               font-medium
                                               text-green-600"
                                    >
                                        Sedang dikelola
                                    </div>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($school->npsn); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($school->level ?: '-'); ?>

                            </td>

                            <td class="p-3">
                                <?php echo e($school->city ?: '-'); ?>

                            </td>

                            <td class="p-3">

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($school->is_active): ?>

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-green-700"
                                    >
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-zinc-600"
                                    >
                                        Nonaktif
                                    </span>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>

                            <td class="p-3">

                                <div
                                    class="flex flex-wrap gap-2"
                                >

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($school->is_active): ?>

                                        <form
                                            method="POST"
                                            action="<?php echo e(route('school.switch')); ?>"
                                        >
                                            <?php echo csrf_field(); ?>

                                            <input
                                                type="hidden"
                                                name="school_id"
                                                value="<?php echo e($school->id); ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="rounded-lg
                                                       bg-zinc-900
                                                       px-3 py-1.5
                                                       text-sm
                                                       text-white
                                                       dark:bg-white
                                                       dark:text-zinc-900"
                                            >
                                                Kelola
                                            </button>

                                        </form>

                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                                    
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('schools.update')): ?>

                                        <button
                                            type="button"
                                            wire:click="edit(<?php echo e($school->id); ?>)"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   text-sm
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>

                                    <?php endif; ?>


                                    
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('schools.toggle')): ?>

                                        <button
                                            type="button"
                                            wire:click="toggleStatus(<?php echo e($school->id); ?>)"
                                            wire:confirm="
                                                <?php echo e($school->is_active
                                                    ? 'Nonaktifkan sekolah ini?'
                                                    : 'Aktifkan sekolah ini?'); ?>

                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   text-sm
                                                   dark:border-zinc-700"
                                        >
                                            <?php echo e($school->is_active
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
                                colspan="6"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada sekolah.
                            </td>

                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            <?php echo e($schools->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\schools\index.blade.php ENDPATH**/ ?>