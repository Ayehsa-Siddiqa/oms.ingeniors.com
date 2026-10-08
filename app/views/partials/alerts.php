<?php if ($message = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= e($message) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
