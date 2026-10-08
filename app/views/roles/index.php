<?php
$authUser = Auth::user();
$isSuperAdmin = ($authUser['role_name'] ?? '') === 'Super Admin';
$systemRoles  = ['Super Admin', 'Admin', 'Employee', 'User'];

$moduleMeta = [
    'dashboard'     => ['icon' => 'bi-speedometer2', 'cat' => 'Core Operations', 'desc' => 'Executive analytics, metrics & live operations'],
    'projects'      => ['icon' => 'bi-kanban', 'cat' => 'Core Operations', 'desc' => 'Engineering projects, timelines & assignments'],
    'employees'     => ['icon' => 'bi-people', 'cat' => 'Core Operations', 'desc' => 'Workforce directory, designations & team roster'],
    'departments'   => ['icon' => 'bi-diagram-3', 'cat' => 'Core Operations', 'desc' => 'Department units, leads & organizational structure'],
    'pending_tasks' => ['icon' => 'bi-list-check', 'cat' => 'Core Operations', 'desc' => 'Daily operational tasks, deadlines & completion tracking'],
    'requirements'  => ['icon' => 'bi-cart-check', 'cat' => 'Procurement & Finance', 'desc' => 'Purchase requests, vendor procurement & costs'],
    'fines'         => ['icon' => 'bi-cash-coin', 'cat' => 'Procurement & Finance', 'desc' => 'Employee penalties, fine records & payment tracking'],
    'documents'     => ['icon' => 'bi-folder2-open', 'cat' => 'Procurement & Finance', 'desc' => 'Company CAD/BIM standards, checklists & uploads'],
    'announcements' => ['icon' => 'bi-megaphone', 'cat' => 'Procurement & Finance', 'desc' => 'Internal office notices & urgent notifications'],
    'backup'        => ['icon' => 'bi-shield-check', 'cat' => 'System & Security', 'desc' => 'Recycle bin, soft-deleted records & data recovery'],
    'reports'       => ['icon' => 'bi-file-earmark-bar-graph', 'cat' => 'System & Security', 'desc' => 'Graphical reports, charts & performance analytics'],
    'settings'      => ['icon' => 'bi-gear', 'cat' => 'System & Security', 'desc' => 'Company branding, Gravity Forms webhook & config'],
    'users'         => ['icon' => 'bi-person-lock', 'cat' => 'System & Security', 'desc' => 'User accounts, credentials & status management'],
    'roles'         => ['icon' => 'bi-shield-lock', 'cat' => 'System & Security', 'desc' => 'Role creation, access matrix & security policies'],
    'profile'       => ['icon' => 'bi-person-circle', 'cat' => 'System & Security', 'desc' => 'Personal account settings & password updates'],
];

// Calculate module counts per role
$rolePermCounts = [];
foreach ($roles as $role) {
    $rId = (int)$role['id'];
    $isSuper = ($role['name'] === 'Super Admin');
    if ($isSuper) {
        $rolePermCounts[$rId] = count($allModules);
    } else {
        $count = 0;
        foreach ($allModules as $modKey => $modLabel) {
            if (!empty($rolePerms[$rId][$modKey])) {
                $count++;
            }
        }
        $rolePermCounts[$rId] = $count;
    }
}
?>

<!-- Header row -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-0 fw-bold" style="font-size:1.35rem;">
            <i class="bi bi-shield-lock me-2 text-primary"></i>Roles &amp; Permissions Management
        </h2>
        <p class="text-muted mb-0 small mt-1">Manage system roles, module access authorization, and security boundaries.</p>
    </div>
    <?php if ($isSuperAdmin): ?>
    <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal" id="btnAddRole">
        <i class="bi bi-plus-circle"></i> Add New Role
    </button>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/partials/alerts.php'; ?>

