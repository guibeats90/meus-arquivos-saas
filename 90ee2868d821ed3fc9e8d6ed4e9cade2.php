<?php
    $isAside = $isAside();
?>

<?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section','data' => ['aside' => $isAside,'collapsed' => $isCollapsed(),'collapsible' => $isCollapsible() && (! $isAside),'compact' => $isCompact(),'contentBefore' => $isFormBefore(),'description' => $getDescription(),'heading' => $getHeading(),'icon' => $getIcon(),'iconColor' => $getIconColor(),'iconSize' => $getIconSize(),'attributes' => 
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->merge($getExtraAlpineAttributes(), escape: false)
    ]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['aside' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isAside),'collapsed' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isCollapsed()),'collapsible' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isCollapsible() && (! $isAside)),'compact' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isCompact()),'content-before' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isFormBefore()),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($getDescription()),'heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($getHeading()),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($getIcon()),'icon-color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($getIconColor()),'icon-size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($getIconSize()),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->merge($getExtraAlpineAttributes(), escape: false)
    )]); ?>
    <?php echo e($getChildComponentContainer()); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
<?php /**PATH /home/deco7682/bet775.fun/vendor/filament/forms/src/../resources/views/components/section.blade.php ENDPATH**/ ?>