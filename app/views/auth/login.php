<div class="text-center mb-4">
    <img class="auth-logo-img auth-logo-dark" src="<?= asset('images/ingeniors-dark-logo.png') ?>" alt="INGENIORS logo">
    <img class="auth-logo-img auth-logo-light" src="<?= asset('images/ingeniors-light-logo.jpeg') ?>" alt="INGENIORS logo">
    <h1 class="h4 mt-3">Sign in to INGENIORS</h1>
    <p class="text-muted mb-0">Secure access for company operations.</p>
</div>
<?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
<form method="post" action="<?= url('login') ?>" class="vstack gap-3">
    <?= csrf_field() ?>
    <div>
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" required autofocus>
    </div>
    <div>
        <label class="form-label">Password</label>
        <div class="input-group">
            <input class="form-control" name="password" type="password" required placeholder="Enter password">
            <button class="btn btn-outline-secondary toggle-password-btn" type="button" title="Show / Hide Password" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <label class="form-check m-0"><input class="form-check-input" name="remember" type="checkbox"> Remember me</label>
        <a href="<?= url('forgot-password') ?>" class="text-decoration-none small">Forgot password?</a>
    </div>
    <button class="btn btn-primary w-100" type="submit">Login</button>
</form>