<!-- Roles Overview Cards -->
<div class="row g-3 mb-4">
<?php foreach ($roles as $role):
    $rid   = $role['id'];
    $rname = $role['name'];
    $cnt   = $userCounts[$rid] ?? 0;
    $isSys = in_array($rname, $systemRoles, true);
    $isSuper = ($rname === 'Super Admin');
    $colorMap = [
        'Super Admin' => ['bg'=>'linear-gradient(135deg,#6366f1,#8b5cf6)', 'badge'=>'badge-soft-danger', 'icon'=>'bi-gem'],
        'Admin'       => ['bg'=>'linear-gradient(135deg,#0ea5e9,#3b82f6)', 'badge'=>'badge-soft-primary', 'icon'=>'bi-shield-check'],
        'Employee'    => ['bg'=>'linear-gradient(135deg,#10b981,#34d399)', 'badge'=>'badge-soft-success', 'icon'=>'bi-person-badge'],
        'User'        => ['bg'=>'linear-gradient(135deg,#10b981,#34d399)', 'badge'=>'badge-soft-success', 'icon'=>'bi-person-check'],
    ];
    $style = $colorMap[$rname] ?? ['bg'=>'linear-gradient(135deg,#f59e0b,#fbbf24)', 'badge'=>'badge-soft-warning', 'icon'=>'bi-person-gear'];
    $permCount = $rolePermCounts[$rid] ?? 0;
    $totalMods = count($allModules);
    ?>
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden role-card" style="border-radius:14px; background: var(--oms-panel);">
            <div style="height:4px;background:<?= $style['bg'] ?>;"></div>
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width:46px;height:46px;background:<?= $style['bg'] ?>;flex-shrink:0;">
                        <i class="bi <?= $style['icon'] ?> text-white fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-truncate"><?= e($rname) ?></h6>
                            <?php if ($isSys): ?>
                                <span class="badge <?= $style['badge'] ?>" style="font-size: 10px;">System</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted d-block mt-1"><i class="bi bi-people me-1"></i><?= $cnt ?> user<?= $cnt !== 1 ? 's' : '' ?> assigned</small>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between p-2 rounded-2 mb-3 small" style="background: rgba(var(--oms-text-rgb, 128,128,128), 0.05); border: 1px solid var(--oms-border);">
                    <span class="text-muted">Module Access:</span>
                    <strong class="<?= $isSuper ? 'text-primary' : 'text-success' ?>"><?= $permCount ?> / <?= $totalMods ?> Modules</strong>
                </div>

                <?php if ($role['description']): ?>
                    <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.4;"><?= e($role['description']) ?></p>
                <?php else: ?>
                    <p class="text-muted small mb-3 flex-grow-1 fst-italic">Standard system defined role.</p>
                <?php endif; ?>

                <div class="d-flex gap-2 flex-wrap mt-auto">
                    <?php if (!$isSuper): ?>
                    <button class="btn btn-sm btn-outline-primary flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-1"
                            onclick="openPermModal(<?= $rid ?>, <?= htmlspecialchars(json_encode($rname)) ?>)"
                            id="permBtn-<?= $rid ?>">
                        <i class="bi bi-sliders"></i> Edit Permissions
                    </button>
                    <?php else: ?>
                    <span class="btn btn-sm btn-outline-secondary flex-grow-1 disabled opacity-75 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-lock-fill text-warning"></i> Full Access (Locked)
                    </span>
                    <?php endif; ?>
                    <?php if (!$isSuper && $isSuperAdmin): ?>
                    <form method="post" action="<?= url('roles-permissions/' . $rid . '/delete') ?>"
                          data-confirm="Delete role &quot;<?= e($rname) ?>&quot;? This will permanently remove it from database." class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger shadow-sm d-flex align-items-center justify-content-center" title="Delete role permanently" type="submit" style="width: 32px; height: 31px;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Permission Matrix Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px; overflow:hidden; background: var(--oms-panel);">
    <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <form class="d-flex gap-2 m-0" onsubmit="return false;">
            <div class="input-group">
                <input class="form-control" id="matrixSearchInput" placeholder="Search roles & permissions...">
                <button class="btn btn-outline-primary" type="button"><i class="bi bi-search"></i></button>
            </div>
        </form>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-print type="button"><i class="bi bi-printer me-1"></i> Print</button>
            <?php if ($isSuperAdmin): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal" type="button">
                <i class="bi bi-plus-circle me-1"></i> Add New Role
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive oms-table-wrapper m-0">
        <table class="table align-middle mb-0 matrix-table" id="permMatrix">
            <thead>
                <tr>
                    <th class="ps-4 py-3" style="min-width: 260px; width: 34%;">
                        Module
                    </th>
                    <?php foreach ($roles as $role): 
                        $rId = (int)$role['id'];
                        $isSuper = ($role['name'] === 'Super Admin');
                        $pCount = $rolePermCounts[$rId] ?? 0;
                    ?>
                        <th class="text-center py-3" style="min-width: 170px; width: <?= round(66 / max(1, count($roles))) ?>%;">
                            <div class="d-flex flex-column align-items-center gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isSuper): ?>
                                        <i class="bi bi-gem text-warning"></i>
                                    <?php elseif ($role['name'] === 'Admin'): ?>
                                        <i class="bi bi-shield-check text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-person-badge text-success"></i>
                                    <?php endif; ?>
                                    <strong class="text-body"><?= e($role['name']) ?></strong>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-secondary-subtle text-body-secondary fw-normal" style="font-size: 11px;">
                                        <?= $pCount ?> / <?= count($allModules) ?> modules
                                    </span>
                                    <?php if (!$isSuper && $isSuperAdmin): ?>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-primary text-decoration-none" style="font-size: 11px;" onclick="openPermModal(<?= $rId ?>, <?= htmlspecialchars(json_encode($role['name'])) ?>)">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allModules as $module => $label): 
                $meta = $moduleMeta[$module] ?? ['icon' => 'bi-circle', 'cat' => 'General', 'desc' => ''];
            ?>
                <tr class="matrix-module-row" data-module-name="<?= strtolower(e($label)) ?>">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="module-icon-box">
                                <i class="bi <?= $meta['icon'] ?>"></i>
                            </div>
                            <div>
                                <strong class="d-block text-body fs-6 mb-0"><?= e($label) ?></strong>
                                <small class="text-muted" style="font-size: 11.5px;"><?= e($meta['desc']) ?></small>
                            </div>
                        </div>
                    </td>
                    <?php foreach ($roles as $role): ?>
                        <?php
                        $rId       = (int)$role['id'];
                        $hasPerm   = $rolePerms[$rId][$module] ?? false;
                        $isSuper   = ($role['name'] === 'Super Admin');
                        $granted   = $isSuper || $hasPerm;
                        ?>
                        <td class="text-center py-3">
                            <?php if ($granted): ?>
                                <span class="perm-pill perm-granted shadow-sm" title="<?= e($role['name']) ?> has full access to <?= e($label) ?>">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    <span>Allowed</span>
                                </span>
                            <?php else: ?>
                                <span class="perm-pill perm-denied" title="<?= e($role['name']) ?> has no access to <?= e($label) ?>">
                                    <i class="bi bi-dash-circle me-1"></i>
                                    <span>No Access</span>
                                </span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer border-top px-4 py-3 d-flex align-items-center justify-content-between gap-4 flex-wrap" style="background: transparent;">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="d-flex align-items-center gap-1 small">
                <span class="perm-pill-dot bg-success me-1"></span> <strong>Allowed:</strong> Role has permission
            </span>
            <span class="d-flex align-items-center gap-1 small text-muted">
                <span class="perm-pill-dot bg-secondary me-1"></span> <strong>No Access:</strong> Module is restricted
            </span>
        </div>
        <div class="small text-muted">
            <i class="bi bi-shield-check text-primary me-1"></i>
            Super Admin permissions are protected and permanent.
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<?php if ($isSuperAdmin): ?>
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden; background: var(--oms-panel);">
            <form method="post" action="<?= url('roles-permissions/create') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold" id="addRoleModalLabel">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>Create New User Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Project Manager, Lead Drafter…" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Description <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief summary of duties and access level…"></textarea>
                    </div>
                    <div class="alert alert-info small mb-0 py-2 px-3 d-flex align-items-center gap-2 border-0 shadow-sm">
                        <i class="bi bi-info-circle-fill text-info fs-5"></i>
                        <div>After creating this role, configure its specific module permissions in the matrix below.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i>Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Permissions Modal -->
