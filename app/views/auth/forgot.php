<div class="text-center mb-4">
    <img class="auth-logo-img auth-logo-dark" src="<?= asset('images/ingeniors-dark-logo.png') ?>" alt="INGENIORS logo">
    <img class="auth-logo-img auth-logo-light" src="<?= asset('images/ingeniors-light-logo.jpeg') ?>" alt="INGENIORS logo">
    <h1 class="h4 mt-3">Forgot Password</h1>
    <p class="text-muted mb-0">Enter your email to receive a password reset link.</p>
</div>
<?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
<form method="post" action="<?= url('forgot-password') ?>" class="vstack gap-3">
    <?= csrf_field() ?>
    <div>
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Send Reset Link</button>
    <a class="text-center" href="<?= url('login') ?>">Back to login</a>
</form>
