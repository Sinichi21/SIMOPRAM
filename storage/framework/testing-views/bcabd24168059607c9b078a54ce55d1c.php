<?php # [BlazeFolded]:{flux::sidebar.group}:{D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/group.blade.php}:{1788140904} ?>
<?php # [BlazeFolded]:{flux::sidebar.nav}:{D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/nav.blade.php}:{1788140904} ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('schools.view')): ?>

    <div class="mb-4 space-y-1">

        <div
            class="px-3 py-2 text-xs
                   font-semibold uppercase
                   tracking-wide text-zinc-500"
        >
            Administrasi
        </div>

        <a
            href="<?php echo e(route('schools.index')); ?>"
            wire:navigate
            class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'block rounded-lg px-3 py-2
                 text-sm font-medium transition',

                'bg-zinc-900 text-white
                 dark:bg-white dark:text-zinc-900'
                    => request()->routeIs(
                        'schools.*'
                    ),

                'text-zinc-700
                 hover:bg-zinc-100
                 dark:text-zinc-300
                 dark:hover:bg-zinc-800'
                    => ! request()->routeIs(
                        'schools.*'
                    ),
            ]); ?>"
        >
            Data Sekolah
        </a>

    </div>

<?php endif; ?>



<form
    method="POST"
    action="<?php echo e(route('school.switch')); ?>"
    class="mb-3"
>
    <?php echo csrf_field(); ?>

<div class="space-y-2">

    <label
        for="school_id"
        class="block text-xs font-semibold uppercase tracking-wide text-zinc-500"
    >
        Sekolah Aktif
    </label>

    <select
        id="school_id"
        name="school_id"
        onchange="if (this.value) this.form.submit()"
        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm
               text-zinc-900 outline-none transition
               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
               dark:focus:border-zinc-500 dark:focus:ring-zinc-800"
    >
        <option value="">
            -- Pilih Sekolah --
        </option>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $availableSchools ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $school): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <option
                value="<?php echo e($school->id); ?>"
                <?php if(
                    (int) session('active_school_id') === (int) $school->id
                ): echo 'selected'; endif; ?>
            >
                <?php echo e($school->name); ?>

            </option>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </select>

</div>

</form>



<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('active_school_id')): ?>

<?php
    $activeSchool = $availableSchools->firstWhere(
        'id',
        (int) session('active_school_id')
    );
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeSchool): ?>

    <div
        class="mb-4 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2
               dark:border-zinc-700 dark:bg-zinc-800/70"
    >

        <div class="text-xs text-zinc-500 dark:text-zinc-400">
            Sedang mengelola
        </div>

        <div
            class="mt-0.5 truncate text-sm font-semibold text-zinc-900
                   dark:text-zinc-100"
            title="<?php echo e($activeSchool->name); ?>"
        >
            <?php echo e($activeSchool->name); ?>

        </div>

    </div>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>



<div class="space-y-1">

<div
    class="px-3 py-2 text-xs font-semibold uppercase tracking-wide
           text-zinc-500 dark:text-zinc-400"
>
    Master Data
</div>



<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('academic_years.view')): ?>

    <a
        href="<?php echo e(route('academic-years.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('academic-years.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('academic-years.*'),
        ]); ?>"
    >
        Tahun Ajaran
    </a>

<?php endif; ?>



<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('semesters.view')): ?>

    <a
        href="<?php echo e(route('semesters.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('semesters.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('semesters.*'),
        ]); ?>"
    >
        Semester
    </a>

<?php endif; ?>



<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('classrooms.view')): ?>

    <a
        href="<?php echo e(route('classrooms.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('classrooms.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('classrooms.*'),
        ]); ?>"
    >
        Kelas
    </a>

<?php endif; ?>



<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('gudep.view')): ?>

    <a
        href="<?php echo e(route('scout-groups.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('scout-groups.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('scout-groups.*'),
        ]); ?>"
    >
        Gugus Depan
    </a>

<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coaches.view')): ?>

    <a
        href="<?php echo e(route('coaches.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2
             text-sm font-medium transition',

            'bg-zinc-900 text-white
             dark:bg-white dark:text-zinc-900'
                => request()->routeIs(
                    'coaches.*'
                ),

            'text-zinc-700
             hover:bg-zinc-100
             hover:text-zinc-900
             dark:text-zinc-300
             dark:hover:bg-zinc-800
             dark:hover:text-white'
                => ! request()->routeIs(
                    'coaches.*'
                ),
        ]); ?>"
    >
        Pembina
    </a>

<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('students.view')): ?>

    <a
        href="<?php echo e(route('students.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2
             text-sm font-medium transition',

            'bg-zinc-900 text-white
             dark:bg-white dark:text-zinc-900'
                => request()->routeIs(
                    'students.*'
                ),

            'text-zinc-700
             hover:bg-zinc-100
             hover:text-zinc-900
             dark:text-zinc-300
             dark:hover:bg-zinc-800
             dark:hover:text-white'
                => ! request()->routeIs(
                    'students.*'
                ),
        ]); ?>"
    >
        Siswa
    </a>

<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('scout_units.view')): ?>

    <a
        href="<?php echo e(route('scout-units.index')); ?>"
        wire:navigate
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'block rounded-lg px-3 py-2
             text-sm font-medium transition',

            'bg-zinc-900 text-white
             dark:bg-white dark:text-zinc-900'
                => request()->routeIs(
                    'scout-units.*'
                ),

            'text-zinc-700
             hover:bg-zinc-100
             hover:text-zinc-900
             dark:text-zinc-300
             dark:hover:bg-zinc-800
             dark:hover:text-white'
                => ! request()->routeIs(
                    'scout-units.*'
                ),
        ]); ?>"
    >
        Regu / Barung
    </a>

