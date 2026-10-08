<?php
$currentUser = Auth::user();
$isAdmin = in_array($currentUser['role_name'] ?? '', ['Super Admin', 'Admin'], true);

$myEmpId = null;
if (!$isAdmin && !empty($currentUser['email'])) {
    $empStmt = Database::connection()->prepare('SELECT id FROM employees WHERE email = :email LIMIT 1');
    $empStmt->execute(['email' => $currentUser['email']]);
    $myEmpId = (int)$empStmt->fetchColumn() ?: null;
}

$visibleFields = array_filter($config['fields'], fn($field) => ($field['list'] ?? true) === true);

$renderProjectTable = function (array $rows, string $emptyMessage) use ($visibleFields, $module, $isAdmin, $myEmpId): void {
    $headers = [];
    foreach ($visibleFields as $name => $field) {
        $thClass = '';
        if (in_array($name, ['project_name', 'title', 'task_title', 'name'])) {
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
    $headers[] = ['label' => 'Assigned', 'class' => ''];
    $headers[] = ['label' => 'Days Left', 'class' => ''];
    $headers[] = ['label' => 'Actions', 'class' => 'text-end'];

    $formatDaysLeft = function(array $row) {
        $st = strtolower((string)($row['status'] ?? ''));
        if (in_array($st, ['delivered', 'finished', 'completed'], true)) {
            $deliveryDateStr = $row['delivery_date'] ?? $row['updated_at'] ?? null;
            $daysPassed = 0;
            if (!empty($deliveryDateStr)) {
                try {
                    $dDate = new DateTime(substr((string)$deliveryDateStr, 0, 10));
                    $today = new DateTime('today');
                    $daysPassed = max(0, (int) $dDate->diff($today)->format('%r%a'));
                } catch (\Throwable $e) {}
            }
            $autoRemoveDays = max(0, 7 - $daysPassed);
            return '<div class="d-flex flex-column" style="line-height: 1.25;">
                <strong style="color: #10b981;"><i class="bi bi-check-lg me-1"></i>Done</strong>
                <small class="text-muted" style="font-size: 11px;">auto-removes in ' . $autoRemoveDays . ' ' . ($autoRemoveDays === 1 ? 'day' : 'days') . '</small>
            </div>';
        }

        $deadline = $row['deadline'] ?? null;
        if (empty($deadline)) {
            return '<span class="text-muted">—</span>';
        }

        try {
            $target = new DateTime($deadline);
            $today = new DateTime('today');
            $diffDays = (int) $today->diff($target)->format('%r%a');

            if ($diffDays < 0) {
                $abs = abs($diffDays);
                return '<strong style="color: #ef4444;">Overdue (' . $abs . 'd)</strong>';
            }
            if ($diffDays === 0) {
                return '<strong style="color: #ef4444;">Due today</strong>';
            }
            if ($diffDays === 1) {
                return '<strong style="color: #ef4444;">1 day</strong>';
            }
            if ($diffDays <= 3) {
                return '<strong style="color: #f59e0b;">' . $diffDays . ' days</strong>';
            }
            return '<strong style="color: #10b981;">' . $diffDays . ' days</strong>';
        } catch (\Throwable $e) {
            return '<span class="text-muted">—</span>';
        }
    };

    $formatProjectCell = function ($name, $val, $row = [], $field = [], $canEdit = false) {
        if ($val === null || $val === '') {
            return '<span class="text-muted">—</span>';
        }
        if (in_array($name, ['description', 'desc', 'content', 'remarks', 'notes', 'reason', 'summary']) || ($field['type'] ?? '') === 'textarea') {
            return '<div class="cell-desc">' . nl2br(e($val)) . '</div>';
        }
        if ($name === 'category') {
            $cat = (string)$val;
            $isHouse = strtolower(str_replace([' ', '-', '_'], '', $cat)) === 'inhouse';
            $cls = $isHouse ? 'badge-soft-primary' : 'badge-soft-warning';
            return '<span class="badge ' . $cls . '">' . e($cat) . '</span>';
        }
        if ($name === 'priority') {
            $cls = match(strtolower((string)$val)) {
                'high' => 'badge-soft-danger',
                'medium' => 'badge-soft-warning',
                default => 'badge-soft-secondary',
            };
            return '<span class="badge ' . $cls . '">' . e($val) . '</span>';
        }
        if ($name === 'status') {
            if (!$canEdit) {
                $stLower = strtolower((string)($val ?? ''));
                $statusCls = match($stLower) {
                    'completed', 'delivered', 'finished' => 'badge-soft-success',
                    'in progress', 'in review', 'waiting on client' => 'badge-soft-info',
                    'revision', 'pending' => 'badge-soft-warning',
                    'on hold' => 'badge-soft-danger',
                    default => 'badge-soft-secondary',
                };
                return '<span class="badge ' . $statusCls . '">' . e($val ?? '—') . '</span>';
            }
            $options = ['Not Started', 'Pending', 'In Progress', 'In Review', 'On Hold', 'Waiting on Client', 'Revision', 'Completed', 'Delivered', 'Finished'];
            $currentStatus = (string)($val ?? 'Pending');
            $csrf = csrf_field();
            $actionUrl = url('dashboard/project-status');
            $id = (int)($row['id'] ?? 0);

            $optionsHtml = '';
            foreach ($options as $opt) {
                $sel = ($currentStatus === $opt) ? 'selected' : '';
                $optionsHtml .= '<option value="' . e($opt) . '" ' . $sel . '>' . e($opt) . '</option>';
            }

            return '<form method="post" action="' . $actionUrl . '" class="m-0" style="min-width: 140px;">'
                . $csrf
                . '<input type="hidden" name="project_id" value="' . $id . '">'
                . '<select class="form-select form-select-sm" name="status" data-auto-submit>'
                . $optionsHtml
                . '</select>'
                . '</form>';
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
        if ($name === 'progress') {
            $p = (int)$val;
            return '<div class="d-flex align-items-center gap-2" style="min-width: 120px;">
                <div class="progress flex-grow-1" style="height: 6px;"><div class="progress-bar" data-progress="' . $p . '"></div></div>
                <small class="text-muted fw-semibold" style="min-width: 32px;">' . $p . '%</small>
            </div>';
        }
        if ($name === 'deadline' || str_contains($name, 'date')) {
            return '<span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i>' . e($val) . '</span>';
        }
        if ($name === 'project_name') {
            return '<strong class="cell-title d-inline-block">' . e($val) . '</strong>';
        }
        return '<span class="text-nowrap">' . e($val) . '</span>';
    };

    ob_start();
    foreach ($rows as $row): 
        $assignedIds = !empty($row['assigned_employee_ids']) ? explode(',', (string)$row['assigned_employee_ids']) : [];
        $isAssignedToMe = (!$isAdmin && $myEmpId && in_array((string)$myEmpId, $assignedIds, true));
        $canEdit = $isAdmin || $isAssignedToMe;
        $canDelete = $isAdmin;
    ?>
        <tr>
            <?php foreach ($visibleFields as $name => $field): 
                $tdClass = '';
                if (in_array($name, ['project_name', 'title', 'task_title', 'name'])) {
                    $tdClass = 'col-title';
                } elseif (in_array($name, ['description', 'desc', 'content', 'remarks', 'notes', 'reason', 'summary']) || ($field['type'] ?? '') === 'textarea') {
                    $tdClass = 'col-desc';
                }
            ?>
                <td class="<?= $tdClass ?>" data-col="<?= e($name) ?>"><?= $formatProjectCell($name, $row[$name] ?? null, $row, $field, $canEdit) ?></td>
            <?php endforeach; ?>
            <td>
                <?php
                $names = array_filter(array_map('trim', explode(',', $row['assigned_names'] ?? '')));
                if ($names): ?>
                    <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
                        <?php foreach ($names as $n): ?>
                            <span class="assignee-chip"><?= e($n) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td><?= $formatDaysLeft($row) ?></td>
            <td class="text-end text-nowrap">
                <div class="d-inline-flex align-items-center gap-1">
                    <?php if ($canEdit): ?>
                        <a class="btn btn-sm btn-icon btn-outline-primary" href="<?= url($module . '/' . $row['id'] . '/edit') ?>" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" disabled style="opacity: 0.35; cursor: not-allowed;" title="Edit restricted (only assigned employee can edit)">
                            <i class="bi bi-pencil"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <form class="d-inline m-0" method="post" action="<?= url($module . '/' . $row['id'] . '/delete') ?>" data-confirm="Delete this record?">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-icon btn-outline-danger" title="Delete" type="submit">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" disabled style="opacity: 0.35; cursor: not-allowed;" title="Delete restricted (Admin only)">
                            <i class="bi bi-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach;
    if (!$rows): ?>
        <tr><td colspan="<?= count($visibleFields) + 3 ?>" class="text-center text-muted py-4"><?= e($emptyMessage) ?></td></tr>
    <?php endif;
    $tbodyHtml = ob_get_clean();

    component('table', [
        'headers' => $headers,
        'slot' => $tbodyHtml,
    ]);
};
?>

<div class="card mb-4" id="activeProjectsCard">
    <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <form class="d-flex gap-2" method="get">
            <div class="input-group">
                <input class="form-control" name="q" value="<?= e($query) ?>" placeholder="Search projects...">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-print type="button"><i class="bi bi-printer me-1"></i> Print</button>
            <?php if ($isAdmin): ?>
                <a class="btn btn-primary" href="<?= url($module . '/create') ?>"><i class="bi bi-plus-circle me-1"></i> Add New</a>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled style="opacity: 0.55; cursor: not-allowed;" title="Create restricted (Admin only)"><i class="bi bi-plus-circle me-1"></i> Add New</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Active Projects</strong>
        <span class="text-muted small"><?= (int)($activeProjects['total'] ?? 0) ?> records</span>
    </div>
    <?php $renderProjectTable($activeProjects['rows'] ?? [], 'No active projects found.'); ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="text-muted small"><?= (int)($activeProjects['total'] ?? 0) ?> active records</span>
        <?= render_pagination((int)($activeProjects['page'] ?? 1), (int)($activeProjects['pages'] ?? 1), function($p) use ($module, $completedProjects, $query) {
            return url($module . '?page_active=' . $p . '&page_completed=' . ($completedProjects['page'] ?? 1) . ($query !== '' ? '&q=' . urlencode($query) : ''));
        }) ?>
    </div>
</div>

<div class="card" id="completedProjectsCard">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Delivered Projects</strong>
        <span class="text-muted small"><?= (int)($completedProjects['total'] ?? 0) ?> records</span>
    </div>
    <?php $renderProjectTable($completedProjects['rows'] ?? [], 'No delivered projects found.'); ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="text-muted small"><?= (int)($completedProjects['total'] ?? 0) ?> delivered records</span>
        <?= render_pagination((int)($completedProjects['page'] ?? 1), (int)($completedProjects['pages'] ?? 1), function($p) use ($module, $activeProjects, $query) {
            return url($module . '?page_active=' . ($activeProjects['page'] ?? 1) . '&page_completed=' . $p . ($query !== '' ? '&q=' . urlencode($query) : ''));
        }) ?>
    </div>
</div>

