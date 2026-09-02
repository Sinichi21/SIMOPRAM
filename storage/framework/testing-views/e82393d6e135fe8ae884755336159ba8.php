<?php
if (!function_exists('_e82393d6e135fe8ae884755336159ba8')):
function _e82393d6e135fe8ae884755336159ba8($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $onLabel ??= $attributes->pluck('on:label'); ?>
<?php $offLabel ??= $attributes->pluck('off:label'); ?>
<?php $onIcon ??= $attributes->pluck('on:icon'); ?>
<?php $offIcon ??= $attributes->pluck('off:icon'); ?>

<?php
$__defaults = [
    'variant' => 'outline',
    'checked' => null,
    'size' => 'base',
    'name' => null,
    'icon' => null,
    'label' => null,
    'color' => null,
    'inset' => null,
    'onLabel' => null,
    'offLabel' => null,
    'onIcon' => null,
    'offIcon' => null,
];
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$checked ??= $attributes['checked'] ?? $__defaults['checked']; unset($attributes['checked']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$icon ??= $attributes['icon'] ?? $__defaults['icon']; unset($attributes['icon']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
$color ??= $attributes['color'] ?? $__defaults['color']; unset($attributes['color']);
$inset ??= $attributes['inset'] ?? $__defaults['inset']; unset($attributes['inset']);
$onLabel ??= $attributes['on-label'] ?? $attributes['onLabel'] ?? $__defaults['onLabel']; unset($attributes['onLabel'], $attributes['on-label']);
$offLabel ??= $attributes['off-label'] ?? $attributes['offLabel'] ?? $__defaults['offLabel']; unset($attributes['offLabel'], $attributes['off-label']);
$onIcon ??= $attributes['on-icon'] ?? $attributes['onIcon'] ?? $__defaults['onIcon']; unset($attributes['onIcon'], $attributes['on-icon']);
$offIcon ??= $attributes['off-icon'] ?? $attributes['offIcon'] ?? $__defaults['offIcon']; unset($attributes['offIcon'], $attributes['off-icon']);
unset($__defaults);
?>

<?php
// We only want to show the name attribute if it has been set manually,
// but not if it has been inferred from the wire:model attribute...
$showName = isset($name);

if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$onIcon = is_string($onIcon) && $onIcon !== '' ? $onIcon : null;
$offIcon = is_string($offIcon) && $offIcon !== '' ? $offIcon : null;

$square = $slot->isEmpty() && ! $onLabel && ! $label;
$hasIcon = $icon || $onIcon;

$iconClasses = Flux::classes()
    ->add(match ($variant) {
        'outline' => 'text-zinc-500/85 dark:text-zinc-300/80 in-data-checked:text-(--color-accent-content) dark:in-data-checked:text-(--color-accent-content)',
        'filled' => 'text-zinc-500/85 dark:text-zinc-300/80 in-data-checked:text-(--color-accent-content) dark:in-data-checked:text-(--color-accent-content)',
        'ghost' => 'text-zinc-500/85 dark:text-zinc-300/80 in-data-checked:text-(--color-accent-content) dark:in-data-checked:text-(--color-accent-content)',
        'subtle' => join(' ', [
            'text-zinc-400/90 group-hover:text-zinc-500 in-data-checked:text-zinc-500 in-data-checked:group-hover:text-zinc-800',
            'dark:text-zinc-500/90 dark:group-hover:text-zinc-400 dark:in-data-checked:text-zinc-400 dark:in-data-checked:group-hover:text-white',
        ])
    })
    ->add($square && $size !== 'xs' ? 'size-5' : 'size-4')
    ->add($attributes->pluck('icon:class'))
    ;

$classes = Flux::classes()
    ->add('group relative inline-flex items-center font-medium justify-center whitespace-nowrap outline-offset-2')
    ->add('transition touch-manipulation')
    ->add('[&[disabled]]:opacity-50 dark:[&[disabled]]:opacity-50 [&[disabled]]:shadow-none [&[disabled]]:cursor-default [&[disabled]]:pointer-events-none')
    ->add(match ($size) {
        'base' => 'h-10 text-sm rounded-lg gap-2' . ' ' . ($square ? 'w-10' : ($hasIcon ? 'ps-3 pe-4' : 'px-4')),
        'sm' => 'h-8 text-sm rounded-md gap-2' . ' ' . ($square ? 'w-8' : ($hasIcon ? 'ps-2 pe-3' : 'px-3')),
        'xs' => 'h-6 text-xs rounded-md gap-1' . ' ' . ($square ? 'w-6' : ($hasIcon ? 'ps-1 pe-2' : 'px-2')),
    })
    ->add($inset ? match ($size) {
        'base' => $square
            ? Flux::applyInset($inset, top: '-mt-2.5', right: '-me-2.5', bottom: '-mb-2.5', left: '-ms-2.5')
            : Flux::applyInset($inset, top: '-mt-2.5', right: '-me-4', bottom: '-mb-3', left: ($hasIcon ? '-ms-3' : '-ms-4')),
        'sm' => $square
            ? Flux::applyInset($inset, top: '-mt-1.5', right: '-me-1.5', bottom: '-mb-1.5', left: '-ms-1.5')
            : Flux::applyInset($inset, top: '-mt-1.5', right: '-me-3', bottom: '-mb-1.5', left: ($hasIcon ? '-ms-2' : '-ms-3')),
        'xs' => $square
            ? Flux::applyInset($inset, top: '-mt-1', right: '-me-1', bottom: '-mb-1', left: '-ms-1')
            : Flux::applyInset($inset, top: '-mt-1', right: '-me-2', bottom: '-mb-1', left: ($hasIcon ? '-ms-1' : '-ms-2')),
    } : '')
    ->add(match ($variant) {
        'outline' => 'bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75',
        'filled' => 'bg-zinc-800/5 hover:bg-zinc-800/10 dark:bg-white/10 dark:hover:bg-white/20',
        'ghost' => 'bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15',
        'subtle' => 'bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15',
    })
    ->add(match ($variant) { // Text color...
        'outline' => 'text-zinc-600/85 data-checked:text-zinc-800 dark:text-zinc-300/95 dark:data-checked:text-white',
        'filled' => 'text-zinc-600/85 data-checked:text-zinc-800 dark:text-zinc-300/95 dark:data-checked:text-white',
        'ghost' => 'text-zinc-600/85 data-checked:text-zinc-800 dark:text-zinc-300/95 dark:data-checked:text-white',
        'subtle' => join(' ', [
            'text-zinc-500/85 hover:text-zinc-500 data-checked:text-zinc-500 data-checked:hover:text-zinc-800',
            'dark:text-zinc-400/80 dark:hover:text-zinc-300 dark:data-checked:text-zinc-400 dark:data-checked:hover:text-white',
        ])
    })
    ->add(match ($variant) {
        'outline' => 'border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-600',
        default => '',
    })
    ->add(match ($variant) {
        'outline' => match ($size) {
            'base', 'sm' => 'shadow-xs',
            'xs' => 'shadow-none',
        },
        default => '',
    })
    ;
?>

<?php if (!function_exists('_63f61dd140d5a72937617a77c72e4fb7')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/accent.blade.php', $__blaze->compiledPath.'/63f61dd140d5a72937617a77c72e4fb7.php'); require $__blaze->compiledPath.'/63f61dd140d5a72937617a77c72e4fb7.php'; } ?>
<?php if (isset($__slots63f61dd140d5a72937617a77c72e4fb7)) { $__slotsStack63f61dd140d5a72937617a77c72e4fb7[] = $__slots63f61dd140d5a72937617a77c72e4fb7; } ?>
<?php if (isset($__attrs63f61dd140d5a72937617a77c72e4fb7)) { $__attrsStack63f61dd140d5a72937617a77c72e4fb7[] = $__attrs63f61dd140d5a72937617a77c72e4fb7; } ?>
<?php $__attrs63f61dd140d5a72937617a77c72e4fb7 = ['color' => $color,'class' => 'contents']; ?>
<?php $__slots63f61dd140d5a72937617a77c72e4fb7 = []; ?>
<?php $__blaze->pushData($__attrs63f61dd140d5a72937617a77c72e4fb7); ?>
<?php ob_start(); ?>
    <?php if (!function_exists('_d8f3101483a683c0a3f34bd3c7413d07')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-tooltip.blade.php', $__blaze->compiledPath.'/d8f3101483a683c0a3f34bd3c7413d07.php'); require $__blaze->compiledPath.'/d8f3101483a683c0a3f34bd3c7413d07.php'; } ?>
<?php if (isset($__slotsd8f3101483a683c0a3f34bd3c7413d07)) { $__slotsStackd8f3101483a683c0a3f34bd3c7413d07[] = $__slotsd8f3101483a683c0a3f34bd3c7413d07; } ?>
<?php if (isset($__attrsd8f3101483a683c0a3f34bd3c7413d07)) { $__attrsStackd8f3101483a683c0a3f34bd3c7413d07[] = $__attrsd8f3101483a683c0a3f34bd3c7413d07; } ?>
<?php $__attrsd8f3101483a683c0a3f34bd3c7413d07 = ['attributes' => $attributes]; ?>
<?php $__slotsd8f3101483a683c0a3f34bd3c7413d07 = []; ?>
<?php $__blaze->pushData($__attrsd8f3101483a683c0a3f34bd3c7413d07); ?>
<?php ob_start(); ?>
        <ui-switch <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> <?php if($checked): ?> checked data-checked <?php endif; ?> data-flux-control data-flux-toggle>
            <?php if ((is_string($icon) && $icon !== '') || $onIcon): ?>
                <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $onIcon ?? $icon, 'variant' => 'solid', 'class' => $iconClasses->add('hidden group-data-checked:block')]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_30c086465a738807bc38961e7a562afb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'); require $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'; } ?>
<?php $__blaze->pushData(['icon' => $onIcon ?? $icon,'variant' => 'solid','class' => $iconClasses->add('hidden group-data-checked:block')]); ?>
<?php _30c086465a738807bc38961e7a562afb($__blaze, ['icon' => $onIcon ?? $icon,'variant' => 'solid','class' => $iconClasses->add('hidden group-data-checked:block')], [], ['icon', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
                <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $offIcon ?? $onIcon ?? $icon, 'variant' => 'outline', 'class' => $iconClasses->add('group-data-checked:hidden')]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_30c086465a738807bc38961e7a562afb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'); require $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'; } ?>
<?php $__blaze->pushData(['icon' => $offIcon ?? $onIcon ?? $icon,'variant' => 'outline','class' => $iconClasses->add('group-data-checked:hidden')]); ?>
<?php _30c086465a738807bc38961e7a562afb($__blaze, ['icon' => $offIcon ?? $onIcon ?? $icon,'variant' => 'outline','class' => $iconClasses->add('group-data-checked:hidden')], [], ['icon', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
            <?php elseif ($icon): ?>
                <?php echo e($icon); ?>

            <?php endif; ?>

            <?php if ($slot->isNotEmpty() || $onLabel || $label): ?>
                <?php $onLabel = $slot->isNotEmpty() ? $slot : ($onLabel ?? $label); ?>

                <span class="group-data-checked:hidden"><?php echo e($offLabel ?? $onLabel); ?></span>
                <span class="hidden group-data-checked:block"><?php echo e($onLabel); ?></span>
            <?php endif; ?>
        </ui-switch>
    <?php $__slotsd8f3101483a683c0a3f34bd3c7413d07['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd8f3101483a683c0a3f34bd3c7413d07); ?>
<?php _d8f3101483a683c0a3f34bd3c7413d07($__blaze, $__attrsd8f3101483a683c0a3f34bd3c7413d07, $__slotsd8f3101483a683c0a3f34bd3c7413d07, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd8f3101483a683c0a3f34bd3c7413d07)) { $__slotsd8f3101483a683c0a3f34bd3c7413d07 = array_pop($__slotsStackd8f3101483a683c0a3f34bd3c7413d07); } ?>
<?php if (! empty($__attrsStackd8f3101483a683c0a3f34bd3c7413d07)) { $__attrsd8f3101483a683c0a3f34bd3c7413d07 = array_pop($__attrsStackd8f3101483a683c0a3f34bd3c7413d07); } ?>
<?php $__blaze->popData(); ?>
<?php $__slots63f61dd140d5a72937617a77c72e4fb7['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots63f61dd140d5a72937617a77c72e4fb7); ?>
<?php _63f61dd140d5a72937617a77c72e4fb7($__blaze, $__attrs63f61dd140d5a72937617a77c72e4fb7, $__slots63f61dd140d5a72937617a77c72e4fb7, ['color'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack63f61dd140d5a72937617a77c72e4fb7)) { $__slots63f61dd140d5a72937617a77c72e4fb7 = array_pop($__slotsStack63f61dd140d5a72937617a77c72e4fb7); } ?>
<?php if (! empty($__attrsStack63f61dd140d5a72937617a77c72e4fb7)) { $__attrs63f61dd140d5a72937617a77c72e4fb7 = array_pop($__attrsStack63f61dd140d5a72937617a77c72e4fb7); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\toggle.blade.php ENDPATH**/ ?>