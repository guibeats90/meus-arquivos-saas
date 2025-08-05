<?php $__env->startPush('styles'); ?>

<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('includes.navbar_left', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="page__content">
            <?php echo $__env->make('includes.navbar_top', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <div class="row">
                <div class="col-lg-6">
                    <h2><img src="<?php echo e(asset('storage/'.$category->image)); ?>" alt="<?php echo e($category->name); ?>" class="mr-2" width="30"> <?php echo e($category->name); ?></h2>
                </div>
                <div class="col-lg-6"></div>
            </div>

            <div class="row mt-3">
                <?php $__currentLoopData = $games; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-xl-2 col-lg-2 col-md-3 col-6 caixa-loop-elementos">
                        <a href="<?php echo e(route('web.game.index', ['slug' => $game->slug])); ?>" class="inner-loop-elementos">
                            <img src="<?php echo e(asset('storage/'.$game->image)); ?>" alt="<?php echo e($game->name); ?>" class="img-fluid rounded-3">
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </div>

            <div class="">
                <div class="col-lg-6"></div>
                <div class="col-lg-6">
                    <?php echo e($games->links()); ?>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/deco7682/westgols.fun/resources/views/web/categories/index.blade.php ENDPATH**/ ?>