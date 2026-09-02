<?php
if (!function_exists('__736ceba1dca996dbc02396145e43c87a')):
function __736ceba1dca996dbc02396145e43c87a($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'paginate' => null,
];
$paginate ??= $attributes['paginate'] ?? $__defaults['paginate']; unset($attributes['paginate']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('[:where(&)]:min-w-full table-fixed border-separate border-spacing-0 isolate')
    ->add('text-zinc-800')
    // We want whitespace-nowrap for the table, but not for modals and dropdowns...
    ->add('whitespace-nowrap [&_dialog]:whitespace-normal [&_[popover]]:whitespace-normal')
    ;

$containerClasses = Flux::classes()
    ->add('flex flex-col')
    ->add($attributes->pluck('container:class'))
    ;
?>

<div class="<?php echo e($containerClasses); ?>">
    <?php echo e($header ?? ''); ?>


    <ui-table-scroll-area class="overflow-auto">
        <table <?php echo e($attributes->class($classes)); ?> data-flux-table>
            <?php echo e($slot); ?>

        </table>
    </ui-table-scroll-area>

    <?php echo e($footer ?? ''); ?>


    <?php if ($paginate): ?>
        <?php $paginationAttributes = Flux::attributesAfter('pagination:', $attributes, ['paginator' => $paginate, 'class' => 'shrink-0']); ?>
        <?php if (!function_exists('__2ec2c4e3629f5e942c4dcf1696dee33c')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/pagination.blade.php', $__blaze->compiledPath.'/2ec2c4e3629f5e942c4dcf1696dee33c.php'); require $__blaze->compiledPath.'/2ec2c4e3629f5e942c4dcf1696dee33c.php'; } ?>
<?php $__blaze->pushData(['attributes' => $paginationAttributes]); ?>
<?php __2ec2c4e3629f5e942c4dcf1696dee33c($__blaze, ['attributes' => $paginationAttributes], [], ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
    <?php endif; ?>
</div>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/table/index.blade.php ENDPATH**/ ?>