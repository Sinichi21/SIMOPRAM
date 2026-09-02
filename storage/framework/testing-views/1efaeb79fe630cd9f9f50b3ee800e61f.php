<?php # [BlazeFolded]:{flux::separator}:{D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/separator.blade.php}:{1788140904} ?>
<?php
if (!function_exists('_1efaeb79fe630cd9f9f50b3ee800e61f')):
function _1efaeb79fe630cd9f9f50b3ee800e61f($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<div class="-mx-[.3125rem] my-[.3125rem] h-px" <?php echo e($attributes); ?> data-flux-navmenu-separator>
    <?php ob_start(); ?><div data-orientation="horizontal" role="none" class="border-0 [print-color-adjust:exact] bg-zinc-800/15 dark:bg-white/20 h-px w-full dark:bg-zinc-600!" data-flux-separator></div>
<?php echo ltrim(ob_get_clean()); ?>
</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\stubs\resources\views\flux\navmenu\separator.blade.php ENDPATH**/ ?>