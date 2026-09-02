<?php
if (!function_exists('_d6a90b657f065293fb5b6f4e25619db3')):
function _d6a90b657f065293fb5b6f4e25619db3($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'length' => null,
    'private' => false,
];
$length ??= $attributes['length'] ?? $__defaults['length']; unset($attributes['length']);
$private ??= $attributes['private'] ?? $__defaults['private']; unset($attributes['private']);
unset($__defaults);
?>

<?php
    $classes = Flux::classes()
        ->add('flex items-center gap-2 isolate w-fit')
        ->add('[&_[data-flux-input-group]]:w-auto')
?>

<?php if (!function_exists('_8cd15635164ad0a0ace248b4cb49bc58')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php', $__blaze->compiledPath.'/8cd15635164ad0a0ace248b4cb49bc58.php'); require $__blaze->compiledPath.'/8cd15635164ad0a0ace248b4cb49bc58.php'; } ?>
<?php if (isset($__slots8cd15635164ad0a0ace248b4cb49bc58)) { $__slotsStack8cd15635164ad0a0ace248b4cb49bc58[] = $__slots8cd15635164ad0a0ace248b4cb49bc58; } ?>
<?php if (isset($__attrs8cd15635164ad0a0ace248b4cb49bc58)) { $__attrsStack8cd15635164ad0a0ace248b4cb49bc58[] = $__attrs8cd15635164ad0a0ace248b4cb49bc58; } ?>
<?php $__attrs8cd15635164ad0a0ace248b4cb49bc58 = ['attributes' => $attributes]; ?>
<?php $__slots8cd15635164ad0a0ace248b4cb49bc58 = []; ?>
<?php $__blaze->pushData($__attrs8cd15635164ad0a0ace248b4cb49bc58); ?>
<?php ob_start(); ?>
    <ui-otp
        <?php echo e($attributes->class($classes)); ?>

        data-flux-otp
        data-flux-control
        role="group"
        data-flux-input-aria-label="<?php echo e(__('Character {current} of {total}')); ?>"
    >
        <?php if($slot->isEmpty() && $length): ?>
            <?php for($i = 0; $i < $length; $i++): ?>
                <?php if (!function_exists('_b6063a88efcee5d02f0eb2a206ede293')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/otp/input.blade.php', $__blaze->compiledPath.'/b6063a88efcee5d02f0eb2a206ede293.php'); require $__blaze->compiledPath.'/b6063a88efcee5d02f0eb2a206ede293.php'; } ?>
<?php $__blaze->pushData([]); ?>
<?php _b6063a88efcee5d02f0eb2a206ede293($__blaze, [], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
            <?php endfor; ?>
        <?php else: ?>
            <?php echo e($slot); ?>

        <?php endif; ?>
    </ui-otp>
<?php $__slots8cd15635164ad0a0ace248b4cb49bc58['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots8cd15635164ad0a0ace248b4cb49bc58); ?>
<?php _8cd15635164ad0a0ace248b4cb49bc58($__blaze, $__attrs8cd15635164ad0a0ace248b4cb49bc58, $__slots8cd15635164ad0a0ace248b4cb49bc58, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack8cd15635164ad0a0ace248b4cb49bc58)) { $__slots8cd15635164ad0a0ace248b4cb49bc58 = array_pop($__slotsStack8cd15635164ad0a0ace248b4cb49bc58); } ?>
<?php if (! empty($__attrsStack8cd15635164ad0a0ace248b4cb49bc58)) { $__attrs8cd15635164ad0a0ace248b4cb49bc58 = array_pop($__attrsStack8cd15635164ad0a0ace248b4cb49bc58); } ?>
<?php $__blaze->popData(); ?><?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\otp\index.blade.php ENDPATH**/ ?>