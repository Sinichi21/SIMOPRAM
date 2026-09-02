<?php
if (!function_exists('_44c2bd08d751a603816500e94619c214')):
function _44c2bd08d751a603816500e94619c214($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'name' => $attributes->whereStartsWith('wire:model')->first(),
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('w-full flex')
    ->add('*:data-flux-input:grow')
    ->add([
        // With the external borders, let's always make sure the first and last children have outside borders.
        // For internal borders, we will ensure that all left borders are removed, but the right borders remain.
        // But when there is a input groupsuffix, then there should be no right internal border.
        // That way we shouldn't ever have a double border...

        // All inputs borders...
        '[&>[data-flux-input]:last-child:not(:first-child)>[data-flux-group-target]:not([data-invalid])]:border-s-0',
        '[&>[data-flux-input]:not(:first-child):not(:last-child)>[data-flux-group-target]:not([data-invalid])]:border-s-0',
        '[&>[data-flux-input]:has(+[data-flux-input-group-suffix])>[data-flux-group-target]:not([data-invalid])]:border-e-0',

        // Selects and date pickers borders...
        '[&>*:last-child:not(:first-child)>[data-flux-group-target]:not([data-invalid])]:border-s-0',
        '[&>*:not(:first-child):not(:last-child)>[data-flux-group-target]:not([data-invalid])]:border-s-0',
        '[&>*:has(+[data-flux-input-group-suffix])>[data-flux-group-target]:not([data-invalid])]:border-e-0',

        // Buttons borders...
        '[&>[data-flux-group-target]:last-child:not(:first-child)]:border-s-0',
        '[&>[data-flux-group-target]:not(:first-child):not(:last-child)]:border-s-0',
        '[&>[data-flux-group-target]:has(+[data-flux-input-group-suffix])]:border-e-0',

        // "Weld" the borders of inputs together by overriding their border radiuses...
        '[&>[data-flux-group-target]:not(:first-child):not(:last-child)]:rounded-none',
        '[&>[data-flux-group-target]:first-child:not(:last-child)]:rounded-e-none',
        '[&>[data-flux-group-target]:last-child:not(:first-child)]:rounded-s-none',

        // "Weld" borders for sub-children of group targets (button element inside ui-select element, etc.)...
        '[&>*:not(:first-child):not(:last-child):not(:only-child)>[data-flux-group-target]]:rounded-none',
        '[&>*:first-child:not(:last-child)>[data-flux-group-target]]:rounded-e-none',
        '[&>*:last-child:not(:first-child)>[data-flux-group-target]]:rounded-s-none',

        // "Weld" borders for sub-sub-children of group targets (input element inside div inside ui-select element (combobox))...
        '[&>*:not(:first-child):not(:last-child):not(:only-child)>[data-flux-input]>[data-flux-group-target]]:rounded-none',
        '[&>*:first-child:not(:last-child)>[data-flux-input]>[data-flux-group-target]]:rounded-e-none',
        '[&>*:last-child:not(:first-child)>[data-flux-input]>[data-flux-group-target]]:rounded-s-none',

        // "Weld" borders for sub-children wrapped in tooltips or time picker triggers...
        '[&>*:not(:first-child):not(:last-child):not(:only-child)>:is([data-flux-tooltip],ui-time-picker-trigger)>[data-flux-group-target]]:rounded-none',
        '[&>*:first-child:not(:last-child)>:is([data-flux-tooltip],ui-time-picker-trigger)>[data-flux-group-target]]:rounded-e-none',
        '[&>*:last-child:not(:first-child)>:is([data-flux-tooltip],ui-time-picker-trigger)>[data-flux-group-target]]:rounded-s-none',

        // Borders for sub-children wrapped in tooltips or time picker triggers...
        '[&>*:last-child:not(:first-child)>:is([data-flux-tooltip],ui-time-picker-trigger)>[data-flux-group-target]:not([data-invalid])]:border-s-0',
        '[&>*:not(:first-child):not(:last-child)>:is([data-flux-tooltip],ui-time-picker-trigger)>[data-flux-group-target]:not([data-invalid])]:border-s-0',
        '[&>*:has(+[data-flux-input-group-suffix])>:is([data-flux-tooltip],ui-time-picker-trigger)>[data-flux-group-target]:not([data-invalid])]:border-e-0',
    ])
    ;
?>

<?php if (!function_exists('_8cd15635164ad0a0ace248b4cb49bc58')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php', $__blaze->compiledPath.'/8cd15635164ad0a0ace248b4cb49bc58.php'); require $__blaze->compiledPath.'/8cd15635164ad0a0ace248b4cb49bc58.php'; } ?>
<?php if (isset($__slots8cd15635164ad0a0ace248b4cb49bc58)) { $__slotsStack8cd15635164ad0a0ace248b4cb49bc58[] = $__slots8cd15635164ad0a0ace248b4cb49bc58; } ?>
<?php if (isset($__attrs8cd15635164ad0a0ace248b4cb49bc58)) { $__attrsStack8cd15635164ad0a0ace248b4cb49bc58[] = $__attrs8cd15635164ad0a0ace248b4cb49bc58; } ?>
<?php $__attrs8cd15635164ad0a0ace248b4cb49bc58 = ['attributes' => $attributes,'name' => $name]; ?>
<?php $__slots8cd15635164ad0a0ace248b4cb49bc58 = []; ?>
<?php $__blaze->pushData($__attrs8cd15635164ad0a0ace248b4cb49bc58); ?>
<?php ob_start(); ?>
    <div <?php echo e($attributes->class($classes)); ?> data-flux-input-group>
        <?php echo e($slot); ?>

    </div>
<?php $__slots8cd15635164ad0a0ace248b4cb49bc58['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots8cd15635164ad0a0ace248b4cb49bc58); ?>
<?php _8cd15635164ad0a0ace248b4cb49bc58($__blaze, $__attrs8cd15635164ad0a0ace248b4cb49bc58, $__slots8cd15635164ad0a0ace248b4cb49bc58, ['attributes', 'name'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack8cd15635164ad0a0ace248b4cb49bc58)) { $__slots8cd15635164ad0a0ace248b4cb49bc58 = array_pop($__slotsStack8cd15635164ad0a0ace248b4cb49bc58); } ?>
<?php if (! empty($__attrsStack8cd15635164ad0a0ace248b4cb49bc58)) { $__attrs8cd15635164ad0a0ace248b4cb49bc58 = array_pop($__attrsStack8cd15635164ad0a0ace248b4cb49bc58); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\input\group\index.blade.php ENDPATH**/ ?>