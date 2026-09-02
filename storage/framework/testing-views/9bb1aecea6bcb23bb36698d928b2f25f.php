<?php
if (!function_exists('_9bb1aecea6bcb23bb36698d928b2f25f')):
function _9bb1aecea6bcb23bb36698d928b2f25f($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php if (!function_exists('_33d1e0bd3435e7fbb0b2a13ba35bfe3f')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/checkbox/index.blade.php', $__blaze->compiledPath.'/33d1e0bd3435e7fbb0b2a13ba35bfe3f.php'); require $__blaze->compiledPath.'/33d1e0bd3435e7fbb0b2a13ba35bfe3f.php'; } ?>
<?php $__blaze->pushData(['all' => true,'attributes' => $attributes]); ?>
<?php _33d1e0bd3435e7fbb0b2a13ba35bfe3f($__blaze, ['all' => true,'attributes' => $attributes], [], ['all', 'attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\checkbox\all.blade.php ENDPATH**/ ?>