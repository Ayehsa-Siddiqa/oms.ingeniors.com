<?php
$currentModuleConfig = $tabs[$activeTab]['config'] ?? null;
$currentResult = $tabs[$activeTab]['result'] ?? ['rows' => [], 'total' => 0, 'pages' => 1];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-primary"></i> Backup
        </h4>
        <p class="text-muted mb-0 small">Restore removed items or permanently delete them. Items are auto-purged from DB based on retention policies (Requirements & Documents: 7 days, Projects, Tasks & Fines: 30 days, Employees & Users: kept until manual deletion).</p>
    </div>
</div>

<!-- Module Tabs -->
<div class="card mb-4">
    <div class="card-header border-bottom-0 pb-0">
        <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-x-auto">
            <?php foreach ($tabs as $slug => $tabData): ?>
                <?php $count = (int) ($tabData['result']['total'] ?? 0); ?>
                <li class="nav-item">
                    <a class="nav-link text-nowrap <?= $slug === $activeTab ? 'active fw-bold' : '' ?>" href="<?= url('backup?tab=' . $slug . ($query !== '' ? '&q=' . urlencode($query) : '')) ?>">
                        <?= e($tabData['config']['title']) ?>
                        <?php if ($count > 0): ?>
                            <span class="badge rounded-pill <?= $slug === $activeTab ? 'bg-danger text-white' : 'bg-secondary-subtle text-body' ?> ms-1"><?= $count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Active Tab Content -->
