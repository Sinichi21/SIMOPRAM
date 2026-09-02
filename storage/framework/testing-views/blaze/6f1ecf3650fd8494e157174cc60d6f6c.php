<?php
if (!function_exists('__6f1ecf3650fd8494e157174cc60d6f6c')):
function __6f1ecf3650fd8494e157174cc60d6f6c($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'position' => 'bottom end',
    'expanded' => false,
];
$position ??= $attributes['position'] ?? $__defaults['position']; unset($attributes['position']);
$expanded ??= $attributes['expanded'] ?? $__defaults['expanded']; unset($attributes['expanded']);
unset($__defaults);
?>

<ui-toast-group x-data x-on:toast-show.document="$el.showToast($event.detail)" popover="manual" position="<?php echo e($position); ?>" <?php echo e($expanded ? 'expanded' : ''); ?> wire:ignore>
    <?php echo e($slot); ?>

</ui-toast-group>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/toast/group.blade.php ENDPATH**/ ?>