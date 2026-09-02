<div class="space-y-6">

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


    

    <div>
        <h1
            class="text-2xl font-semibold
                   text-zinc-900 dark:text-white"
        >
            Regu / Barung
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Kelola kelompok Pramuka dan keanggotaan siswa.
        </p>
    </div>


    

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('scout_units.manage')): ?>

        <form
            wire:submit="saveUnit"
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800 dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">

                <?php echo e($editingId
                    ? 'Edit Regu / Barung'
                    : 'Tambah Regu / Barung'); ?>


            </h2>


            <div class="grid gap-4 md:grid-cols-3">

                
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model="academic_year_id"
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


                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($year->is_active): ?>
                                    (Aktif)
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </select>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['academic_year_id'];
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Golongan
                    </label>

                    <select
                        wire:model="scout_level_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $scoutLevels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <option value="<?php echo e($level->id); ?>">
                                <?php echo e($level->name); ?>

                            </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </select>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['scout_level_id'];
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

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Nama
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Contoh: Regu Garuda"
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
                        <p class="mt-1 text-sm text-red-500">
                            <?php echo e($message); ?>

                        </p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </div>

            </div>


            <div class="mt-4">

                <label
                    class="mb-1 block text-sm font-medium"
                >
                    Keterangan
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            <label class="mt-4 flex items-center gap-2">

                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                <span class="text-sm">
                    Unit aktif
                </span>

            </label>


            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white
                           dark:bg-white dark:text-zinc-900"
                >
                    <?php echo e($editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah'); ?>

                </button>


                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>

                    <button
                        type="button"
                        wire:click="cancelUnitEdit"
                        class="rounded-lg border
                               border-zinc-300 px-4 py-2
                               text-sm dark:border-zinc-700"
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

        <div
            class="mb-5 grid gap-3
                   md:grid-cols-3"
        >

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari Regu / Barung..."
                class="rounded-lg border border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >


            <select
                wire:model.live="filterAcademicYearId"
                class="rounded-lg border border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

                <option value="">
                    Semua Tahun Ajaran
                </option>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $academicYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                    <option value="<?php echo e($year->id); ?>">
                        <?php echo e($year->name); ?>

                    </option>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

            </select>


            <select
                wire:model.live="filterScoutLevelId"
                class="rounded-lg border border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

                <option value="">
                    Semua Golongan
                </option>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $scoutLevels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                    <option value="<?php echo e($level->id); ?>">
                        <?php echo e($level->name); ?>

                    </option>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

            </select>

        </div>


        

        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>

                    <tr
                        class="border-b border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">
                            Nama
                        </th>

                        <th class="p-3">
                            Golongan
                        </th>

                        <th class="p-3">
                            Tahun
                        </th>

                        <th class="p-3">
                            Pemimpin
                        </th>

                        <th class="p-3">
                            Anggota
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

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <tr
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'unit-'.e($unit->id).''; ?>wire:key="unit-<?php echo e($unit->id); ?>"
                            class="border-b border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    <?php echo e($unit->name); ?>

                                </div>

                                <div
                                    class="mt-1 text-xs
                                           uppercase text-zinc-500"
                                >
                                    <?php echo e($unit->unit_type); ?>

                                </div>

                            </td>


                            <td class="p-3">
                                <?php echo e($unit->scoutLevel?->name ?? '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($unit->academicYear?->name ?? '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($unit->leader?->name ?? '-'); ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($unit->active_members_count); ?>

                            </td>


                            <td class="p-3">

                                <?php echo e($unit->is_active
                                    ? 'Aktif'
                                    : 'Nonaktif'); ?>


                            </td>


                            <td class="p-3">

                                <div class="flex flex-wrap gap-2">

                                    <button
                                        type="button"
                                        wire:click="selectUnit(<?php echo e($unit->id); ?>)"
                                        class="rounded-lg bg-zinc-900
                                               px-3 py-1.5 text-white
                                               dark:bg-white
                                               dark:text-zinc-900"
                                    >
                                        Anggota
                                    </button>


                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('scout_units.manage')): ?>

                                        <button
                                            type="button"
                                            wire:click="editUnit(<?php echo e($unit->id); ?>)"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>


                                        <button
                                            type="button"
                                            wire:click="
                                                toggleUnitStatus(
                                                    <?php echo e($unit->id); ?>

                                                )
                                            "
                                            wire:confirm="
                                                Ubah status unit ini?
                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            <?php echo e($unit->is_active
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
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada Regu / Barung.
                            </td>

                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            <?php echo e($units->links()); ?>

        </div>

    </div>


    

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedUnit): ?>

        <div
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div class="mb-6">

                <h2 class="text-lg font-semibold">
                    Anggota <?php echo e($selectedUnit->name); ?>

                </h2>

                <p class="mt-1 text-sm text-zinc-500">

                    <?php echo e($selectedUnit->scoutLevel?->name); ?>


                    ·

                    <?php echo e($selectedUnit->academicYear?->name); ?>


                </p>

            </div>


            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('scout_units.manage')): ?>

                <div
                    class="mb-6 grid gap-3
                           md:grid-cols-4"
                >

                    
                    <select
                        wire:model="memberStudentId"
                        class="rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih Siswa --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $eligibleStudents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <option
                                value="<?php echo e($student->id); ?>"
                            >
                                <?php echo e($student->name); ?>

                                - <?php echo e($student->nis); ?>

                            </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </select>


                    
                    <select
                        wire:model="memberPosition"
                        class="rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $positionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <option value="<?php echo e($value); ?>">
                                <?php echo e($label); ?>

                            </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </select>


                    
                    <input
                        type="date"
                        wire:model="memberJoinedAt"
                        class="rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >


                    <button
                        type="button"
                        wire:click="addMember"
                        class="rounded-lg bg-zinc-900
                               px-4 py-2 text-sm
                               font-medium text-white
                               dark:bg-white
                               dark:text-zinc-900"
                    >
                        Tambah Anggota
                    </button>

                </div>


                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['memberStudentId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mb-4 text-sm text-red-500">
                        <?php echo e($message); ?>

                    </p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php endif; ?>


            
            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead>

                        <tr
                            class="border-b border-zinc-200
                                   text-zinc-500
                                   dark:border-zinc-800"
                        >
                            <th class="p-3">
                                Nama
                            </th>

                            <th class="p-3">
                                NIS
                            </th>

                            <th class="p-3">
                                Jabatan
                            </th>

                            <th class="p-3">
                                Bergabung
                            </th>

                            <th class="p-3">
                                Aksi
                            </th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $selectedUnit->memberships; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $membership): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <tr
                                <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = '                                    membership-'.e($membership->id).'

                                '; ?>wire:key="
                                    membership-<?php echo e($membership->id); ?>

                                "
                                class="border-b
                                       border-zinc-100
                                       dark:border-zinc-800"
                            >

                                <td class="p-3">
                                    <?php echo e($membership->student?->name); ?>

                                </td>


                                <td class="p-3">
                                    <?php echo e($membership->student?->nis); ?>

                                </td>


                                <td class="p-3">

                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('scout_units.manage')): ?>

                                        <select
                                            wire:change="
                                                changeMemberPosition(
                                                    <?php echo e($membership->id); ?>,
                                                    $event.target.value
                                                )
                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-2 py-1
                                                   dark:border-zinc-700
                                                   dark:bg-zinc-800"
                                        >

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $positionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                                                <option
                                                    value="<?php echo e($value); ?>"
                                                    <?php if(
                                                        $membership->position
                                                        ===
                                                        $value
                                                    ): echo 'selected'; endif; ?>
                                                >
                                                    <?php echo e($label); ?>

                                                </option>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                                        </select>

                                    <?php else: ?>

                                        <?php echo e($positionOptions[
                                            $membership->position
                                        ] ?? $membership->position); ?>


                                    <?php endif; ?>

                                </td>


                                <td class="p-3">

                                    <?php echo e($membership
                                        ->joined_at
                                        ?->format('d-m-Y')
                                        ?? '-'); ?>


                                </td>


                                <td class="p-3">

                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('scout_units.manage')): ?>

                                        <button
                                            type="button"
                                            wire:click="
                                                removeMember(
                                                    <?php echo e($membership->id); ?>

                                                )
                                            "
                                            wire:confirm="
                                                Keluarkan siswa
                                                dari unit ini?
                                            "
                                            class="rounded-lg border
                                                   border-red-300
                                                   px-3 py-1.5
                                                   text-red-600"
                                        >
                                            Keluarkan
                                        </button>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="p-8 text-center
                                           text-zinc-500"
                                >
                                    Belum ada anggota.
                                </td>

                            </tr>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\scout-units\index.blade.php ENDPATH**/ ?>