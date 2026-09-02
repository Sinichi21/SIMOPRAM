<?php
if (!function_exists('_b50365e25e07488c4368dcc49575d1ee')):
function _b50365e25e07488c4368dcc49575d1ee($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'align' => 'right',
    'checked' => null
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$align ??= $attributes['align'] ?? $__defaults['align']; unset($attributes['align']);
$checked ??= $attributes['checked'] ?? $__defaults['checked']; unset($attributes['checked']);
unset($__defaults);
?>

<?php
// We only want to show the name attribute it has been set manually
// but not if it has been set from the `wire:model` attribute...
$showName = isset($name);
if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$classes = Flux::classes()
    ->add('group h-5 w-8 min-w-8 relative inline-flex items-center outline-offset-2')
    ->add('rounded-full')
    ->add('transition')
    ->add('bg-zinc-800/15 [&[disabled]]:opacity-50 dark:bg-transparent dark:border dark:border-white/20 dark:[&[disabled]]:border-white/10')
    ->add('[print-color-adjust:exact]')
    ->add([
        'data-checked:bg-(--color-accent)',
        'data-checked:border-0',
    ])
    ;

$indicatorClasses = Flux::classes()
    ->add('size-3.5')
    ->add('rounded-full')
    ->add('transition translate-x-[0.1875rem] dark:translate-x-[0.125rem] rtl:-translate-x-[0.1875rem] dark:rtl:-translate-x-[0.125rem]')
    ->add('bg-white')
    ->add([
        'group-data-checked:translate-x-[0.9375rem] rtl:group-data-checked:-translate-x-[0.9375rem]',
        // We have to add the dark variant of the `translate-x-[0.9375rem]` to ensure that if `.dark` is added to an element mid way
        // down the DOM instead of on the root HTML element, that the above `dark:translate-x-[0.125rem]` doesn't over ride it...
        'dark:group-data-checked:translate-x-[0.9375rem] dark:rtl:group-data-checked:-translate-x-[0.9375rem]',
        'group-data-checked:bg-(--color-accent-foreground)',
    ]);
?>

<?php if ($align === 'left' || $align === 'start'): ?>
    <?php if (!function_exists('_d1cf272b5fe4e40733145fd8acfd138f')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-inline-field.blade.php', $__blaze->compiledPath.'/d1cf272b5fe4e40733145fd8acfd138f.php'); require $__blaze->compiledPath.'/d1cf272b5fe4e40733145fd8acfd138f.php'; } ?>
<?php if (isset($__slotsd1cf272b5fe4e40733145fd8acfd138f)) { $__slotsStackd1cf272b5fe4e40733145fd8acfd138f[] = $__slotsd1cf272b5fe4e40733145fd8acfd138f; } ?>
<?php if (isset($__attrsd1cf272b5fe4e40733145fd8acfd138f)) { $__attrsStackd1cf272b5fe4e40733145fd8acfd138f[] = $__attrsd1cf272b5fe4e40733145fd8acfd138f; } ?>
<?php $__attrsd1cf272b5fe4e40733145fd8acfd138f = ['attributes' => $attributes]; ?>
<?php $__slotsd1cf272b5fe4e40733145fd8acfd138f = []; ?>
<?php $__blaze->pushData($__attrsd1cf272b5fe4e40733145fd8acfd138f); ?>
<?php ob_start(); ?>
        <ui-switch <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> <?php if($checked): ?> checked data-checked <?php endif; ?> data-flux-control data-flux-switch>
            <span class="<?php echo e(\Illuminate\Support\Arr::toCssClasses($indicatorClasses)); ?>"></span>
        </ui-switch>
    <?php $__slotsd1cf272b5fe4e40733145fd8acfd138f['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd1cf272b5fe4e40733145fd8acfd138f); ?>
<?php _d1cf272b5fe4e40733145fd8acfd138f($__blaze, $__attrsd1cf272b5fe4e40733145fd8acfd138f, $__slotsd1cf272b5fe4e40733145fd8acfd138f, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd1cf272b5fe4e40733145fd8acfd138f)) { $__slotsd1cf272b5fe4e40733145fd8acfd138f = array_pop($__slotsStackd1cf272b5fe4e40733145fd8acfd138f); } ?>
<?php if (! empty($__attrsStackd1cf272b5fe4e40733145fd8acfd138f)) { $__attrsd1cf272b5fe4e40733145fd8acfd138f = array_pop($__attrsStackd1cf272b5fe4e40733145fd8acfd138f); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php if (!function_exists('_cc9a51537dd25bca170d77c4168f2913')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/with-reversed-inline-field.blade.php', $__blaze->compiledPath.'/cc9a51537dd25bca170d77c4168f2913.php'); require $__blaze->compiledPath.'/cc9a51537dd25bca170d77c4168f2913.php'; } ?>
<?php if (isset($__slotscc9a51537dd25bca170d77c4168f2913)) { $__slotsStackcc9a51537dd25bca170d77c4168f2913[] = $__slotscc9a51537dd25bca170d77c4168f2913; } ?>
<?php if (isset($__attrscc9a51537dd25bca170d77c4168f2913)) { $__attrsStackcc9a51537dd25bca170d77c4168f2913[] = $__attrscc9a51537dd25bca170d77c4168f2913; } ?>
<?php $__attrscc9a51537dd25bca170d77c4168f2913 = ['attributes' => $attributes]; ?>
<?php $__slotscc9a51537dd25bca170d77c4168f2913 = []; ?>
<?php $__blaze->pushData($__attrscc9a51537dd25bca170d77c4168f2913); ?>
<?php ob_start(); ?>
        <ui-switch <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> <?php if($checked): ?> checked data-checked <?php endif; ?> data-flux-control data-flux-switch>
            <span class="<?php echo e(\Illuminate\Support\Arr::toCssClasses($indicatorClasses)); ?>"></span>
        </ui-switch>
    <?php $__slotscc9a51537dd25bca170d77c4168f2913['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotscc9a51537dd25bca170d77c4168f2913); ?>
<?php _cc9a51537dd25bca170d77c4168f2913($__blaze, $__attrscc9a51537dd25bca170d77c4168f2913, $__slotscc9a51537dd25bca170d77c4168f2913, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackcc9a51537dd25bca170d77c4168f2913)) { $__slotscc9a51537dd25bca170d77c4168f2913 = array_pop($__slotsStackcc9a51537dd25bca170d77c4168f2913); } ?>
<?php if (! empty($__attrsStackcc9a51537dd25bca170d77c4168f2913)) { $__attrscc9a51537dd25bca170d77c4168f2913 = array_pop($__attrsStackcc9a51537dd25bca170d77c4168f2913); } ?>
<?php $__blaze->popData(); ?>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\switch.blade.php ENDPATH**/ ?>