<div class="space-y-6">

    
    <div>
        <h1
            class="text-2xl font-semibold
                   text-zinc-900
                   dark:text-white"
        >
            Data Siswa
        </h1>

        <p
            class="mt-1 text-sm
                   text-zinc-500"
        >
            Kelola identitas siswa,
            kelas, dan golongan Pramuka.
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


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
        $academicYears->isEmpty()
        || $classrooms->isEmpty()
        || $scoutLevels->isEmpty()
    ): ?>

        <div
            class="rounded-xl border
                   border-amber-200
                   bg-amber-50 p-4
                   text-sm text-amber-700
                   dark:border-amber-900
                   dark:bg-amber-950
                   dark:text-amber-300"
        >
            Sebelum menambahkan siswa,
            pastikan Tahun Ajaran,
            Kelas, dan Golongan Pramuka
            sudah tersedia.
        </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
        'students.create',
        'students.update'
    ])): ?>

        <form
            wire:submit="save"
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div class="mb-6">

                <h2
                    class="text-lg font-semibold"
                >
                    <?php echo e($editingId
                        ? 'Edit Siswa'
                        : 'Tambah Siswa'); ?>

                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Data siswa dan
                    penempatannya akan
                    disimpan bersamaan.
                </p>

            </div>


            
            <div
                class="mb-4 text-sm
                       font-semibold
                       text-zinc-700
                       dark:text-zinc-300"
            >
                Identitas Siswa
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
                        NIS
                    </label>

                    <input
                        type="text"
                        wire:model="nis"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nis'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1
                                   text-sm
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
                        NISN
                    </label>

                    <input
                        type="text"
                        wire:model="nisn"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nisn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1
                                   text-sm
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
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
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
                            class="mt-1
                                   text-sm
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
                               border
                               border-zinc-300
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
                            class="mt-1
                                   text-sm
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
                        Tempat Lahir
                    </label>

                    <input
                        type="text"
                        wire:model="birth_place"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Lahir
                    </label>

                    <input
                        type="date"
                        wire:model="birth_date"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['birth_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1
                                   text-sm
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
                        Telepon Siswa
                    </label>

                    <input
                        type="text"
                        wire:model="phone"
                        placeholder="Opsional"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Telepon Orang Tua
                    </label>

                    <input
                        type="text"
                        wire:model="parent_phone"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Bergabung
                    </label>

                    <input
                        type="date"
                        wire:model="joined_at"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="active">
                            Aktif
                        </option>

                        <option value="inactive">
                            Nonaktif
                        </option>

                        <option value="graduated">
                            Lulus
                        </option>

                        <option value="transferred">
                            Pindah
                        </option>
                    </select>
                </div>

            </div>


            
            <div class="mt-4">

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Alamat
                </label>

                <textarea
                    wire:model="address"
                    rows="3"
                    class="w-full rounded-lg
                           border
                           border-zinc-300
                           px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            
            <div
                class="mb-4 mt-8
                       border-t
                       border-zinc-200
                       pt-6
                       dark:border-zinc-800"
            >

                <div
                    class="text-sm
                           font-semibold
                           text-zinc-700
                           dark:text-zinc-300"
                >
                    Penempatan Siswa
                </div>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Penempatan disimpan
                    berdasarkan tahun ajaran.
                </p>

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

                    <select
                        wire:model.live="
                            academic_year_id
                        "
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $academicYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $academicYear): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <option
                                value="<?php echo e($academicYear->id); ?>"
                            >
                                <?php echo e($academicYear->name); ?>


                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
                                    $academicYear->is_active
                                ): ?>
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
                        <p
                            class="mt-1
                                   text-sm
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
                        Kelas
                    </label>

                    <select
                        wire:model="classroom_id"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih Kelas --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $classrooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $classroom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <option
                                value="<?php echo e($classroom->id); ?>"
                            >
                                <?php echo e($classroom->name); ?>

                            </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </select>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['classroom_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1
                                   text-sm
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
                        Golongan Pramuka
                    </label>

                    <select
                        wire:model="scout_level_id"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih Golongan --
                        </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $scoutLevels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scoutLevel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                            <option
                                value="<?php echo e($scoutLevel->id); ?>"
                            >
                                <?php echo e($scoutLevel->name); ?>

                            </option>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    </select>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['scout_level_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p
                            class="mt-1
                                   text-sm
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


            
            <div
                class="mt-6 flex gap-2"
            >

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    <?php if(
                        $academicYears->isEmpty()
                        ||
                        $classrooms->isEmpty()
                        ||
                        $scoutLevels->isEmpty()
                    ): echo 'disabled'; endif; ?>
                    class="rounded-lg
                           bg-zinc-900
                           px-4 py-2
                           text-sm font-medium
                           text-white
                           disabled:cursor-not-allowed
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
                            : 'Tambah Siswa'); ?>

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
               bg-white p-6
               shadow-sm
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

                <h2
                    class="text-lg font-semibold"
                >
                    Daftar Siswa
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    <?php echo e($students->total()); ?>

                    siswa ditemukan.
                </p>

            </div>


            <input
                type="search"
                wire:model.live.debounce.300ms="
                    search
                "
                placeholder="
                    Cari nama / NIS / NISN...
                "
                class="rounded-lg
                       border
                       border-zinc-300
                       px-3 py-2
                       text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

        </div>


        <div class="overflow-x-auto">

            <table
                class="w-full text-left
                       text-sm"
            >

                <thead>

                    <tr
                        class="border-b
                               border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >

                        <th class="p-3">
                            Siswa
                        </th>

                        <th class="p-3">
                            NIS
                        </th>

                        <th class="p-3">
                            Kelas
                        </th>

                        <th class="p-3">
                            Golongan
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

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <?php
                            $currentEnrollment =
                                $student
                                    ->enrollments
                                    ->first();

                            $currentScoutLevel =
                                $student
                                    ->scoutLevelHistories
                                    ->first();
                        ?>

                        <tr
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = '                                student-'.e($student->id).'

                            '; ?>wire:key="
                                student-<?php echo e($student->id); ?>

                            "
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div
                                    class="font-medium"
                                >
                                    <?php echo e($student->name); ?>

                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($student->nisn): ?>

                                    <div
                                        class="mt-1
                                               text-xs
                                               text-zinc-500"
                                    >
                                        NISN:
                                        <?php echo e($student->nisn); ?>

                                    </div>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>


                            <td class="p-3">
                                <?php echo e($student->nis); ?>

                            </td>


                            <td class="p-3">

                                <?php echo e($currentEnrollment
                                    ?->classroom
                                    ?->name
                                    ?? '-'); ?>


                            </td>


                            <td class="p-3">

                                <?php echo e($currentScoutLevel
                                    ?->scoutLevel
                                    ?->name
                                    ?? '-'); ?>


                            </td>


                            <td class="p-3">

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch($student->status):

                                    case ('active'): ?>

                                        <span
                                            class="rounded-full
                                                   bg-green-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-green-700
                                                   dark:bg-green-950
                                                   dark:text-green-300"
                                        >
                                            Aktif
                                        </span>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php break; ?>


                                    <?php case ('inactive'): ?>

                                        <span
                                            class="rounded-full
                                                   bg-zinc-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-zinc-600
                                                   dark:bg-zinc-800
                                                   dark:text-zinc-300"
                                        >
                                            Nonaktif
                                        </span>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php break; ?>


                                    <?php case ('graduated'): ?>

                                        <span
                                            class="rounded-full
                                                   bg-blue-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-blue-700"
                                        >
                                            Lulus
                                        </span>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php break; ?>


                                    <?php case ('transferred'): ?>

                                        <span
                                            class="rounded-full
                                                   bg-amber-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-amber-700"
                                        >
                                            Pindah
                                        </span>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php break; ?>

                                <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            </td>


                            <td class="p-3">

                                <div
                                    class="flex
                                           flex-wrap gap-2"
                                >

                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check(
                                        'students.update'
                                    )): ?>

                                        <button
                                            type="button"
                                            wire:click="
                                                edit(
                                                    <?php echo e($student->id); ?>

                                                )
                                            "
                                            class="rounded-lg
                                                   border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>

                                    <?php endif; ?>


                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check(
                                        'students.toggle'
                                    )): ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(
                                            in_array(
                                                $student->status,
                                                [
                                                    'active',
                                                    'inactive'
                                                ],
                                                true
                                            )
                                        ): ?>

                                            <button
                                                type="button"
                                                wire:click="
                                                    toggleStatus(
                                                        <?php echo e($student->id); ?>

                                                    )
                                                "
                                                wire:confirm="
                                                    Ubah status siswa ini?
                                                "
                                                class="rounded-lg
                                                       border
                                                       border-zinc-300
                                                       px-3 py-1.5
                                                       dark:border-zinc-700"
                                            >
                                                <?php echo e($student->status ===
                                                    'active'
                                                        ? 'Nonaktifkan'
                                                        : 'Aktifkan'); ?>

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
                                class="p-8
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada data siswa.
                            </td>

                        </tr>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            <?php echo e($students->links()); ?>

        </div>

    </div>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\livewire\students\index.blade.php ENDPATH**/ ?>