<?php
if (!function_exists('_a77758090c40ca2e6ef8223d140dbcfa')):
function _a77758090c40ca2e6ef8223d140dbcfa($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$classes = Flux::classes()
    ->add('*:data-flux-field:mb-3')
    ->add('[&>[data-flux-field]:has(>[data-flux-description])]:mb-4')
    ->add('[&>[data-flux-field]:last-child]:mb-0!')
    ;

// Support adding the .self modifier to the wire:model directive...
if (($wireModel = $attributes->wire('model')) && $wireModel->directive && ! $wireModel->hasModifier('self')) {
    unset($attributes[$wireModel->directive]);

    $wireModel->directive .= '.self';

    $attributes = $attributes->merge([$wireModel->directive => $wireModel->value]);
}
?>

<?php if (!function_exists('_8cd15635164ad0a0ace248b4cb49bc58')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php', $__blaze->compiledPath.'/8cd15635164ad0a0ace248b4cb49bc58.php'); require $__blaze->compiledPath.'/8cd15635164ad0a0ace248b4cb49bc58.php'; } ?>
<?php if (isset($__slots8cd15635164ad0a0ace248b4cb49bc58)) { $__slotsStack8cd15635164ad0a0ace248b4cb49bc58[] = $__slots8cd15635164ad0a0ace248b4cb49bc58; } ?>
<?php if (isset($__attrs8cd15635164ad0a0ace248b4cb49bc58)) { $__attrsStack8cd15635164ad0a0ace248b4cb49bc58[] = $__attrs8cd15635164ad0a0ace248b4cb49bc58; } ?>
<?php $__attrs8cd15635164ad0a0ace248b4cb49bc58 = ['attributes' => $attributes]; ?>
<?php $__slots8cd15635164ad0a0ace248b4cb49bc58 = []; ?>
<?php $__blaze->pushData($__attrs8cd15635164ad0a0ace248b4cb49bc58); ?>
<?php ob_start(); ?>
    <ui-checkbox-group <?php echo e($attributes->class($classes)); ?> data-flux-checkbox-group>
        <?php echo e($slot); ?>

    </ui-checkbox-group>
<?php $__slots8cd15635164ad0a0ace248b4cb49bc58['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots8cd15635164ad0a0ace248b4cb49bc58); ?>
<?php _8cd15635164ad0a0ace248b4cb49bc58($__blaze, $__attrs8cd15635164ad0a0ace248b4cb49bc58, $__slots8cd15635164ad0a0ace248b4cb49bc58, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack8cd15635164ad0a0ace248b4cb49bc58)) { $__slots8cd15635164ad0a0ace248b4cb49bc58 = array_pop($__slotsStack8cd15635164ad0a0ace248b4cb49bc58); } ?>
<?php if (! empty($__attrsStack8cd15635164ad0a0ace248b4cb49bc58)) { $__attrs8cd15635164ad0a0ace248b4cb49bc58 = array_pop($__attrsStack8cd15635164ad0a0ace248b4cb49bc58); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\checkbox\group\variants\default.blade.php ENDPATH**/ ?>