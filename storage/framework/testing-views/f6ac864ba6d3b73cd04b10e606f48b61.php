<?php # [BlazeFolded]:{flux::menu.separator}:{D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/menu/separator.blade.php}:{1788140904} ?>
<?php # [BlazeFolded]:{flux::menu.heading}:{D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/menu/heading.blade.php}:{1788140904} ?>
<?php # [BlazeFolded]:{flux::menu.separator}:{D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/menu/separator.blade.php}:{1788140904} ?>
<?php
if (!function_exists('_f6ac864ba6d3b73cd04b10e606f48b61')):
function _f6ac864ba6d3b73cd04b10e606f48b61($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'heading' => null,
];
$heading ??= $attributes['heading'] ?? $__defaults['heading']; unset($attributes['heading']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('-mx-[.3125rem] px-[.3125rem]')
    ->add('[&+&>[data-flux-menu-separator-top]]:hidden [&:first-child>[data-flux-menu-separator-top]]:hidden [&:last-child>[data-flux-menu-separator-bottom]]:hidden')
    ;
?>

<div <?php echo e($attributes->class($classes)); ?> role="group" data-flux-menu-group>
    <?php ob_start(); ?><div class="-mx-[.3125rem] my-[.3125rem] h-px" data-flux-menu-separator-top="data-flux-menu-separator-top" data-flux-menu-separator>
    <div data-orientation="horizontal" role="none" class="border-0 [print-color-adjust:exact] bg-zinc-800/15 dark:bg-white/20 h-px w-full dark:bg-zinc-600!" data-flux-separator></div>
</div>
<?php echo ltrim(ob_get_clean()); ?>

    <?php if ($heading): ?>
        <?php ob_start(); ?><div class="p-2 pb-1 w-full flex items-center text-start text-xs font-medium text-zinc-500 font-medium dark:text-zinc-300" data-flux-menu-heading>
    <div class="w-7 hidden [[data-flux-menu]:has(>[data-flux-menu-item-has-icon])_&]:block"></div>

    <div><?php ob_start(); ?><?php echo e($heading); ?><?php echo trim(ob_get_clean()); ?></div>
</div>
<?php echo ltrim(ob_get_clean()); ?>
    <?php endif; ?>

    <?php echo e($slot); ?>


    <?php ob_start(); ?><div class="-mx-[.3125rem] my-[.3125rem] h-px" data-flux-menu-separator-bottom="data-flux-menu-separator-bottom" data-flux-menu-separator>
    <div data-orientation="horizontal" role="none" class="border-0 [print-color-adjust:exact] bg-zinc-800/15 dark:bg-white/20 h-px w-full dark:bg-zinc-600!" data-flux-separator></div>
</div>
<?php echo ltrim(ob_get_clean()); ?>
</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\menu\group.blade.php ENDPATH**/ ?>