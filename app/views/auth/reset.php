<div class="text-center mb-4">
    <img class="auth-logo-img auth-logo-dark" src="<?= asset('images/ingeniors-dark-logo.png') ?>" alt="INGENIORS logo">
    <img class="auth-logo-img auth-logo-light" src="<?= asset('images/ingeniors-light-logo.jpeg') ?>" alt="INGENIORS logo">
    <h1 class="h4 mt-3">Reset Password</h1>
    <p class="text-muted mb-0">Create a new secure password for your account.</p>
</div>
<?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
<form method="post" action="<?= url('reset-password') ?>" class="vstack gap-3">
    <?= csrf_field() ?>
    <input type="hidden" name="email" value="<?= e($email) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div>
        <label class="form-label">Email</label>
        <input class="form-control" value="<?= e($email) ?>" type="email" disabled>
    </div>
    <div>
        <label class="form-label">New Password</label>
        <div class="input-group">
            <input class="form-control" name="password" type="password" minlength="8" required autofocus placeholder="Enter new password">
            <button class="btn btn-outline-secondary toggle-password-btn" type="button" title="Show / Hide Password" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <div>
        <label class="form-label">Confirm Password</label>
        <div class="input-group">
            <input class="form-control" name="password_confirmation" type="password" minlength="8" required placeholder="Confirm new password">
            <button class="btn btn-outline-secondary toggle-password-btn" type="button" title="Show / Hide Password" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <button class="btn btn-primary w-100" type="submit">Update Password</button>
    <a class="text-center" href="<?= url('login') ?>">Back to login</a>
</form>
