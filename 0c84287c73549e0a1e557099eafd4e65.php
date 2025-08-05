<?php
    use Filament\Tables\Columns\IconColumn\IconColumnSize;
?>

<div
    <?php echo e($attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-ta-icon flex flex-wrap gap-1.5',
                'px-3 py-4' => ! $isInline(),
                'flex-col' => $isListWithLineBreaks(),
            ])); ?>

>
    <?php $__currentLoopData = \Illuminate\Support\Arr::wrap($getState()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $state): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($icon = $getIcon($state)): ?>
            <?php
                $color = $getColor($state) ?? 'gray';
                $size = $getSize($state) ?? IconColumnSize::Large;
            ?>

            <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $icon,'class' => \Illuminate\Support\Arr::toCssClasses([
                    'fi-ta-icon-item',
                    match ($size) {
                        IconColumnSize::ExtraSmall, 'xs' => 'fi-ta-icon-item-size-xs h-3 w-3',
                        IconColumnSize::Small, 'sm' => 'fi-ta-icon-item-size-sm h-4 w-4',
                        IconColumnSize::Medium, 'md' => 'fi-ta-icon-item-size-md h-5 w-5',
                        IconColumnSize::Large, 'lg' => 'fi-ta-icon-item-size-lg h-6 w-6',
                        IconColumnSize::ExtraLarge, 'xl' => 'fi-ta-icon-item-size-xl h-7 w-7',
                        default => $size,
                    },
                    match ($color) {
                        'gray' => 'text-gray-400 dark:text-gray-500',
                        default => 'text-custom-500 dark:text-custom-400',
                    },
                ]),'style' => \Illuminate\Support\Arr::toCssStyles([
                    \Filament\Support\get_color_css_variables($color, shades: [400, 500]) => $color !== 'gray',
                ])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses([
                    'fi-ta-icon-item',
                    match ($size) {
                        IconColumnSize::ExtraSmall, 'xs' => 'fi-ta-icon-item-size-xs h-3 w-3',
                        IconColumnSize::Small, 'sm' => 'fi-ta-icon-item-size-sm h-4 w-4',
                        IconColumnSize::Medium, 'md' => 'fi-ta-icon-item-size-md h-5 w-5',
                        IconColumnSize::Large, 'lg' => 'fi-ta-icon-item-size-lg h-6 w-6',
                        IconColumnSize::ExtraLarge, 'xl' => 'fi-ta-icon-item-size-xl h-7 w-7',
                        default => $size,
                    },
                    match ($color) {
                        'gray' => 'text-gray-400 dark:text-gray-500',
                        default => 'text-custom-500 dark:text-custom-400',
                    },
                ])),'style' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssStyles([
                    \Filament\Support\get_color_css_variables($color, shades: [400, 500]) => $color !== 'gray',
                ]))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /home/deco7682/bet775.fun/vendor/filament/tables/src/../resources/views/columns/icon-column.blade.php ENDPATH**/ ?>