<?php endif; ?>
</div>

<?php ob_start(); ?><nav class="flex flex-col overflow-visible min-h-auto" data-flux-sidebar-nav>
    <?php ob_start(); ?>

    <?php ob_start(); ?><div class="flex flex-col in-data-flux-sidebar-collapsed-desktop:hidden grid" data-flux-sidebar-group>
        <div class="px-3 py-2">
            <div class="text-sm text-zinc-400 font-medium leading-none">Kegiatan</div>
        </div>

        <div class="flex flex-col">
            <?php ob_start(); ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activities.view')): ?>

            <?php if (!function_exists('_9a2efb6e4050d7485eb368a8c72b7c41')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/item.blade.php', $__blaze->compiledPath.'/9a2efb6e4050d7485eb368a8c72b7c41.php'); require $__blaze->compiledPath.'/9a2efb6e4050d7485eb368a8c72b7c41.php'; } ?>
<?php if (isset($__slots9a2efb6e4050d7485eb368a8c72b7c41)) { $__slotsStack9a2efb6e4050d7485eb368a8c72b7c41[] = $__slots9a2efb6e4050d7485eb368a8c72b7c41; } ?>
<?php if (isset($__attrs9a2efb6e4050d7485eb368a8c72b7c41)) { $__attrsStack9a2efb6e4050d7485eb368a8c72b7c41[] = $__attrs9a2efb6e4050d7485eb368a8c72b7c41; } ?>
<?php $__attrs9a2efb6e4050d7485eb368a8c72b7c41 = ['icon' => 'calendar-days','href' => route('activities.index'),'current' => request()->routeIs('activities.*'),'wire:navigate' => true]; ?>
<?php $__slots9a2efb6e4050d7485eb368a8c72b7c41 = []; ?>
<?php $__blaze->pushData($__attrs9a2efb6e4050d7485eb368a8c72b7c41); ?>
<?php ob_start(); ?>
                Agenda / Kegiatan
            <?php $__slots9a2efb6e4050d7485eb368a8c72b7c41['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots9a2efb6e4050d7485eb368a8c72b7c41); ?>
<?php _9a2efb6e4050d7485eb368a8c72b7c41($__blaze, $__attrs9a2efb6e4050d7485eb368a8c72b7c41, $__slots9a2efb6e4050d7485eb368a8c72b7c41, ['href', 'current', 'wire:navigate'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack9a2efb6e4050d7485eb368a8c72b7c41)) { $__slots9a2efb6e4050d7485eb368a8c72b7c41 = array_pop($__slotsStack9a2efb6e4050d7485eb368a8c72b7c41); } ?>
<?php if (! empty($__attrsStack9a2efb6e4050d7485eb368a8c72b7c41)) { $__attrs9a2efb6e4050d7485eb368a8c72b7c41 = array_pop($__attrsStack9a2efb6e4050d7485eb368a8c72b7c41); } ?>
<?php $__blaze->popData(); ?>

        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('attendance_sessions.view')): ?>

            <?php if (!function_exists('_9a2efb6e4050d7485eb368a8c72b7c41')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/item.blade.php', $__blaze->compiledPath.'/9a2efb6e4050d7485eb368a8c72b7c41.php'); require $__blaze->compiledPath.'/9a2efb6e4050d7485eb368a8c72b7c41.php'; } ?>
<?php if (isset($__slots9a2efb6e4050d7485eb368a8c72b7c41)) { $__slotsStack9a2efb6e4050d7485eb368a8c72b7c41[] = $__slots9a2efb6e4050d7485eb368a8c72b7c41; } ?>
<?php if (isset($__attrs9a2efb6e4050d7485eb368a8c72b7c41)) { $__attrsStack9a2efb6e4050d7485eb368a8c72b7c41[] = $__attrs9a2efb6e4050d7485eb368a8c72b7c41; } ?>
<?php $__attrs9a2efb6e4050d7485eb368a8c72b7c41 = ['icon' => 'clipboard-document-check','href' => route('attendances.index'),'current' => request()->routeIs('attendances.*'),'wire:navigate' => true]; ?>
<?php $__slots9a2efb6e4050d7485eb368a8c72b7c41 = []; ?>
<?php $__blaze->pushData($__attrs9a2efb6e4050d7485eb368a8c72b7c41); ?>
<?php ob_start(); ?>
                Absensi
            <?php $__slots9a2efb6e4050d7485eb368a8c72b7c41['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots9a2efb6e4050d7485eb368a8c72b7c41); ?>
<?php _9a2efb6e4050d7485eb368a8c72b7c41($__blaze, $__attrs9a2efb6e4050d7485eb368a8c72b7c41, $__slots9a2efb6e4050d7485eb368a8c72b7c41, ['href', 'current', 'wire:navigate'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack9a2efb6e4050d7485eb368a8c72b7c41)) { $__slots9a2efb6e4050d7485eb368a8c72b7c41 = array_pop($__slotsStack9a2efb6e4050d7485eb368a8c72b7c41); } ?>
<?php if (! empty($__attrsStack9a2efb6e4050d7485eb368a8c72b7c41)) { $__attrs9a2efb6e4050d7485eb368a8c72b7c41 = array_pop($__attrsStack9a2efb6e4050d7485eb368a8c72b7c41); } ?>
<?php $__blaze->popData(); ?>

        <?php endif; ?>

    <?php echo trim(ob_get_clean()); ?>

        </div>
    </div>
<?php echo ltrim(ob_get_clean()); ?>

<?php echo trim(ob_get_clean()); ?>

</nav>
<?php echo ltrim(ob_get_clean()); ?>
<?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\layouts\app\sidebar.blade.php ENDPATH**/ ?>