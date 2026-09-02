<?php
if (!function_exists('_e0d1cf0f93bdaaed7b5c8e2e0c8c2480')):
function _e0d1cf0f93bdaaed7b5c8e2e0c8c2480($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'name' => null,
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
unset($__defaults);
?>

<?php
// We only want to show the name attribute on the checkbox if it has been set
// manually, but not if it has been set from the wire:model attribute...
$showName = isset($name);

if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$classes = Flux::classes()
    ->add('flex size-[1.125rem] rounded-[.3rem] mt-px outline-offset-2')
    ;
?>

<?php if (!function_exists('_d1cf272b5fe4e40733145fd8acfd138f')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-inline-field.blade.php', $__blaze->compiledPath.'/d1cf272b5fe4e40733145fd8acfd138f.php'); require $__blaze->compiledPath.'/d1cf272b5fe4e40733145fd8acfd138f.php'; } ?>
<?php if (isset($__slotsd1cf272b5fe4e40733145fd8acfd138f)) { $__slotsStackd1cf272b5fe4e40733145fd8acfd138f[] = $__slotsd1cf272b5fe4e40733145fd8acfd138f; } ?>
<?php if (isset($__attrsd1cf272b5fe4e40733145fd8acfd138f)) { $__attrsStackd1cf272b5fe4e40733145fd8acfd138f[] = $__attrsd1cf272b5fe4e40733145fd8acfd138f; } ?>
<?php $__attrsd1cf272b5fe4e40733145fd8acfd138f = ['attributes' => $attributes]; ?>
<?php $__slotsd1cf272b5fe4e40733145fd8acfd138f = []; ?>
<?php $__blaze->pushData($__attrsd1cf272b5fe4e40733145fd8acfd138f); ?>
<?php ob_start(); ?>
    <ui-checkbox <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> data-flux-control data-flux-checkbox>
        <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::checkbox.indicator", []); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_4ccd22de28afad50349e366edd1743d1')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/checkbox/indicator.blade.php', $__blaze->compiledPath.'/4ccd22de28afad50349e366edd1743d1.php'); require $__blaze->compiledPath.'/4ccd22de28afad50349e366edd1743d1.php'; } ?>
<?php $__blaze->pushData([]); ?>
<?php _4ccd22de28afad50349e366edd1743d1($__blaze, [], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
    </ui-checkbox>
<?php $__slotsd1cf272b5fe4e40733145fd8acfd138f['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd1cf272b5fe4e40733145fd8acfd138f); ?>
<?php _d1cf272b5fe4e40733145fd8acfd138f($__blaze, $__attrsd1cf272b5fe4e40733145fd8acfd138f, $__slotsd1cf272b5fe4e40733145fd8acfd138f, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd1cf272b5fe4e40733145fd8acfd138f)) { $__slotsd1cf272b5fe4e40733145fd8acfd138f = array_pop($__slotsStackd1cf272b5fe4e40733145fd8acfd138f); } ?>
<?php if (! empty($__attrsStackd1cf272b5fe4e40733145fd8acfd138f)) { $__attrsd1cf272b5fe4e40733145fd8acfd138f = array_pop($__attrsStackd1cf272b5fe4e40733145fd8acfd138f); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/checkbox/variants/default.blade.php ENDPATH**/ ?>