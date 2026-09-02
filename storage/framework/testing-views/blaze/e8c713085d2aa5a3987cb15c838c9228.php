<?php
if (!function_exists('__e8c713085d2aa5a3987cb15c838c9228')):
function __e8c713085d2aa5a3987cb15c838c9228($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'interactive' => null,
    'position' => 'top',
    'align' => 'center',
    'content' => null,
    'kbd' => null,
    'toggleable' => null,
];
$interactive ??= $attributes['interactive'] ?? $__defaults['interactive']; unset($attributes['interactive']);
$position ??= $attributes['position'] ?? $__defaults['position']; unset($attributes['position']);
$align ??= $attributes['align'] ?? $__defaults['align']; unset($attributes['align']);
$content ??= $attributes['content'] ?? $__defaults['content']; unset($attributes['content']);
$kbd ??= $attributes['kbd'] ?? $__defaults['kbd']; unset($attributes['kbd']);
$toggleable ??= $attributes['toggleable'] ?? $__defaults['toggleable']; unset($attributes['toggleable']);
unset($__defaults);
?>

<?php
// Support adding the .self modifier to the wire:model directive...
if (($wireModel = $attributes->wire('model')) && $wireModel->directive && ! $wireModel->hasModifier('self')) {
    unset($attributes[$wireModel->directive]);

    $wireModel->directive .= '.self';

    $attributes = $attributes->merge([$wireModel->directive => $wireModel->value]);
}
?>

<?php if ($toggleable): ?>
    <ui-dropdown position="<?php echo e($position); ?> <?php echo e($align); ?>" <?php echo e($attributes); ?> data-flux-tooltip>
        <?php echo e($slot); ?>


        <?php if ($content !== null): ?>
            <?php if (!function_exists('__92b9e845f5a344ac25cde8a0c6e5637d')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/content.blade.php', $__blaze->compiledPath.'/92b9e845f5a344ac25cde8a0c6e5637d.php'); require $__blaze->compiledPath.'/92b9e845f5a344ac25cde8a0c6e5637d.php'; } ?>
<?php if (isset($__slots92b9e845f5a344ac25cde8a0c6e5637d)) { $__slotsStack92b9e845f5a344ac25cde8a0c6e5637d[] = $__slots92b9e845f5a344ac25cde8a0c6e5637d; } ?>
<?php if (isset($__attrs92b9e845f5a344ac25cde8a0c6e5637d)) { $__attrsStack92b9e845f5a344ac25cde8a0c6e5637d[] = $__attrs92b9e845f5a344ac25cde8a0c6e5637d; } ?>
<?php $__attrs92b9e845f5a344ac25cde8a0c6e5637d = ['kbd' => $kbd]; ?>
<?php $__slots92b9e845f5a344ac25cde8a0c6e5637d = []; ?>
<?php $__blaze->pushData($__attrs92b9e845f5a344ac25cde8a0c6e5637d); ?>
<?php ob_start(); ?><?php echo e($content); ?><?php $__slots92b9e845f5a344ac25cde8a0c6e5637d['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots92b9e845f5a344ac25cde8a0c6e5637d); ?>
<?php __92b9e845f5a344ac25cde8a0c6e5637d($__blaze, $__attrs92b9e845f5a344ac25cde8a0c6e5637d, $__slots92b9e845f5a344ac25cde8a0c6e5637d, ['kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack92b9e845f5a344ac25cde8a0c6e5637d)) { $__slots92b9e845f5a344ac25cde8a0c6e5637d = array_pop($__slotsStack92b9e845f5a344ac25cde8a0c6e5637d); } ?>
<?php if (! empty($__attrsStack92b9e845f5a344ac25cde8a0c6e5637d)) { $__attrs92b9e845f5a344ac25cde8a0c6e5637d = array_pop($__attrsStack92b9e845f5a344ac25cde8a0c6e5637d); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>
    </ui-dropdown>
<?php else: ?>
    <ui-tooltip position="<?php echo e($position); ?> <?php echo e($align); ?>" <?php echo e($attributes); ?> data-flux-tooltip <?php if($interactive): ?> interactive <?php endif; ?>>
        <?php echo e($slot); ?>


        <?php if ($content !== null): ?>
            <?php if (!function_exists('__92b9e845f5a344ac25cde8a0c6e5637d')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/content.blade.php', $__blaze->compiledPath.'/92b9e845f5a344ac25cde8a0c6e5637d.php'); require $__blaze->compiledPath.'/92b9e845f5a344ac25cde8a0c6e5637d.php'; } ?>
<?php if (isset($__slots92b9e845f5a344ac25cde8a0c6e5637d)) { $__slotsStack92b9e845f5a344ac25cde8a0c6e5637d[] = $__slots92b9e845f5a344ac25cde8a0c6e5637d; } ?>
<?php if (isset($__attrs92b9e845f5a344ac25cde8a0c6e5637d)) { $__attrsStack92b9e845f5a344ac25cde8a0c6e5637d[] = $__attrs92b9e845f5a344ac25cde8a0c6e5637d; } ?>
<?php $__attrs92b9e845f5a344ac25cde8a0c6e5637d = ['kbd' => $kbd]; ?>
<?php $__slots92b9e845f5a344ac25cde8a0c6e5637d = []; ?>
<?php $__blaze->pushData($__attrs92b9e845f5a344ac25cde8a0c6e5637d); ?>
<?php ob_start(); ?><?php echo e($content); ?><?php $__slots92b9e845f5a344ac25cde8a0c6e5637d['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots92b9e845f5a344ac25cde8a0c6e5637d); ?>
<?php __92b9e845f5a344ac25cde8a0c6e5637d($__blaze, $__attrs92b9e845f5a344ac25cde8a0c6e5637d, $__slots92b9e845f5a344ac25cde8a0c6e5637d, ['kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack92b9e845f5a344ac25cde8a0c6e5637d)) { $__slots92b9e845f5a344ac25cde8a0c6e5637d = array_pop($__slotsStack92b9e845f5a344ac25cde8a0c6e5637d); } ?>
<?php if (! empty($__attrsStack92b9e845f5a344ac25cde8a0c6e5637d)) { $__attrs92b9e845f5a344ac25cde8a0c6e5637d = array_pop($__attrsStack92b9e845f5a344ac25cde8a0c6e5637d); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>
    </ui-tooltip>
<?php endif; ?>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php ENDPATH**/ ?>