<?php if ($currentModuleConfig): ?>
    <div class="card" id="recycleBinTableCard">
        <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <form class="d-flex gap-2" method="get">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                <div class="input-group">
                    <input class="form-control" name="q" value="<?= e($query) ?>" placeholder="Search deleted <?= strtolower(e($currentModuleConfig['title'])) ?>...">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($query !== ''): ?>
                        <a class="btn btn-outline-secondary" href="<?= url('recycle-bin?tab=' . $activeTab) ?>"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
            <div class="text-muted small">
                Showing <strong><?= count($currentResult['rows']) ?></strong> of <strong><?= (int)$currentResult['total'] ?></strong> deleted <?= strtolower(e($currentModuleConfig['title'])) ?>
            </div>
        </div>

        <?php
        $visibleFields = array_filter($currentModuleConfig['fields'], fn($field) => ($field['list'] ?? true) === true);
        $headers = [];
        foreach ($visibleFields as $name => $field) {
            $thClass = '';
            if (in_array($name, ['title', 'project_name', 'task_title', 'department_name', 'item', 'name', 'employee_name'])) {
                $thClass = 'col-title';
            } elseif (in_array($name, ['description', 'desc', 'content', 'remarks', 'notes', 'reason', 'summary']) || ($field['type'] ?? '') === 'textarea') {
                $thClass = 'col-desc';
            }
            $headers[] = [
                'label' => $field['label'],
                'class' => $thClass,
                'attrs' => 'data-col="' . e($name) . '"'
            ];
        }
        $headers[] = 'Deleted At';
        $headers[] = ['label' => 'Actions', 'class' => 'text-end'];

        $formatCell = function ($name, $field, $val) {
            if ($val === null || $val === '') {
                return '<span class="text-muted">—</span>';
            }
            if (($field['type'] ?? '') === 'file') {
                $ext = strtolower(pathinfo((string)$val, PATHINFO_EXTENSION));
                $icon = match($ext) {
                    'pdf' => 'bi-file-earmark-pdf text-danger',
                    'doc', 'docx', 'dot', 'dotx', 'docm', 'rtf' => 'bi-file-earmark-word text-primary',
                    'xls', 'xlsx', 'xlsm', 'xlsb', 'csv' => 'bi-file-earmark-excel text-success',
                    'ppt', 'pptx', 'pps', 'ppsx', 'pot', 'potx' => 'bi-file-earmark-ppt text-warning',
                    'png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'svg' => 'bi-file-earmark-image text-info',
                    'zip', 'rar', '7z', 'tar', 'gz' => 'bi-file-earmark-zip text-secondary',
                    'txt', 'md', 'log', 'json', 'xml' => 'bi-file-earmark-text text-body',
                    default => 'bi-file-earmark text-primary',
                };
                return '<a class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap d-inline-flex align-items-center gap-1" href="' . asset('uploads/' . $val) . '" download target="_blank"><i class="bi ' . $icon . '"></i><span>Download</span></a>';
            }
            if (in_array($name, ['description', 'desc', 'content', 'remarks', 'notes', 'reason', 'summary']) || ($field['type'] ?? '') === 'textarea') {
                return '<div class="cell-desc">' . nl2br(e($val)) . '</div>';
            }
            if (in_array($name, ['title', 'project_name', 'task_title', 'department_name', 'item', 'name', 'employee_name'])) {
                return '<strong class="cell-title d-inline-block">' . e($val) . '</strong>';
            }
            if ($name === 'paid') {
                $isPaid = strtolower((string)$val) === 'yes';
                $cls = $isPaid ? 'badge-soft-success' : 'badge-soft-danger';
                $icon = $isPaid ? 'bi-check-circle' : 'bi-x-circle';
                return '<span class="badge ' . $cls . '"><i class="bi ' . $icon . ' me-1"></i>' . e($val) . '</span>';
            }
            if ($name === 'priority') {
                $cls = match(strtolower((string)$val)) {
                    'high', 'urgent' => 'badge-soft-danger',
                    'medium', 'important' => 'badge-soft-warning',
                    default => 'badge-soft-secondary',
                };
                return '<span class="badge ' . $cls . '">' . e($val) . '</span>';
            }
            if ($name === 'status') {
                $cls = match(strtolower((string)$val)) {
                    'active', 'completed', 'delivered', 'finished', 'paid', 'received' => 'badge-soft-success',
                    'inactive', 'unpaid', 'on hold' => 'badge-soft-danger',
                    'pending', 'waiting on client', 'ordered' => 'badge-soft-warning',
                    'in progress', 'in review', 'revision' => 'badge-soft-info',
                    default => 'badge-soft-secondary',
                };
                return '<span class="badge ' . $cls . '">' . e($val) . '</span>';
            }
            if ($name === 'role_name') {
                $roleCls = match(strtolower((string)$val)) {
                    'super admin'          => 'badge-soft-danger',
                    'admin'                => 'badge-soft-primary',
                    'employee', 'user'     => 'badge-soft-success',
                    default                => 'badge-soft-secondary',
                };
                return '<span class="badge ' . $roleCls . '"><i class="bi bi-shield-lock me-1"></i>' . e($val) . '</span>';
            }
            if ($name === 'source') {
                $sLower = strtolower((string)$val);
                $color = match($sLower) {
                    'upwork' => '#14a800',
                    'fiverr' => '#1dbf73',
                    'whatsapp' => '#25d366',
                    'direct' => 'var(--text-primary)',
                    default => 'var(--text-secondary)',
                };
                return '<strong style="color: ' . $color . ';">' . e($val) . '</strong>';
            }
            if (in_array($name, ['employee_code', 'code', 'id'])) {
                return '<span class="badge-code">' . e($val) . '</span>';
            }
            if (str_contains($name, 'date') || str_contains($name, 'deadline')) {
                return '<span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i>' . e($val) . '</span>';
            }
            if (is_numeric($val) && str_contains($name, 'amount')) {
                return '<strong class="text-nowrap">' . money($val) . '</strong>';
            }
            return '<span class="text-nowrap">' . e($val) . '</span>';
        };

        ob_start();
        foreach ($currentResult['rows'] as $row): ?>
            <tr>
                <?php foreach ($visibleFields as $name => $field): 
                    $tdClass = '';
                    if (in_array($name, ['title', 'project_name', 'task_title', 'department_name', 'item', 'name', 'employee_name'])) {
                        $tdClass = 'col-title';
                    } elseif (in_array($name, ['description', 'desc', 'content', 'remarks', 'notes', 'reason', 'summary']) || ($field['type'] ?? '') === 'textarea') {
                        $tdClass = 'col-desc';
                    }
                ?>
                    <td class="<?= $tdClass ?>" data-col="<?= e($name) ?>"><?= $formatCell($name, $field, $row[$name] ?? null) ?></td>
                <?php endforeach; ?>
                <td class="text-nowrap text-muted small">
                    <i class="bi bi-clock-history me-1"></i><?= e($row['deleted_at'] ?? '—') ?>
                </td>
                <td class="text-end text-nowrap">
                    <div class="d-inline-flex align-items-center gap-1">
                        <!-- Restore Form -->
                        <form class="d-inline m-0" method="post" action="<?= url($activeTab . '/' . $row['id'] . '/restore') ?>" data-confirm="Restore this record back to active?">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-success" title="Restore back to active" type="submit">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                            </button>
                        </form>
                        <!-- Permanent Delete Form -->
                        <?php if ((Auth::user()['role_name'] ?? '') !== 'Employee'): ?>
                            <form class="d-inline m-0" method="post" action="<?= url($activeTab . '/' . $row['id'] . '/permanent-delete') ?>" data-confirm="Are you sure? This item will be permanently deleted and cannot be recovered!">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Permanently delete forever" type="submit">
                                    <i class="bi bi-trash3 me-1"></i> Delete Forever
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach;
        if (!$currentResult['rows']): ?>
            <tr>
                <td colspan="<?= count($visibleFields) + 2 ?>" class="text-center py-5">
                    <div class="text-muted">
                        <i class="bi bi-shield-check display-4 d-block mb-2 opacity-50 text-primary"></i>
                        <p class="mb-0">No records found in backup for <?= strtolower(e($currentModuleConfig['title'])) ?>.</p>
                    </div>
                </td>
            </tr>
        <?php endif;
        $tbodyHtml = ob_get_clean();

        component('table', [
            'headers' => $headers,
            'slot' => $tbodyHtml,
        ]);
        ?>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small"><?= (int)$currentResult['total'] ?> total records</span>
            <?= render_pagination((int)$page, (int)($currentResult['pages'] ?? 1), function($p) use ($activeTab, $query) {
                return url('backup?tab=' . $activeTab . '&page=' . $p . ($query !== '' ? '&q=' . urlencode($query) : ''));
            }) ?>
        </div>
    </div>
<?php endif; ?>
