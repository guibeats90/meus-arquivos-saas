<?php $__env->startPush('styles'); ?>

<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('includes.navbar_top', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>


        <form id="resetPasswordForm" action="">
            <?php echo csrf_field(); ?>
            <div class="forgotpassword-container relative">

                <div id="loadingResetPassword" class="loading-spinner">
                    <span class="spinner"></span>
                </div>

                <div class="forgotpassword">
                    <h4>REDEFINIR SENHA</h4>
                    <div class="form-group mb-3 mt-5">
                        <input id="email" name="email" type="email" class="form-control" placeholder="Digite seu email" required>
                    </div>
                    <button type="submit" class="ui-button s-conic2 text-center w-full">
                        REDEFINIR
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const loadingElement = document.getElementById('loadingResetPassword');
            loadingElement.style.display = 'block';

            const email = document.getElementById('email').value;

            fetch('<?php echo e(url('/send-reset-link')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                iziToast.show({
                    title: 'Sucesso',
                    message: data.message,
                    theme: 'dark',
                    icon: 'fa-regular fa-circle-exclamation',
                    iconColor: '#ffffff',
                    backgroundColor: '#23ab0e',
                    position: 'topRight'
                });

                document.getElementById('email').value = '';
                loadingElement.style.display = 'none';

                setTimeout(function() {
                    window.location.replace('<?php echo e(url('/')); ?>');
                }, 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                loadingElement.style.display = 'none';
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/deco7682/westgols.fun/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>