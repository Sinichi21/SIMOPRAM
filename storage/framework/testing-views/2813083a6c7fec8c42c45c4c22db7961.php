<?php
if (!function_exists('_2813083a6c7fec8c42c45c4c22db7961')):
function _2813083a6c7fec8c42c45c4c22db7961($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$__defaults = [
    'animate' => null,
];
$animate ??= $attributes['animate'] ?? $__defaults['animate']; unset($attributes['animate']);
unset($__defaults);
?>

<div <?php echo e($attributes); ?> data-flux-skeleton-group>
    <?php echo e($slot); ?>

</div><?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\skeleton\group.blade.php ENDPATH**/ ?>