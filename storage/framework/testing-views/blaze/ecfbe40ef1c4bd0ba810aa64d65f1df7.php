<?php
if (!function_exists('__ecfbe40ef1c4bd0ba810aa64d65f1df7')):
function __ecfbe40ef1c4bd0ba810aa64d65f1df7($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'direction' => null,
    'sortable' => false,
    'sorted' => false,
    'align' => 'start',
    'sticky' => false,
];
$direction ??= $attributes['direction'] ?? $__defaults['direction']; unset($attributes['direction']);
$sortable ??= $attributes['sortable'] ?? $__defaults['sortable']; unset($attributes['sortable']);
$sorted ??= $attributes['sorted'] ?? $__defaults['sorted']; unset($attributes['sorted']);
$align ??= $attributes['align'] ?? $__defaults['align']; unset($attributes['align']);
$sticky ??= $attributes['sticky'] ?? $__defaults['sticky']; unset($attributes['sticky']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('py-3 px-3 first:ps-0 last:pe-0')
    ->add('text-start text-sm font-medium text-zinc-800 dark:text-white')
    ->add('border-b border-zinc-800/10 dark:border-white/20')
    ->add(match($align) {
        'center' => 'group/center-align',
        'end' => 'group/end-align',
        // Right is @deprecated but needed for backwards compatibility...
        'right' => 'group/end-align',
        default => '',
    })
    ->add($sortable ? 'group/sortable' : '')
    ->add($sticky ? [
        'z-10',
        'first:sticky first:left-0',
        'last:sticky last:right-0',
        'first:after:w-8 first:after:absolute first:after:inset-y-0 first:after:right-0 first:after:translate-x-full first:after:pointer-events-none',
        'last:after:w-8 last:after:absolute last:after:inset-y-0 last:after:left-0 last:after:-translate-x-full last:after:pointer-events-none',
        'in-data-scrolled-right:first:after:inset-shadow-[8px_0px_8px_-8px_rgba(0,0,0,0.05)]',
        'in-data-scrolled-left:last:after:inset-shadow-[-8px_0px_8px_-8px_rgba(0,0,0,0.05)]',
    ]: '')
    // If the last column is sortable, remove the right negative margin that the sortable applies to itself, as the
    // negative margin caused the last column to overflow the table creating an unnecessary horizontal scrollbar...
    ->add('**:data-flux-table-sortable:last:me-0')
    ;
?>

<th <?php echo e($attributes->class($classes)); ?> data-flux-column>
    <?php if ($sortable): ?>
        <div class="flex in-[.group\/center-align]:justify-center in-[.group\/end-align]:justify-end">
            <?php if (!function_exists('__969dbd329c0c39e4cf51712178bb7f5e')) { $__blaze->compile('D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/table/sortable.blade.php', $__blaze->compiledPath.'/969dbd329c0c39e4cf51712178bb7f5e.php'); require $__blaze->compiledPath.'/969dbd329c0c39e4cf51712178bb7f5e.php'; } ?>
<?php if (isset($__slots969dbd329c0c39e4cf51712178bb7f5e)) { $__slotsStack969dbd329c0c39e4cf51712178bb7f5e[] = $__slots969dbd329c0c39e4cf51712178bb7f5e; } ?>
<?php if (isset($__attrs969dbd329c0c39e4cf51712178bb7f5e)) { $__attrsStack969dbd329c0c39e4cf51712178bb7f5e[] = $__attrs969dbd329c0c39e4cf51712178bb7f5e; } ?>
<?php $__attrs969dbd329c0c39e4cf51712178bb7f5e = ['sorted' => $sorted,'direction' => $direction]; ?>
<?php $__slots969dbd329c0c39e4cf51712178bb7f5e = []; ?>
<?php $__blaze->pushData($__attrs969dbd329c0c39e4cf51712178bb7f5e); ?>
<?php ob_start(); ?>
                <div><?php echo e($slot); ?></div>
            <?php $__slots969dbd329c0c39e4cf51712178bb7f5e['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slots969dbd329c0c39e4cf51712178bb7f5e); ?>
<?php __969dbd329c0c39e4cf51712178bb7f5e($__blaze, $__attrs969dbd329c0c39e4cf51712178bb7f5e, $__slots969dbd329c0c39e4cf51712178bb7f5e, ['sorted', 'direction'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack969dbd329c0c39e4cf51712178bb7f5e)) { $__slots969dbd329c0c39e4cf51712178bb7f5e = array_pop($__slotsStack969dbd329c0c39e4cf51712178bb7f5e); } ?>
<?php if (! empty($__attrsStack969dbd329c0c39e4cf51712178bb7f5e)) { $__attrs969dbd329c0c39e4cf51712178bb7f5e = array_pop($__attrsStack969dbd329c0c39e4cf51712178bb7f5e); } ?>
<?php $__blaze->popData(); ?>
        </div>
    <?php else: ?>
        <div class="flex in-[.group\/center-align]:justify-center in-[.group\/end-align]:justify-end"><?php echo e($slot); ?></div>
    <?php endif; ?>
</th>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH D:\laragon\www\SIMOPRAM-1\vendor\livewire\flux\src/../stubs/resources/views/flux/table/column.blade.php ENDPATH**/ ?>