<?php
if (!function_exists('_cfc2bf20d24b81699d8dfb9834943769')):
function _cfc2bf20d24b81699d8dfb9834943769($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
<?php $__attrs3c5e6933b8cff67768dcf077fcc2e700 = ['attributes' => $attributes,'size' => $size === 'sm' || $size === 'xs' ? 'xs' : 'sm']; ?>
<?php $__slots3c5e6933b8cff67768dcf077fcc2e700 = []; ?>
<?php $__blaze->pushData($__attrs3c5e6933b8cff67768dcf077fcc2e700); ?>
<?php ob_start(); ?>
    <?php if (!function_exists('_eff02191728c704e42fc2caec4ea0b8e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-down.blade.php', $__blaze->compiledPath.'/eff02191728c704e42fc2caec4ea0b8e.php'); require $__blaze->compiledPath.'/eff02191728c704e42fc2caec4ea0b8e.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant]); ?>
<?php _eff02191728c704e42fc2caec4ea0b8e($__blaze, ['variant' => $iconVariant], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
<?php $__slots3c5e6933b8cff67768dcf077fcc2e700['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots3c5e6933b8cff67768dcf077fcc2e700); ?>
<?php _3c5e6933b8cff67768dcf077fcc2e700($__blaze, $__attrs3c5e6933b8cff67768dcf077fcc2e700, $__slots3c5e6933b8cff67768dcf077fcc2e700, ['attributes', 'size'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack3c5e6933b8cff67768dcf077fcc2e700)) { $__slots3c5e6933b8cff67768dcf077fcc2e700 = array_pop($__slotsStack3c5e6933b8cff67768dcf077fcc2e700); } ?>
<?php if (! empty($__attrsStack3c5e6933b8cff67768dcf077fcc2e700)) { $__attrs3c5e6933b8cff67768dcf077fcc2e700 = array_pop($__attrsStack3c5e6933b8cff67768dcf077fcc2e700); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\input\expandable.blade.php ENDPATH**/ ?>