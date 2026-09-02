<?php
if (!function_exists('_4d77fc4afbd32d21d9cd8542bbdf10d0')):
function _4d77fc4afbd32d21d9cd8542bbdf10d0($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'iconVariant' => 'mini',
    'iconTrailing' => null,
    'variant' => 'default',
    'disabled' => false,
    'indent' => false,
    'suffix' => null,
    'value' => null,
    'icon' => null,
    'kbd' => null,
];
$iconVariant ??= $attributes['icon-variant'] ?? $attributes['iconVariant'] ?? $__defaults['iconVariant']; unset($attributes['iconVariant'], $attributes['icon-variant']);
$iconTrailing ??= $attributes['icon-trailing'] ?? $attributes['iconTrailing'] ?? $__defaults['iconTrailing']; unset($attributes['iconTrailing'], $attributes['icon-trailing']);
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$disabled ??= $attributes['disabled'] ?? $__defaults['disabled']; unset($attributes['disabled']);
$indent ??= $attributes['indent'] ?? $__defaults['indent']; unset($attributes['indent']);
$suffix ??= $attributes['suffix'] ?? $__defaults['suffix']; unset($attributes['suffix']);
$value ??= $attributes['value'] ?? $__defaults['value']; unset($attributes['value']);
$icon ??= $attributes['icon'] ?? $__defaults['icon']; unset($attributes['icon']);
$kbd ??= $attributes['kbd'] ?? $__defaults['kbd']; unset($attributes['kbd']);
unset($__defaults);
?>

<?php
if ($kbd) $suffix = $kbd;

$iconClasses = Flux::classes()
    ->add('me-2')
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5' : null)
    ;

$trailingIconClasses = Flux::classes()
    ->add('ms-auto')
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5' : null)
    ;

$classes = Flux::classes()
    ->add('group flex items-center px-2 py-2 lg:py-1.5 w-full')
    ->add('rounded-md')
    ->add('text-start text-sm font-medium')
    ->add(match ($variant) {
        'danger' => [
            'text-zinc-800 hover:text-red-600 hover:bg-red-50 dark:text-white dark:hover:bg-red-400/20 dark:hover:text-red-400',
            '**:data-navmenu-icon:text-zinc-400 dark:**:data-navmenu-icon:text-white/60 [&:hover_[data-navmenu-icon]]:text-current',
        ],
        'default' => [
            'text-zinc-800 hover:bg-zinc-50 dark:text-white dark:hover:bg-zinc-600',
            '**:data-navmenu-icon:text-zinc-400 dark:**:data-navmenu-icon:text-white/60 [&:hover_[data-navmenu-icon]]:text-current',
        ]
    })
    ->add($disabled ? 'text-zinc-400' : '')
    ;
?>

<?php if (!function_exists('_0e5cde04e1ccdd4ef673892b673d1443')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/button-or-link.blade.php', $__blaze->compiledPath.'/0e5cde04e1ccdd4ef673892b673d1443.php'); require $__blaze->compiledPath.'/0e5cde04e1ccdd4ef673892b673d1443.php'; } ?>
<?php if (isset($__slots0e5cde04e1ccdd4ef673892b673d1443)) { $__slotsStack0e5cde04e1ccdd4ef673892b673d1443[] = $__slots0e5cde04e1ccdd4ef673892b673d1443; } ?>
<?php if (isset($__attrs0e5cde04e1ccdd4ef673892b673d1443)) { $__attrsStack0e5cde04e1ccdd4ef673892b673d1443[] = $__attrs0e5cde04e1ccdd4ef673892b673d1443; } ?>
<?php $__attrs0e5cde04e1ccdd4ef673892b673d1443 = ['attributes' => $attributes->class($classes),'dataFluxNavmenuItem' => true]; ?>
<?php $__slots0e5cde04e1ccdd4ef673892b673d1443 = []; ?>
<?php $__blaze->pushData($__attrs0e5cde04e1ccdd4ef673892b673d1443); ?>
<?php ob_start(); ?>
    <?php if ($indent): ?>
        <div class="w-7"></div>
    <?php endif; ?>

    <?php if (is_string($icon) && $icon !== ''): ?>
        <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $icon, 'variant' => $iconVariant, 'class' => $iconClasses, 'dataNavmenuIcon' => true]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_30c086465a738807bc38961e7a562afb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'); require $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'; } ?>
<?php $__blaze->pushData(['icon' => $icon,'variant' => $iconVariant,'class' => $iconClasses,'dataNavmenuIcon' => true]); ?>
<?php _30c086465a738807bc38961e7a562afb($__blaze, ['icon' => $icon,'variant' => $iconVariant,'class' => $iconClasses,'dataNavmenuIcon' => true], [], ['icon', 'variant', 'class', 'dataNavmenuIcon'], ['dataNavmenuIcon' => 'data-navmenu-icon'], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
    <?php elseif ($icon): ?>
        <?php echo e($icon); ?>

    <?php endif; ?>

    <?php echo e($slot); ?>


    <?php if ($suffix): ?>
        <?php if (is_string($suffix)): ?>
            <div class="ms-auto opacity-50 text-xs">
                <?php echo e($suffix); ?>

            </div>
        <?php else: ?>
            <?php echo e($suffix); ?>

        <?php endif; ?>
    <?php endif; ?>

    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
        <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $iconTrailing, 'variant' => $iconVariant, 'class' => $trailingIconClasses]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_30c086465a738807bc38961e7a562afb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'); require $__blaze->compiledPath.'/30c086465a738807bc38961e7a562afb.php'; } ?>
<?php $__blaze->pushData(['icon' => $iconTrailing,'variant' => $iconVariant,'class' => $trailingIconClasses]); ?>
<?php _30c086465a738807bc38961e7a562afb($__blaze, ['icon' => $iconTrailing,'variant' => $iconVariant,'class' => $trailingIconClasses], [], ['icon', 'variant', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
    <?php elseif ($iconTrailing): ?>
        <?php echo e($iconTrailing); ?>

    <?php endif; ?>
<?php $__slots0e5cde04e1ccdd4ef673892b673d1443['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots0e5cde04e1ccdd4ef673892b673d1443); ?>
<?php _0e5cde04e1ccdd4ef673892b673d1443($__blaze, $__attrs0e5cde04e1ccdd4ef673892b673d1443, $__slots0e5cde04e1ccdd4ef673892b673d1443, ['attributes', 'dataFluxNavmenuItem'], ['dataFluxNavmenuItem' => 'data-flux-navmenu-item'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack0e5cde04e1ccdd4ef673892b673d1443)) { $__slots0e5cde04e1ccdd4ef673892b673d1443 = array_pop($__slotsStack0e5cde04e1ccdd4ef673892b673d1443); } ?>
<?php if (! empty($__attrsStack0e5cde04e1ccdd4ef673892b673d1443)) { $__attrs0e5cde04e1ccdd4ef673892b673d1443 = array_pop($__attrsStack0e5cde04e1ccdd4ef673892b673d1443); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\navmenu\item.blade.php ENDPATH**/ ?>