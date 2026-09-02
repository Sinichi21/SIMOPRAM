<?php
if (!function_exists('_a7d05a9e0b254f081e53a48e262000bb')):
function _a7d05a9e0b254f081e53a48e262000bb($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'shortcut' => null,
    'name' => null,
];
$shortcut ??= $attributes['shortcut'] ?? $__defaults['shortcut']; unset($attributes['shortcut']);
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
unset($__defaults);
?>

<div
    <?php echo e($attributes->class('contents')); ?>

    x-data
    x-on:click="$el.querySelector('button[disabled]') || $dispatch('modal-show', { name: '<?php echo e($name); ?>' })"
    <?php if($shortcut): ?>
        x-on:keydown.<?php echo e($shortcut); ?>.document="$event.preventDefault(); $dispatch('modal-show', { name: '<?php echo e($name); ?>' })"
    <?php endif; ?>
    data-flux-modal-trigger
>
    <?php echo e($slot); ?>

</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\modal\trigger.blade.php ENDPATH**/ ?>