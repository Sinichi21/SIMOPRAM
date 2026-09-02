<?php
if (!function_exists('__268d2d7a7128a59354c7776fd48a5831')):
function __268d2d7a7128a59354c7776fd48a5831($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'key' => null,
    'sticky' => false,
];
$key ??= $attributes['key'] ?? $__defaults['key']; unset($attributes['key']);
$sticky ??= $attributes['sticky'] ?? $__defaults['sticky']; unset($attributes['sticky']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add($sticky ? 'last:sticky last:bottom-0 last:z-20' : '')
    ;
?>

<tr <?php if($key): ?> wire:key="table-<?php echo e($key); ?>" <?php endif; ?> <?php echo e($attributes->class($classes)); ?> data-flux-row>
    <?php echo e($slot); ?>

</tr>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/table/row.blade.php ENDPATH**/ ?>