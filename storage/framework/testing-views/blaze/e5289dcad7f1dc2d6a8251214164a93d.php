<?php
if (!function_exists('__e5289dcad7f1dc2d6a8251214164a93d')):
function __e5289dcad7f1dc2d6a8251214164a93d($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $iconTrailing ??= $attributes->pluck('icon:trailing'); ?>
<?php $iconVariant ??= $attributes->pluck('icon:variant'); ?>

<?php
$__defaults = [
    'iconVariant' => 'outline',
    'iconTrailing' => null,
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
    'icon' => null,
];
$iconVariant ??= $attributes['icon-variant'] ?? $attributes['iconVariant'] ?? $__defaults['iconVariant']; unset($attributes['iconVariant'], $attributes['icon-variant']);
$iconTrailing ??= $attributes['icon-trailing'] ?? $attributes['iconTrailing'] ?? $__defaults['iconTrailing']; unset($attributes['iconTrailing'], $attributes['icon-trailing']);
$expandable ??= $attributes['expandable'] ?? $__defaults['expandable']; unset($attributes['expandable']);
$expanded ??= $attributes['expanded'] ?? $__defaults['expanded']; unset($attributes['expanded']);
$heading ??= $attributes['heading'] ?? $__defaults['heading']; unset($attributes['heading']);
$icon ??= $attributes['icon'] ?? $__defaults['icon']; unset($attributes['icon']);
unset($__defaults);
?>

<?php if ($expandable && $heading): ?>
    <?php if ($icon): ?>
        <ui-disclosure <?php echo e($attributes->class('group/disclosure in-data-flux-sidebar-collapsed-desktop:hidden')); ?> <?php if($expanded === true): ?> open <?php endif; ?> data-flux-sidebar-group>
            <button type="button" class="border-1 border-transparent w-full h-8 in-data-flux-sidebar-on-mobile:h-10 flex items-center group/disclosure-button my-px rounded-lg hover:bg-zinc-800/5 dark:hover:bg-white/[7%] text-zinc-500 hover:text-zinc-800 dark:text-white/80 dark:hover:text-white">
                <div class="px-3">
                    <?php if (is_string($icon) && $icon !== ''): ?>
                        <?php if (!function_exists('__30c086465a738807bc38961e7a562afb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'); require $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'; } ?>
<?php $__blaze->pushData(['icon' => $icon,'variant' => $iconVariant,'class' => 'size-4']); ?>
<?php __30c086465a738807bc38961e7a562afb($__blaze, ['icon' => $icon,'variant' => $iconVariant,'class' => 'size-4'], [], ['icon', 'variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                    <?php else: ?>
                        <?php echo e($icon); ?>

                    <?php endif; ?>
                </div>

                <span class="flex-1 text-left rtl:text-right text-sm font-medium leading-none"><?php echo e($heading); ?></span>

                <div class="ps-3 pe-2.5">
                    <?php if (!function_exists('__eff02191728c704e42fc2caec4ea0b8e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-down.blade.php', $__blaze->compiledPath.'/eff02191728c704e42fc2caec4ea0b8e.php'); require $__blaze->compiledPath.'/eff02191728c704e42fc2caec4ea0b8e.php'; } ?>
<?php $__blaze->pushData(['class' => 'size-3! hidden group-data-open/disclosure-button:block']); ?>
<?php __eff02191728c704e42fc2caec4ea0b8e($__blaze, ['class' => 'size-3! hidden group-data-open/disclosure-button:block'], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                    <?php if (!function_exists('__2dddbacd1181b319bf11897114707d1e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-right.blade.php', $__blaze->compiledPath.'/2dddbacd1181b319bf11897114707d1e.php'); require $__blaze->compiledPath.'/2dddbacd1181b319bf11897114707d1e.php'; } ?>
<?php $__blaze->pushData(['class' => 'size-3! block group-data-open/disclosure-button:hidden rtl:rotate-180']); ?>
<?php __2dddbacd1181b319bf11897114707d1e($__blaze, ['class' => 'size-3! block group-data-open/disclosure-button:hidden rtl:rotate-180'], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                </div>
            </button>

            <div class="relative hidden data-open:block ps-7" <?php if($expanded === true): ?> data-open <?php endif; ?>>
                <div class="absolute inset-y-[3px] w-px bg-zinc-200 dark:bg-white/30 start-0 ms-5"></div>

                <div class="flex flex-col">
                    <?php echo e($slot); ?>

                </div>
            </div>
        </ui-disclosure>

        <?php if (!function_exists('__538870d24e5fba8153065c3dc2e99f79')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/dropdown.blade.php', $__blaze->compiledPath.'/538870d24e5fba8153065c3dc2e99f79.php'); require $__blaze->compiledPath.'/538870d24e5fba8153065c3dc2e99f79.php'; } ?>
<?php if (isset($__slots538870d24e5fba8153065c3dc2e99f79)) { $__slotsStack538870d24e5fba8153065c3dc2e99f79[] = $__slots538870d24e5fba8153065c3dc2e99f79; } ?>
<?php if (isset($__attrs538870d24e5fba8153065c3dc2e99f79)) { $__attrsStack538870d24e5fba8153065c3dc2e99f79[] = $__attrs538870d24e5fba8153065c3dc2e99f79; } ?>
<?php $__attrs538870d24e5fba8153065c3dc2e99f79 = ['hover' => true,'class' => 'in-data-flux-sidebar-on-mobile:hidden not-in-data-flux-sidebar-collapsed-desktop:hidden','position' => 'right','align' => 'start','dataFluxSidebarGroupDropdown' => true]; ?>
<?php $__slots538870d24e5fba8153065c3dc2e99f79 = []; ?>
<?php $__blaze->pushData($__attrs538870d24e5fba8153065c3dc2e99f79); ?>
<?php ob_start(); ?>
            <button type="button" class="border-1 border-transparent w-full px-3 in-data-flux-menu:px-2 h-8 flex gap-3 items-center group/disclosure-button my-px rounded-lg in-data-flux-sidebar-collapsed-desktop:not-in-data-flux-menu:w-10 in-data-flux-sidebar-collapsed-desktop:not-in-data-flux-menu:justify-center hover:bg-zinc-800/5 dark:hover:bg-white/[7%] in-data-flux-menu:hover:bg-zinc-50 dark:in-data-flux-menu:hover:bg-zinc-600 text-zinc-500 in-data-flux-menu:text-zinc-800 hover:text-zinc-800 dark:text-white/80 in-data-flux-menu:dark:text-white dark:hover:text-white">
                <?php if ($icon): ?>
                    <div class="relative">
                        <?php if (is_string($icon) && $icon !== ''): ?>
                            <?php if (!function_exists('__30c086465a738807bc38961e7a562afb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'); require $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'; } ?>
<?php $__blaze->pushData(['icon' => $icon,'variant' => $iconVariant,'class' => 'in-data-flux-menu:text-zinc-400 in-data-flux-menu:dark:text-white/80 in-data-flux-menu:[[data-flux-sidebar-group-dropdown]>button:hover_&]:text-current size-4']); ?>
<?php __30c086465a738807bc38961e7a562afb($__blaze, ['icon' => $icon,'variant' => $iconVariant,'class' => 'in-data-flux-menu:text-zinc-400 in-data-flux-menu:dark:text-white/80 in-data-flux-menu:[[data-flux-sidebar-group-dropdown]>button:hover_&]:text-current size-4'], [], ['icon', 'variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                        <?php else: ?>
                            <?php echo e($icon); ?>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <span class="hidden in-data-flux-menu:block flex-1 text-start text-sm font-medium leading-none text-zinc-800 dark:text-white"><?php echo e($heading); ?></span>

                <div class="hidden in-data-flux-menu:block">
                    <?php if (!function_exists('__2dddbacd1181b319bf11897114707d1e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-right.blade.php', $__blaze->compiledPath.'/2dddbacd1181b319bf11897114707d1e.php'); require $__blaze->compiledPath.'/2dddbacd1181b319bf11897114707d1e.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant,'class' => 'ms-auto size-4 text-zinc-400 [[data-flux-sidebar-group-dropdown]>button:hover_&]:text-current rtl:hidden']); ?>
<?php __2dddbacd1181b319bf11897114707d1e($__blaze, ['variant' => $iconVariant,'class' => 'ms-auto size-4 text-zinc-400 [[data-flux-sidebar-group-dropdown]>button:hover_&]:text-current rtl:hidden'], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                    <?php if (!function_exists('__8ff64f8784ec85e85a9ab9f5f14ea376')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-left.blade.php', $__blaze->compiledPath.'/8ff64f8784ec85e85a9ab9f5f14ea376.php'); require $__blaze->compiledPath.'/8ff64f8784ec85e85a9ab9f5f14ea376.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant,'class' => 'ms-auto size-4 text-zinc-400 [[data-flux-sidebar-group-dropdown]>button:hover_&]:text-current hidden rtl:inline']); ?>
<?php __8ff64f8784ec85e85a9ab9f5f14ea376($__blaze, ['variant' => $iconVariant,'class' => 'ms-auto size-4 text-zinc-400 [[data-flux-sidebar-group-dropdown]>button:hover_&]:text-current hidden rtl:inline'], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                </div>
            </button>

            <?php if (!function_exists('__d4fbb40246d288bcc75582da7fafeb06')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/menu/index.blade.php', $__blaze->compiledPath.'/d4fbb40246d288bcc75582da7fafeb06.php'); require $__blaze->compiledPath.'/d4fbb40246d288bcc75582da7fafeb06.php'; } ?>
<?php if (isset($__slotsd4fbb40246d288bcc75582da7fafeb06)) { $__slotsStackd4fbb40246d288bcc75582da7fafeb06[] = $__slotsd4fbb40246d288bcc75582da7fafeb06; } ?>
<?php if (isset($__attrsd4fbb40246d288bcc75582da7fafeb06)) { $__attrsStackd4fbb40246d288bcc75582da7fafeb06[] = $__attrsd4fbb40246d288bcc75582da7fafeb06; } ?>
<?php $__attrsd4fbb40246d288bcc75582da7fafeb06 = []; ?>
<?php $__slotsd4fbb40246d288bcc75582da7fafeb06 = []; ?>
<?php $__blaze->pushData($__attrsd4fbb40246d288bcc75582da7fafeb06); ?>
<?php ob_start(); ?>
                <?php if (!function_exists('__f31863992c249f65626dbe274a6e2769')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/menu/group.blade.php', $__blaze->compiledPath.'/f31863992c249f65626dbe274a6e2769.php'); require $__blaze->compiledPath.'/f31863992c249f65626dbe274a6e2769.php'; } ?>
<?php if (isset($__slotsf31863992c249f65626dbe274a6e2769)) { $__slotsStackf31863992c249f65626dbe274a6e2769[] = $__slotsf31863992c249f65626dbe274a6e2769; } ?>
<?php if (isset($__attrsf31863992c249f65626dbe274a6e2769)) { $__attrsStackf31863992c249f65626dbe274a6e2769[] = $__attrsf31863992c249f65626dbe274a6e2769; } ?>
<?php $__attrsf31863992c249f65626dbe274a6e2769 = ['heading' => $heading]; ?>
<?php $__slotsf31863992c249f65626dbe274a6e2769 = []; ?>
<?php $__blaze->pushData($__attrsf31863992c249f65626dbe274a6e2769); ?>
<?php ob_start(); ?>
                    <?php echo e($slot); ?>

                <?php $__slotsf31863992c249f65626dbe274a6e2769['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slotsf31863992c249f65626dbe274a6e2769); ?>
<?php __f31863992c249f65626dbe274a6e2769($__blaze, $__attrsf31863992c249f65626dbe274a6e2769, $__slotsf31863992c249f65626dbe274a6e2769, ['heading'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackf31863992c249f65626dbe274a6e2769)) { $__slotsf31863992c249f65626dbe274a6e2769 = array_pop($__slotsStackf31863992c249f65626dbe274a6e2769); } ?>
<?php if (! empty($__attrsStackf31863992c249f65626dbe274a6e2769)) { $__attrsf31863992c249f65626dbe274a6e2769 = array_pop($__attrsStackf31863992c249f65626dbe274a6e2769); } ?>
<?php $__blaze->popData(); ?>
            <?php $__slotsd4fbb40246d288bcc75582da7fafeb06['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slotsd4fbb40246d288bcc75582da7fafeb06); ?>
<?php __d4fbb40246d288bcc75582da7fafeb06($__blaze, $__attrsd4fbb40246d288bcc75582da7fafeb06, $__slotsd4fbb40246d288bcc75582da7fafeb06, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd4fbb40246d288bcc75582da7fafeb06)) { $__slotsd4fbb40246d288bcc75582da7fafeb06 = array_pop($__slotsStackd4fbb40246d288bcc75582da7fafeb06); } ?>
<?php if (! empty($__attrsStackd4fbb40246d288bcc75582da7fafeb06)) { $__attrsd4fbb40246d288bcc75582da7fafeb06 = array_pop($__attrsStackd4fbb40246d288bcc75582da7fafeb06); } ?>
<?php $__blaze->popData(); ?>
        <?php $__slots538870d24e5fba8153065c3dc2e99f79['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots538870d24e5fba8153065c3dc2e99f79); ?>
<?php __538870d24e5fba8153065c3dc2e99f79($__blaze, $__attrs538870d24e5fba8153065c3dc2e99f79, $__slots538870d24e5fba8153065c3dc2e99f79, ['hover', 'dataFluxSidebarGroupDropdown'], ['dataFluxSidebarGroupDropdown' => 'data-flux-sidebar-group-dropdown'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack538870d24e5fba8153065c3dc2e99f79)) { $__slots538870d24e5fba8153065c3dc2e99f79 = array_pop($__slotsStack538870d24e5fba8153065c3dc2e99f79); } ?>
<?php if (! empty($__attrsStack538870d24e5fba8153065c3dc2e99f79)) { $__attrs538870d24e5fba8153065c3dc2e99f79 = array_pop($__attrsStack538870d24e5fba8153065c3dc2e99f79); } ?>
<?php $__blaze->popData(); ?>
    <?php else: ?>
        <ui-disclosure <?php echo e($attributes->class('group/disclosure in-data-flux-sidebar-collapsed-desktop:hidden')); ?> <?php if($expanded === true): ?> open <?php endif; ?> data-flux-sidebar-group>
            <button type="button" class="border-1 border-transparent w-full h-8 in-data-flux-sidebar-on-mobile:h-10 flex items-center group/disclosure-button my-px rounded-lg hover:bg-zinc-800/5 dark:hover:bg-white/[7%] text-zinc-500 hover:text-zinc-800 dark:text-white/80 dark:hover:text-white">
                <div class="ps-3.5 pe-3.5">
                    <?php if (!function_exists('__eff02191728c704e42fc2caec4ea0b8e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-down.blade.php', $__blaze->compiledPath.'/eff02191728c704e42fc2caec4ea0b8e.php'); require $__blaze->compiledPath.'/eff02191728c704e42fc2caec4ea0b8e.php'; } ?>
<?php $__blaze->pushData(['class' => 'size-3! hidden group-data-open/disclosure-button:block']); ?>
<?php __eff02191728c704e42fc2caec4ea0b8e($__blaze, ['class' => 'size-3! hidden group-data-open/disclosure-button:block'], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                    <?php if (!function_exists('__2dddbacd1181b319bf11897114707d1e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-right.blade.php', $__blaze->compiledPath.'/2dddbacd1181b319bf11897114707d1e.php'); require $__blaze->compiledPath.'/2dddbacd1181b319bf11897114707d1e.php'; } ?>
<?php $__blaze->pushData(['class' => 'size-3! block group-data-open/disclosure-button:hidden rtl:rotate-180']); ?>
<?php __2dddbacd1181b319bf11897114707d1e($__blaze, ['class' => 'size-3! block group-data-open/disclosure-button:hidden rtl:rotate-180'], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                </div>

                <span class="text-sm font-medium leading-none"><?php echo e($heading); ?></span>
            </button>

            <div class="relative hidden data-open:block ps-7" <?php if($expanded === true): ?> data-open <?php endif; ?>>
                <div class="absolute inset-y-[3px] w-px bg-zinc-200 dark:bg-white/30 start-0 ms-5"></div>

                <div class="flex flex-col">
                    <?php echo e($slot); ?>

                </div>
            </div>
        </ui-disclosure>
    <?php endif; ?>

<?php elseif ($heading): ?>
    <div <?php echo e($attributes->class('flex flex-col in-data-flux-sidebar-collapsed-desktop:hidden')); ?> data-flux-sidebar-group>
        <div class="px-3 py-2">
            <div class="text-sm text-zinc-400 font-medium leading-none"><?php echo e($heading); ?></div>
        </div>

        <div class="flex flex-col">
            <?php echo e($slot); ?>

        </div>
    </div>
<?php else: ?>
    <div <?php echo e($attributes->class('flex flex-col in-data-flux-sidebar-collapsed-desktop:hidden')); ?> data-flux-sidebar-group>
        <?php echo e($slot); ?>

    </div>
<?php endif; ?>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/group.blade.php ENDPATH**/ ?>