<?php
if (!function_exists('_b67997d2b65823b4d8e554338a00e097')):
function _b67997d2b65823b4d8e554338a00e097($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
extract(Flux::forwardedAttributes($attributes, [
    'tooltipPosition',
    'tooltipKbd',
    'tooltip',
]));
?>

<?php $tooltipPosition = $tooltipPosition ??= $attributes->pluck('tooltip:position'); ?>
<?php $tooltipKbd = $tooltipKbd ??= $attributes->pluck('tooltip:kbd'); ?>
<?php $tooltip = $tooltip ??= $attributes->pluck('tooltip'); ?>

<?php
$__defaults = [
    'tooltipPosition' => 'top',
    'tooltipKbd' => null,
    'tooltip' => null,
];
$tooltipPosition ??= $attributes['tooltip-position'] ?? $attributes['tooltipPosition'] ?? $__defaults['tooltipPosition']; unset($attributes['tooltipPosition'], $attributes['tooltip-position']);
$tooltipKbd ??= $attributes['tooltip-kbd'] ?? $attributes['tooltipKbd'] ?? $__defaults['tooltipKbd']; unset($attributes['tooltipKbd'], $attributes['tooltip-kbd']);
$tooltip ??= $attributes['tooltip'] ?? $__defaults['tooltip']; unset($attributes['tooltip']);
unset($__defaults);
?>

<?php if ($tooltip): ?>
    <?php if (!function_exists('_e8c713085d2aa5a3987cb15c838c9228')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php', $__blaze->compiledPath.'/e8c713085d2aa5a3987cb15c838c9228.php'); require $__blaze->compiledPath.'/e8c713085d2aa5a3987cb15c838c9228.php'; } ?>
<?php if (isset($__slotse8c713085d2aa5a3987cb15c838c9228)) { $__slotsStacke8c713085d2aa5a3987cb15c838c9228[] = $__slotse8c713085d2aa5a3987cb15c838c9228; } ?>
<?php if (isset($__attrse8c713085d2aa5a3987cb15c838c9228)) { $__attrsStacke8c713085d2aa5a3987cb15c838c9228[] = $__attrse8c713085d2aa5a3987cb15c838c9228; } ?>
<?php $__attrse8c713085d2aa5a3987cb15c838c9228 = ['class' => 'inline-flex','content' => $tooltip,'position' => $tooltipPosition,'kbd' => $tooltipKbd]; ?>
<?php $__slotse8c713085d2aa5a3987cb15c838c9228 = []; ?>
<?php $__blaze->pushData($__attrse8c713085d2aa5a3987cb15c838c9228); ?>
<?php ob_start(); ?>
        <?php echo e($slot); ?>

    <?php $__slotse8c713085d2aa5a3987cb15c838c9228['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotse8c713085d2aa5a3987cb15c838c9228); ?>
<?php _e8c713085d2aa5a3987cb15c838c9228($__blaze, $__attrse8c713085d2aa5a3987cb15c838c9228, $__slotse8c713085d2aa5a3987cb15c838c9228, ['content', 'position', 'kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStacke8c713085d2aa5a3987cb15c838c9228)) { $__slotse8c713085d2aa5a3987cb15c838c9228 = array_pop($__slotsStacke8c713085d2aa5a3987cb15c838c9228); } ?>
<?php if (! empty($__attrsStacke8c713085d2aa5a3987cb15c838c9228)) { $__attrse8c713085d2aa5a3987cb15c838c9228 = array_pop($__attrsStacke8c713085d2aa5a3987cb15c838c9228); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php echo e($slot); ?>

<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\with-tooltip.blade.php ENDPATH**/ ?>