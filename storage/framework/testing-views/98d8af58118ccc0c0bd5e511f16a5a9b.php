<?php
if (!function_exists('_98d8af58118ccc0c0bd5e511f16a5a9b')):
function _98d8af58118ccc0c0bd5e511f16a5a9b($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'name' => $attributes->whereStartsWith('wire:model')->first(),
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
unset($__defaults);
?>

<?php if (!function_exists('_d1cf272b5fe4e40733145fd8acfd138f')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-inline-field.blade.php', $__blaze->compiledPath.'/d1cf272b5fe4e40733145fd8acfd138f.php'); require $__blaze->compiledPath.'/d1cf272b5fe4e40733145fd8acfd138f.php'; } ?>
<?php if (isset($__slotsd1cf272b5fe4e40733145fd8acfd138f)) { $__slotsStackd1cf272b5fe4e40733145fd8acfd138f[] = $__slotsd1cf272b5fe4e40733145fd8acfd138f; } ?>
<?php if (isset($__attrsd1cf272b5fe4e40733145fd8acfd138f)) { $__attrsStackd1cf272b5fe4e40733145fd8acfd138f[] = $__attrsd1cf272b5fe4e40733145fd8acfd138f; } ?>
<?php $__attrsd1cf272b5fe4e40733145fd8acfd138f = ['variant' => 'inline','attributes' => $attributes]; ?>
<?php $__slotsd1cf272b5fe4e40733145fd8acfd138f = []; ?>
<?php $__blaze->pushData($__attrsd1cf272b5fe4e40733145fd8acfd138f); ?>
<?php ob_start(); ?>
    
    
    
    <ui-radio <?php echo e($attributes->class('flex size-[1.125rem] rounded-full mt-px outline-offset-2')); ?> data-flux-control data-flux-radio tabindex="-1">
        <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::radio.indicator", []); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_a0ad18f13c84b7cbe1bb46345dd5f1b8')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/radio/indicator.blade.php', $__blaze->compiledPath.'/a0ad18f13c84b7cbe1bb46345dd5f1b8.php'); require $__blaze->compiledPath.'/a0ad18f13c84b7cbe1bb46345dd5f1b8.php'; } ?>
<?php $__blaze->pushData([]); ?>
<?php _a0ad18f13c84b7cbe1bb46345dd5f1b8($__blaze, [], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
    </ui-radio>
<?php $__slotsd1cf272b5fe4e40733145fd8acfd138f['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd1cf272b5fe4e40733145fd8acfd138f); ?>
<?php _d1cf272b5fe4e40733145fd8acfd138f($__blaze, $__attrsd1cf272b5fe4e40733145fd8acfd138f, $__slotsd1cf272b5fe4e40733145fd8acfd138f, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd1cf272b5fe4e40733145fd8acfd138f)) { $__slotsd1cf272b5fe4e40733145fd8acfd138f = array_pop($__slotsStackd1cf272b5fe4e40733145fd8acfd138f); } ?>
<?php if (! empty($__attrsStackd1cf272b5fe4e40733145fd8acfd138f)) { $__attrsd1cf272b5fe4e40733145fd8acfd138f = array_pop($__attrsStackd1cf272b5fe4e40733145fd8acfd138f); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\radio\variants\default.blade.php ENDPATH**/ ?>