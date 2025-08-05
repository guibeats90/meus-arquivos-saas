<?php $__env->startPush('styles'); ?>

<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('includes.navbar_left', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="page__content">
            <?php echo $__env->make('includes.navbar_top', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <div class="container">
                <h1>Lista de Notificações</h1>

                <div class="mb-5">
                    <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="notification" role="alert">
                            <div class="notification-icon">
                                <i class="fa-regular fa-bell bi flex-shrink-0 text-3xl" style="font-size: 2rem;margin-right: 10px !important;"></i>
                            </div>
                            <div class="notification-body">
                                <?php echo e($notification->data['message']); ?>

                            </div>
                            <div class="notification-time">
                                <?php echo e(\Carbon\Carbon::parse($notification->created_at)->diffForHumans()); ?>

                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <?php echo e($notifications->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/eurobetgame/htdocs/www.eurobetgame.com/resources/views/panel/notifications/index.blade.php ENDPATH**/ ?>