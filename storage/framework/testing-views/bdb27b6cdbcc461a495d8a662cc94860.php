<div class="space-y-1">

    <div class="px-3 py-2 text-xs font-semibold uppercase text-zinc-500">
        Master Data
    </div>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('academic_years.view')): ?>
        <a
            href="<?php echo e(route('academic-years.index')); ?>"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Tahun Ajaran
        </a>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('semesters.view')): ?>
        <a
            href="<?php echo e(route('semesters.index')); ?>"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Semester
        </a>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('classrooms.view')): ?>
        <a
            href="<?php echo e(route('classrooms.index')); ?>"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Kelas
        </a>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('gudep.view')): ?>
        <a
            href="<?php echo e(route('scout-groups.index')); ?>"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Gugus Depan
        </a>
    <?php endif; ?>

</div><?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views\components\layouts\app\sidebar.blade.php ENDPATH**/ ?>