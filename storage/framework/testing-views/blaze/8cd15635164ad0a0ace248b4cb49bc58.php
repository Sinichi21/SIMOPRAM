<?php
if (!function_exists('__8cd15635164ad0a0ace248b4cb49bc58')):
function __8cd15635164ad0a0ace248b4cb49bc58($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
extract(Flux::forwardedAttributes($attributes, [
    'name',
    'descriptionTrailing',
    'description',
    'label',
    'badge',
]));
?>

<?php $descriptionTrailing = $descriptionTrailing ??= $attributes->pluck('description:trailing'); ?>

<?php
$__defaults = [
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'descriptionTrailing' => null,
    'description' => null,
    'label' => null,
    'badge' => null,
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$descriptionTrailing ??= $attributes['description-trailing'] ?? $attributes['descriptionTrailing'] ?? $__defaults['descriptionTrailing']; unset($attributes['descriptionTrailing'], $attributes['description-trailing']);
$description ??= $attributes['description'] ?? $__defaults['description']; unset($attributes['description']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
$badge ??= $attributes['badge'] ?? $__defaults['badge']; unset($attributes['badge']);
unset($__defaults);
?>

<?php if (isset($label) || isset($description) || isset($descriptionTrailing)): ?>
    <?php

        $fieldAttributes = Flux::attributesAfter('field:', $attributes, []);
        $labelAttributes = Flux::attributesAfter('label:', $attributes, ['badge' => $badge]);
        $descriptionAttributes = Flux::attributesAfter('description:', $attributes, []);
        $errorAttributes = Flux::attributesAfter('error:', $attributes, ['name' => $name]);
    ?>
    <?php if (!function_exists('__9ab522f4930bf1f610808ae1a9b6b610')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/field.blade.php', $__blaze->compiledPath.'/9ab522f4930bf1f610808ae1a9b6b610.php'); require $__blaze->compiledPath.'/9ab522f4930bf1f610808ae1a9b6b610.php'; } ?>
<?php if (isset($__slots9ab522f4930bf1f610808ae1a9b6b610)) { $__slotsStack9ab522f4930bf1f610808ae1a9b6b610[] = $__slots9ab522f4930bf1f610808ae1a9b6b610; } ?>
<?php if (isset($__attrs9ab522f4930bf1f610808ae1a9b6b610)) { $__attrsStack9ab522f4930bf1f610808ae1a9b6b610[] = $__attrs9ab522f4930bf1f610808ae1a9b6b610; } ?>
<?php $__attrs9ab522f4930bf1f610808ae1a9b6b610 = ['attributes' => $fieldAttributes]; ?>
<?php $__slots9ab522f4930bf1f610808ae1a9b6b610 = []; ?>
<?php $__blaze->pushData($__attrs9ab522f4930bf1f610808ae1a9b6b610); ?>
<?php ob_start(); ?>
        <?php if (isset($label)): ?>
            <?php if (!function_exists('__a0750d8e7ebbc3c3c18c5bf30dfe2b2b')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/label.blade.php', $__blaze->compiledPath.'/a0750d8e7ebbc3c3c18c5bf30dfe2b2b.php'); require $__blaze->compiledPath.'/a0750d8e7ebbc3c3c18c5bf30dfe2b2b.php'; } ?>
<?php if (isset($__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b)) { $__slotsStacka0750d8e7ebbc3c3c18c5bf30dfe2b2b[] = $__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b; } ?>
<?php if (isset($__attrsa0750d8e7ebbc3c3c18c5bf30dfe2b2b)) { $__attrsStacka0750d8e7ebbc3c3c18c5bf30dfe2b2b[] = $__attrsa0750d8e7ebbc3c3c18c5bf30dfe2b2b; } ?>
<?php $__attrsa0750d8e7ebbc3c3c18c5bf30dfe2b2b = ['attributes' => $labelAttributes]; ?>
<?php $__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b = []; ?>
<?php $__blaze->pushData($__attrsa0750d8e7ebbc3c3c18c5bf30dfe2b2b); ?>
<?php ob_start(); ?><?php echo e($label); ?><?php $__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b); ?>
<?php __a0750d8e7ebbc3c3c18c5bf30dfe2b2b($__blaze, $__attrsa0750d8e7ebbc3c3c18c5bf30dfe2b2b, $__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStacka0750d8e7ebbc3c3c18c5bf30dfe2b2b)) { $__slotsa0750d8e7ebbc3c3c18c5bf30dfe2b2b = array_pop($__slotsStacka0750d8e7ebbc3c3c18c5bf30dfe2b2b); } ?>
<?php if (! empty($__attrsStacka0750d8e7ebbc3c3c18c5bf30dfe2b2b)) { $__attrsa0750d8e7ebbc3c3c18c5bf30dfe2b2b = array_pop($__attrsStacka0750d8e7ebbc3c3c18c5bf30dfe2b2b); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>

        <?php if (isset($description)): ?>
            <?php if (!function_exists('__62503a92f5ae80ac86dcc08db7fb04a0')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/description.blade.php', $__blaze->compiledPath.'/62503a92f5ae80ac86dcc08db7fb04a0.php'); require $__blaze->compiledPath.'/62503a92f5ae80ac86dcc08db7fb04a0.php'; } ?>
<?php if (isset($__slots62503a92f5ae80ac86dcc08db7fb04a0)) { $__slotsStack62503a92f5ae80ac86dcc08db7fb04a0[] = $__slots62503a92f5ae80ac86dcc08db7fb04a0; } ?>
<?php if (isset($__attrs62503a92f5ae80ac86dcc08db7fb04a0)) { $__attrsStack62503a92f5ae80ac86dcc08db7fb04a0[] = $__attrs62503a92f5ae80ac86dcc08db7fb04a0; } ?>
<?php $__attrs62503a92f5ae80ac86dcc08db7fb04a0 = ['attributes' => $descriptionAttributes]; ?>
<?php $__slots62503a92f5ae80ac86dcc08db7fb04a0 = []; ?>
<?php $__blaze->pushData($__attrs62503a92f5ae80ac86dcc08db7fb04a0); ?>
<?php ob_start(); ?><?php echo e($description); ?><?php $__slots62503a92f5ae80ac86dcc08db7fb04a0['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots62503a92f5ae80ac86dcc08db7fb04a0); ?>
<?php __62503a92f5ae80ac86dcc08db7fb04a0($__blaze, $__attrs62503a92f5ae80ac86dcc08db7fb04a0, $__slots62503a92f5ae80ac86dcc08db7fb04a0, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack62503a92f5ae80ac86dcc08db7fb04a0)) { $__slots62503a92f5ae80ac86dcc08db7fb04a0 = array_pop($__slotsStack62503a92f5ae80ac86dcc08db7fb04a0); } ?>
<?php if (! empty($__attrsStack62503a92f5ae80ac86dcc08db7fb04a0)) { $__attrs62503a92f5ae80ac86dcc08db7fb04a0 = array_pop($__attrsStack62503a92f5ae80ac86dcc08db7fb04a0); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>

        <?php echo e($slot); ?>


        
        [STARTCOMPILEDUNBLAZE:zfIvMuuHg5]<?php \Livewire\Blaze\Unblaze::storeScope("zfIvMuuHg5", scope: ['attributes' => $errorAttributes->getAttributes()]) ?><?php \Livewire\Blaze\Unblaze::storeReplacement("zfIvMuuHg5", "CiAgICAgICAgPGZsdXg6ZXJyb3IgOmF0dHJpYnV0ZXM9Im5ldyBcSWxsdW1pbmF0ZVxWaWV3XENvbXBvbmVudEF0dHJpYnV0ZUJhZygkc2NvcGVbJ2F0dHJpYnV0ZXMnXSkiIC8+CiAgICAgICAg") ?>[ENDCOMPILEDUNBLAZE:zfIvMuuHg5]

        <?php if (isset($descriptionTrailing)): ?>
            <?php if (!function_exists('__62503a92f5ae80ac86dcc08db7fb04a0')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/description.blade.php', $__blaze->compiledPath.'/62503a92f5ae80ac86dcc08db7fb04a0.php'); require $__blaze->compiledPath.'/62503a92f5ae80ac86dcc08db7fb04a0.php'; } ?>
<?php if (isset($__slots62503a92f5ae80ac86dcc08db7fb04a0)) { $__slotsStack62503a92f5ae80ac86dcc08db7fb04a0[] = $__slots62503a92f5ae80ac86dcc08db7fb04a0; } ?>
<?php if (isset($__attrs62503a92f5ae80ac86dcc08db7fb04a0)) { $__attrsStack62503a92f5ae80ac86dcc08db7fb04a0[] = $__attrs62503a92f5ae80ac86dcc08db7fb04a0; } ?>
<?php $__attrs62503a92f5ae80ac86dcc08db7fb04a0 = ['attributes' => $descriptionAttributes]; ?>
<?php $__slots62503a92f5ae80ac86dcc08db7fb04a0 = []; ?>
<?php $__blaze->pushData($__attrs62503a92f5ae80ac86dcc08db7fb04a0); ?>
<?php ob_start(); ?><?php echo e($descriptionTrailing); ?><?php $__slots62503a92f5ae80ac86dcc08db7fb04a0['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots62503a92f5ae80ac86dcc08db7fb04a0); ?>
<?php __62503a92f5ae80ac86dcc08db7fb04a0($__blaze, $__attrs62503a92f5ae80ac86dcc08db7fb04a0, $__slots62503a92f5ae80ac86dcc08db7fb04a0, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack62503a92f5ae80ac86dcc08db7fb04a0)) { $__slots62503a92f5ae80ac86dcc08db7fb04a0 = array_pop($__slotsStack62503a92f5ae80ac86dcc08db7fb04a0); } ?>
<?php if (! empty($__attrsStack62503a92f5ae80ac86dcc08db7fb04a0)) { $__attrs62503a92f5ae80ac86dcc08db7fb04a0 = array_pop($__attrsStack62503a92f5ae80ac86dcc08db7fb04a0); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>
    <?php $__slots9ab522f4930bf1f610808ae1a9b6b610['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots9ab522f4930bf1f610808ae1a9b6b610); ?>
<?php __9ab522f4930bf1f610808ae1a9b6b610($__blaze, $__attrs9ab522f4930bf1f610808ae1a9b6b610, $__slots9ab522f4930bf1f610808ae1a9b6b610, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack9ab522f4930bf1f610808ae1a9b6b610)) { $__slots9ab522f4930bf1f610808ae1a9b6b610 = array_pop($__slotsStack9ab522f4930bf1f610808ae1a9b6b610); } ?>
<?php if (! empty($__attrsStack9ab522f4930bf1f610808ae1a9b6b610)) { $__attrs9ab522f4930bf1f610808ae1a9b6b610 = array_pop($__attrsStack9ab522f4930bf1f610808ae1a9b6b610); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php echo e($slot); ?>

<?php endif; ?>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php ENDPATH**/ ?>