<div class="modal fade" id="editPermModal" tabindex="-1" aria-labelledby="editPermModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden; background: var(--oms-panel);">
            <form method="post" id="editPermForm" action="">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold" id="editPermModalLabel">
                        <i class="bi bi-sliders me-2 text-primary"></i>
                        Edit Permissions — <span id="editPermRoleName" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">Check or uncheck the modules this role can access. Changes apply immediately upon saving.</p>

                    <!-- Select All / None toolbar -->
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-success shadow-sm" id="permSelectAll">
                            <i class="bi bi-check2-all me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" id="permSelectNone">
                            <i class="bi bi-x-square me-1"></i>Clear All
                        </button>
                    </div>

                    <div class="row g-2" id="permCheckboxGrid">
                        <?php foreach ($allModules as $module => $label):
                            $meta2 = $moduleMeta[$module] ?? ['icon' => 'bi-circle', 'cat' => 'General', 'desc' => ''];
                        ?>
                        <div class="col-md-4 col-sm-6">
                            <label class="perm-toggle-card" data-module="<?= $module ?>">
                                <input type="checkbox" name="modules[]" value="<?= $module ?>" class="perm-cb visually-hidden">
                                <div class="perm-toggle-inner">
                                    <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                        <i class="bi <?= $meta2['icon'] ?> perm-toggle-icon"></i>
                                        <span class="perm-toggle-label text-truncate"><?= e($label) ?></span>
                                    </div>
                                    <span class="perm-toggle-check"><i class="bi bi-check2"></i></span>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i>Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS for modal wiring & table filter -->
