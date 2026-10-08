<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-person-circle profile-icon"></i>
                <h2 class="h5 mt-3"><?= e($user['name']) ?></h2>
                <p class="text-muted mb-0"><?= e($user['role_name']) ?></p>
                <p class="text-muted"><?= e($user['email']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <form class="card" method="post" action="<?= url('profile/password') ?>">
            <?= csrf_field() ?>
            <div class="card-header"><strong>Change Password</strong></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <input class="form-control" name="password" type="password" minlength="8" required>
                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" title="Show / Hide Password" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input class="form-control" name="password_confirmation" type="password" minlength="8" required>
                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" title="Show / Hide Password" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">Update Password</button></div>
        </form>
    </div>
</div>

