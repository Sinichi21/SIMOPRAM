<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'sidebar' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'sidebar' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sidebar): ?>
    <?php if (!function_exists('_795a1c08325d967c63cb3db6fe082f45')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/brand.blade.php', $__blaze->compiledPath.'/795a1c08325d967c63cb3db6fe082f45.php'); require $__blaze->compiledPath.'/795a1c08325d967c63cb3db6fe082f45.php'; } ?>
<?php if (isset($__slots795a1c08325d967c63cb3db6fe082f45)) { $__slotsStack795a1c08325d967c63cb3db6fe082f45[] = $__slots795a1c08325d967c63cb3db6fe082f45; } ?>
<?php if (isset($__attrs795a1c08325d967c63cb3db6fe082f45)) { $__attrsStack795a1c08325d967c63cb3db6fe082f45[] = $__attrs795a1c08325d967c63cb3db6fe082f45; } ?>
<?php $__attrs795a1c08325d967c63cb3db6fe082f45 = ['name' => config('app.name', 'Laravel'),'attributes' => $attributes]; ?>
<?php $__slots795a1c08325d967c63cb3db6fe082f45 = []; ?>
<?php $__blaze->pushData($__attrs795a1c08325d967c63cb3db6fe082f45); ?>
<?php ob_start(); ?>
         <?php ob_start(); ?>
            <?php if (isset($component)) { $__componentOriginal159d6670770cb479b1921cea6416c26c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal159d6670770cb479b1921cea6416c26c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo-icon','data' => ['class' => 'size-5 fill-current text-white dark:text-black']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 fill-current text-white dark:text-black']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $attributes = $__attributesOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__attributesOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $component = $__componentOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__componentOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
        <?php $__slots795a1c08325d967c63cb3db6fe082f45['logo'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), ['class' => 'flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground']); ?>
    <?php $__slots795a1c08325d967c63cb3db6fe082f45['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots795a1c08325d967c63cb3db6fe082f45); ?>
<?php _795a1c08325d967c63cb3db6fe082f45($__blaze, $__attrs795a1c08325d967c63cb3db6fe082f45, $__slots795a1c08325d967c63cb3db6fe082f45, ['name', 'attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack795a1c08325d967c63cb3db6fe082f45)) { $__slots795a1c08325d967c63cb3db6fe082f45 = array_pop($__slotsStack795a1c08325d967c63cb3db6fe082f45); } ?>
<?php if (! empty($__attrsStack795a1c08325d967c63cb3db6fe082f45)) { $__attrs795a1c08325d967c63cb3db6fe082f45 = array_pop($__attrsStack795a1c08325d967c63cb3db6fe082f45); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php if (!function_exists('_23bb28674d38f9eabcb85e4a1e7c12bb')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/brand.blade.php', $__blaze->compiledPath.'/23bb28674d38f9eabcb85e4a1e7c12bb.php'); require $__blaze->compiledPath.'/23bb28674d38f9eabcb85e4a1e7c12bb.php'; } ?>
<?php if (isset($__slots23bb28674d38f9eabcb85e4a1e7c12bb)) { $__slotsStack23bb28674d38f9eabcb85e4a1e7c12bb[] = $__slots23bb28674d38f9eabcb85e4a1e7c12bb; } ?>
<?php if (isset($__attrs23bb28674d38f9eabcb85e4a1e7c12bb)) { $__attrsStack23bb28674d38f9eabcb85e4a1e7c12bb[] = $__attrs23bb28674d38f9eabcb85e4a1e7c12bb; } ?>
<?php $__attrs23bb28674d38f9eabcb85e4a1e7c12bb = ['name' => config('app.name', 'Laravel'),'attributes' => $attributes]; ?>
<?php $__slots23bb28674d38f9eabcb85e4a1e7c12bb = []; ?>
<?php $__blaze->pushData($__attrs23bb28674d38f9eabcb85e4a1e7c12bb); ?>
<?php ob_start(); ?>
         <?php ob_start(); ?>
            <?php if (isset($component)) { $__componentOriginal159d6670770cb479b1921cea6416c26c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal159d6670770cb479b1921cea6416c26c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo-icon','data' => ['class' => 'size-5 fill-current text-white dark:text-black']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 fill-current text-white dark:text-black']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $attributes = $__attributesOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__attributesOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $component = $__componentOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__componentOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
        <?php $__slots23bb28674d38f9eabcb85e4a1e7c12bb['logo'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), ['class' => 'flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground']); ?>
    <?php $__slots23bb28674d38f9eabcb85e4a1e7c12bb['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots23bb28674d38f9eabcb85e4a1e7c12bb); ?>
<?php _23bb28674d38f9eabcb85e4a1e7c12bb($__blaze, $__attrs23bb28674d38f9eabcb85e4a1e7c12bb, $__slots23bb28674d38f9eabcb85e4a1e7c12bb, ['name', 'attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack23bb28674d38f9eabcb85e4a1e7c12bb)) { $__slots23bb28674d38f9eabcb85e4a1e7c12bb = array_pop($__slotsStack23bb28674d38f9eabcb85e4a1e7c12bb); } ?>
<?php if (! empty($__attrsStack23bb28674d38f9eabcb85e4a1e7c12bb)) { $__attrs23bb28674d38f9eabcb85e4a1e7c12bb = array_pop($__attrsStack23bb28674d38f9eabcb85e4a1e7c12bb); } ?>
<?php $__blaze->popData(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\laragon\www\SIMOPRAM-1\resources\views/components/app-logo.blade.php ENDPATH**/ ?>