<script>
const rolePermsData = <?= json_encode($rolePerms) ?>;
const baseUrl = <?= json_encode(url('roles-permissions')) ?>;

function openPermModal(roleId, roleName) {
    document.getElementById('editPermRoleName').textContent = roleName;
    document.getElementById('editPermForm').action = baseUrl + '/' + roleId + '/permissions';

    const perms = rolePermsData[roleId] || {};
    document.querySelectorAll('.perm-cb').forEach(cb => {
        const granted = perms[cb.value] === true;
        cb.checked = granted;
        updatePermCard(cb);
    });

    const modal = new bootstrap.Modal(document.getElementById('editPermModal'));
    modal.show();
}

function updatePermCard(cb) {
    const card = cb.closest('.perm-toggle-card');
    if (!card) return;
    if (cb.checked) {
        card.classList.add('active');
    } else {
        card.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.perm-cb').forEach(cb => {
        cb.addEventListener('change', function () { updatePermCard(this); });
    });

    document.getElementById('permSelectAll')?.addEventListener('click', function () {
        document.querySelectorAll('.perm-cb').forEach(cb => { cb.checked = true; updatePermCard(cb); });
    });
    document.getElementById('permSelectNone')?.addEventListener('click', function () {
        document.querySelectorAll('.perm-cb').forEach(cb => { cb.checked = false; updatePermCard(cb); });
    });

    // Matrix search filter
    var searchInput = document.getElementById('matrixSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('.matrix-module-row');
            rows.forEach(function(row) {
                var name = row.getAttribute('data-module-name') || '';
                if (q === '' || name.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<style>
/* Module Icon Box */
.module-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(var(--oms-primary-rgb, 79, 140, 255), 0.12);
    color: var(--oms-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

/* Permission Pills */
.perm-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    min-width: 96px;
    transition: all 0.15s ease;
}

.perm-granted {
    background: rgba(16, 185, 129, 0.14);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.28);
}

.perm-denied {
    background: rgba(148, 163, 184, 0.1);
    color: var(--oms-muted, #94a3b8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    opacity: 0.75;
}

.perm-pill-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

/* Matrix Table Hover & Styling */
.matrix-table tbody tr.matrix-module-row:hover td {
    background: rgba(var(--oms-primary-rgb, 79, 140, 255), 0.04);
}

.matrix-cat-row td {
    border-top: 1px solid var(--oms-border);
    border-bottom: 1px solid var(--oms-border);
}

/* Permission toggle cards */
.perm-toggle-card {
    display: block;
    cursor: pointer;
    border-radius: 10px;
    border: 1px solid var(--oms-border);
    background: var(--oms-panel);
    transition: border-color .18s, box-shadow .18s, background .18s;
}
.perm-toggle-card:hover {
    border-color: var(--oms-primary);
    box-shadow: 0 0 0 3px rgba(79, 140, 255, 0.12);
}
.perm-toggle-card.active {
    border-color: var(--oms-primary);
    background: rgba(79, 140, 255, 0.08);
}
.perm-toggle-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
}
.perm-toggle-icon {
    font-size: 1.1rem;
    color: var(--oms-muted);
    transition: color .18s;
}
.perm-toggle-card.active .perm-toggle-icon {
    color: var(--oms-primary);
}
.perm-toggle-label {
    font-size: .85rem;
    font-weight: 600;
    color: var(--oms-text);
}
.perm-toggle-check {
    width: 22px; 
    height: 22px;
    border-radius: 50%;
    border: 1.5px solid var(--oms-border);
    display: flex; 
    align-items: center; 
    justify-content: center;
    font-size: .75rem;
    transition: background .18s, border-color .18s;
    color: transparent;
    flex-shrink: 0;
}
.perm-toggle-card.active .perm-toggle-check {
    background: var(--oms-primary);
    border-color: var(--oms-primary);
    color: #fff;
}
</style>
