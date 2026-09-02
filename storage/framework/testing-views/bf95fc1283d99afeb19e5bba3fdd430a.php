<?php
if (!function_exists('_bf95fc1283d99afeb19e5bba3fdd430a')):
function _bf95fc1283d99afeb19e5bba3fdd430a($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$__defaults = [
    'iconVariant' => 'mini',
    'size' => null,
];
$iconVariant ??= $attributes['icon-variant'] ?? $attributes['iconVariant'] ?? $__defaults['iconVariant']; unset($attributes['iconVariant'], $attributes['icon-variant']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
unset($__defaults);
?>

<?php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-1',
    'square' => true,
    'size' => null,
]);
?>

<?php if (!function_exists('_3c5e6933b8cff67768dcf077fcc2e700')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/button/index.blade.php', $__blaze->compiledPath.'/3c5e6933b8cff67768dcf077fcc2e700.php'); require $__blaze->compiledPath.'/3c5e6933b8cff67768dcf077fcc2e700.php'; } ?>
<?php if (isset($__slots3c5e6933b8cff67768dcf077fcc2e700)) { $__slotsStack3c5e6933b8cff67768dcf077fcc2e700[] = $__slots3c5e6933b8cff67768dcf077fcc2e700; } ?>
<?php if (isset($__attrs3c5e6933b8cff67768dcf077fcc2e700)) { $__attrsStack3c5e6933b8cff67768dcf077fcc2e700[] = $__attrs3c5e6933b8cff67768dcf077fcc2e700; } ?>
<?php $__attrs3c5e6933b8cff67768dcf077fcc2e700 = ['attributes' => $attributes,'size' => $size === 'sm' || $size === 'xs' ? 'xs' : 'sm','xData' => 'fluxInputViewable','xOn:click' => 'toggle()','xBind:dataViewableOpen' => 'open','ariaLabel' => e(__('Toggle password visibility'))]; ?>
<?php $__slots3c5e6933b8cff67768dcf077fcc2e700 = []; ?>
<?php $__blaze->pushData($__attrs3c5e6933b8cff67768dcf077fcc2e700); ?>
<?php ob_start(); ?>
    <?php if (!function_exists('_201777b3f7bf1aea2448d4572d6eafed')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/eye-slash.blade.php', $__blaze->compiledPath.'/201777b3f7bf1aea2448d4572d6eafed.php'); require $__blaze->compiledPath.'/201777b3f7bf1aea2448d4572d6eafed.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant,'class' => 'hidden [[data-viewable-open]>&]:block']); ?>
<?php _201777b3f7bf1aea2448d4572d6eafed($__blaze, ['variant' => $iconVariant,'class' => 'hidden [[data-viewable-open]>&]:block'], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
    <?php if (!function_exists('_68066b4ebc6ab53f48859adcc8546595')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/eye.blade.php', $__blaze->compiledPath.'/68066b4ebc6ab53f48859adcc8546595.php'); require $__blaze->compiledPath.'/68066b4ebc6ab53f48859adcc8546595.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant,'class' => 'block [[data-viewable-open]>&]:hidden']); ?>
<?php _68066b4ebc6ab53f48859adcc8546595($__blaze, ['variant' => $iconVariant,'class' => 'block [[data-viewable-open]>&]:hidden'], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
<?php $__slots3c5e6933b8cff67768dcf077fcc2e700['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots3c5e6933b8cff67768dcf077fcc2e700); ?>
<?php _3c5e6933b8cff67768dcf077fcc2e700($__blaze, $__attrs3c5e6933b8cff67768dcf077fcc2e700, $__slots3c5e6933b8cff67768dcf077fcc2e700, ['attributes', 'size'], ['xData' => 'x-data', 'xOn:click' => 'x-on:click', 'xBind:dataViewableOpen' => 'x-bind:data-viewable-open', 'ariaLabel' => 'aria-label'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack3c5e6933b8cff67768dcf077fcc2e700)) { $__slots3c5e6933b8cff67768dcf077fcc2e700 = array_pop($__slotsStack3c5e6933b8cff67768dcf077fcc2e700); } ?>
<?php if (! empty($__attrsStack3c5e6933b8cff67768dcf077fcc2e700)) { $__attrs3c5e6933b8cff67768dcf077fcc2e700 = array_pop($__attrsStack3c5e6933b8cff67768dcf077fcc2e700); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\input\viewable.blade.php ENDPATH**/ ?>