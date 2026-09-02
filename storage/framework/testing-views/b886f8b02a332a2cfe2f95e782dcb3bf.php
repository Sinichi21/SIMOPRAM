<?php
if (!function_exists('_b886f8b02a332a2cfe2f95e782dcb3bf')):
function _b886f8b02a332a2cfe2f95e782dcb3bf($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$classes = Flux::classes('[grid-area:footer]')
    ->add($attributes->has('container') ? '' : 'p-6 lg:p-8')
    ;
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-footer>
    <?php if (!function_exists('_38969385cee0c616c2147b31a14bb62a')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-container.blade.php', $__blaze->compiledPath.'/38969385cee0c616c2147b31a14bb62a.php'); require $__blaze->compiledPath.'/38969385cee0c616c2147b31a14bb62a.php'; } ?>
<?php if (isset($__slots38969385cee0c616c2147b31a14bb62a)) { $__slotsStack38969385cee0c616c2147b31a14bb62a[] = $__slots38969385cee0c616c2147b31a14bb62a; } ?>
<?php if (isset($__attrs38969385cee0c616c2147b31a14bb62a)) { $__attrsStack38969385cee0c616c2147b31a14bb62a[] = $__attrs38969385cee0c616c2147b31a14bb62a; } ?>
<?php $__attrs38969385cee0c616c2147b31a14bb62a = ['attributes' => $attributes->except('class')->class('p-6 lg:p-8')]; ?>
<?php $__slots38969385cee0c616c2147b31a14bb62a = []; ?>
<?php $__blaze->pushData($__attrs38969385cee0c616c2147b31a14bb62a); ?>
<?php ob_start(); ?>
        <?php echo e($slot); ?>

    <?php $__slots38969385cee0c616c2147b31a14bb62a['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots38969385cee0c616c2147b31a14bb62a); ?>
<?php _38969385cee0c616c2147b31a14bb62a($__blaze, $__attrs38969385cee0c616c2147b31a14bb62a, $__slots38969385cee0c616c2147b31a14bb62a, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack38969385cee0c616c2147b31a14bb62a)) { $__slots38969385cee0c616c2147b31a14bb62a = array_pop($__slotsStack38969385cee0c616c2147b31a14bb62a); } ?>
<?php if (! empty($__attrsStack38969385cee0c616c2147b31a14bb62a)) { $__attrs38969385cee0c616c2147b31a14bb62a = array_pop($__attrsStack38969385cee0c616c2147b31a14bb62a); } ?>
<?php $__blaze->popData(); ?>
</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\footer.blade.php ENDPATH**/ ?>