<div class="card" id="moduleTableCard">
    <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <form class="d-flex gap-2" method="get">
            <div class="input-group">
                <input class="form-control" name="q" value="<?= e($query) ?>" placeholder="Search <?= e(strtolower($config['title'])) ?>...">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-print type="button"><i class="bi bi-printer me-1"></i> Print</button>
            <a class="btn btn-primary" href="<?= url($module . '/create') ?>"><i class="bi bi-plus-circle me-1"></i> Add New</a>
        </div>
    </div>
    <?php
    $visibleFields = array_filter($config['fields'], fn($field) => ($field['list'] ?? true) === true);
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
    $headers[] = ['label' => 'Actions', 'class' => 'text-end'];

    $formatCell = function ($name, $field, $val, $row = []) use ($module) {
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
        if ($name === 'type' || $name === 'priority' || (is_string($val) && in_array(strtolower($val), ['high', 'medium', 'low', 'urgent', 'important', 'update', 'general']))) {
            $cls = match(strtolower((string)$val)) {
                'high', 'urgent' => 'badge-soft-danger',
                'medium', 'important' => 'badge-soft-warning',
                'update' => 'badge-soft-success',
                'general' => 'badge-soft-primary',
                default => 'badge-soft-secondary',
            };
            return '<span class="badge ' . $cls . '">' . e($val) . '</span>';
        }
        if ($name === 'status' || (($field['type'] ?? '') === 'select' && in_array(strtolower((string)$val), ['active', 'inactive', 'pending', 'completed', 'in progress', 'in review', 'waiting on client', 'on hold', 'revision', 'delivered', 'finished', 'paid', 'unpaid', 'ordered', 'received']))) {
            $vLower = strtolower((string)$val);
            if ($module === 'pending-tasks' || isset($row['task_title'])) {
                if ($vLower === 'completed') {
                    $completedTime = !empty($row['updated_at']) ? strtotime($row['updated_at']) : strtotime($row['created_at'] ?? 'now');
                    $daysPassed = max(0, (int) floor((time() - $completedTime) / 86400));
                    $remDays = max(1, 7 - $daysPassed);
                    return '<div class="d-flex flex-column" style="line-height: 1.25;">
                        <strong style="color: #10b981;"><i class="bi bi-check-lg me-1"></i>Completed</strong>
                        <small class="text-muted" style="font-size: 11px;">auto-removes in ' . $remDays . ' ' . ($remDays === 1 ? 'day' : 'days') . '</small>
                    </div>';
                }
                $options = ['Pending', 'In Progress', 'Completed'];
                $currentStatus = (string)($val ?? 'Pending');
                $csrf = csrf_field();
                $actionUrl = url('dashboard/task-status');
                $id = (int)($row['id'] ?? 0);
                $optionsHtml = '';
                foreach ($options as $opt) {
                    $sel = ($currentStatus === $opt) ? 'selected' : '';
                    $optionsHtml .= '<option value="' . e($opt) . '" ' . $sel . '>' . e($opt) . '</option>';
                }
                return '<form method="post" action="' . $actionUrl . '" class="m-0" style="min-width: 130px;">'
                    . $csrf
                    . '<input type="hidden" name="task_id" value="' . $id . '">'
                    . '<select class="form-select form-select-sm" name="status" data-auto-submit>'
                    . $optionsHtml
                    . '</select>'
                    . '</form>';
            }
            if ($module === 'requirements' || isset($row['item'])) {
                $options = ['Pending', 'Ordered', 'Purchased', 'Received'];
                $currentStatus = (string)($val ?? 'Pending');
                $csrf = csrf_field();
                $actionUrl = url('dashboard/purchase-status');
                $id = (int)($row['id'] ?? 0);
                $optionsHtml = '';
                foreach ($options as $opt) {
                    $sel = ($currentStatus === $opt) ? 'selected' : '';
                    $optionsHtml .= '<option value="' . e($opt) . '" ' . $sel . '>' . e($opt) . '</option>';
                }
                return '<form method="post" action="' . $actionUrl . '" class="m-0" style="min-width: 130px;">'
                    . $csrf
                    . '<input type="hidden" name="purchase_id" value="' . $id . '">'
                    . '<select class="form-select form-select-sm" name="status" data-auto-submit>'
                    . $optionsHtml
                    . '</select>'
                    . '</form>';
            }
            $cls = match($vLower) {
                'active', 'completed', 'delivered', 'finished', 'paid', 'received' => 'badge-soft-success',
                'inactive', 'unpaid', 'on hold' => 'badge-soft-danger',
                'pending', 'waiting on client', 'ordered' => 'badge-soft-warning',
                'in progress', 'in review', 'revision' => 'badge-soft-info',
                default => 'badge-soft-secondary',
            };
            return '<span class="badge ' . $cls . '">' . e($val) . '</span>';
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
        if (in_array($name, ['employee_code', 'code', 'id'])) {
            return '<span class="badge-code">' . e($val) . '</span>';
        }
        if ($name === 'email') {
            return '<a class="email-link text-nowrap" href="mailto:' . e($val) . '"><i class="bi bi-envelope me-1 text-muted"></i>' . e($val) . '</a>';
        }
        if (in_array($name, ['phone', 'emergency_contact'])) {
            return '<span class="text-nowrap"><i class="bi bi-telephone me-1 text-muted"></i>' . e($val) . '</span>';
        }
        if (($field['type'] ?? '') === 'date' || str_contains($name, 'date') || str_contains($name, 'deadline')) {
            return '<span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i>' . e($val) . '</span>';
        }
        if (is_numeric($val) && str_contains($name, 'amount')) {
            return '<strong class="text-nowrap">' . money($val) . '</strong>';
        }
        // Role name — render as a badge with shield icon
        if ($name === 'role_name') {
            $roleCls = match(strtolower((string)$val)) {
                'super admin'          => 'badge-soft-danger',
                'admin'                => 'badge-soft-primary',
                'employee', 'user'     => 'badge-soft-success',
                default                => 'badge-soft-secondary',
            };
            return '<span class="badge ' . $roleCls . '"><i class="bi bi-shield-lock me-1"></i>' . e($val) . '</span>';
        }
        if (in_array($name, ['assigned_to', 'assigned_names', 'assignee', 'assignees'])) {
            $names = array_filter(array_map('trim', explode(',', (string)$val)));
            if (!empty($names)) {
                $chips = '';
                foreach ($names as $n) {
                    $chips .= '<span class="assignee-chip">' . e($n) . '</span>';
                }
                return '<div class="d-flex flex-wrap gap-1" style="max-width: 240px;">' . $chips . '</div>';
            }
            return '<span class="text-muted">—</span>';
        }
        return '<span class="text-nowrap">' . e($val) . '</span>';
    };

    ob_start();
    foreach ($result['rows'] as $row): ?>
        <tr>
            <?php foreach ($visibleFields as $name => $field): 
                $tdClass = '';
                if (in_array($name, ['title', 'project_name', 'task_title', 'department_name', 'item', 'name', 'employee_name'])) {
                    $tdClass = 'col-title';
                } elseif (in_array($name, ['description', 'desc', 'content', 'remarks', 'notes', 'reason', 'summary']) || ($field['type'] ?? '') === 'textarea') {
                    $tdClass = 'col-desc';
                }
            ?>
                <td class="<?= $tdClass ?>" data-col="<?= e($name) ?>"><?= $formatCell($name, $field, $row[$name] ?? null, $row) ?></td>
            <?php endforeach; ?>
            <td class="text-end text-nowrap">
                <div class="d-inline-flex align-items-center gap-1">
                    <a class="btn btn-sm btn-icon btn-outline-primary" href="<?= url($module . '/' . $row['id'] . '/edit') ?>" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form class="d-inline m-0" method="post" action="<?= url($module . '/' . $row['id'] . '/delete') ?>" data-confirm="Delete this record?">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-icon btn-outline-danger" title="Delete" type="submit">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach;
    if (!$result['rows']): ?>
        <tr><td colspan="<?= count($visibleFields) + 1 ?>" class="text-center text-muted py-4">No records found.</td></tr>
    <?php endif;
    $tbodyHtml = ob_get_clean();

    component('table', [
        'headers' => $headers,
        'slot' => $tbodyHtml,
    ]);
    ?>

    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="text-muted small"><?= e($result['total']) ?> records</span>
        <?= render_pagination((int)$page, (int)($result['pages'] ?? 1), function($p) use ($module, $query) {
            return url($module . '?page=' . $p . ($query !== '' ? '&q=' . urlencode($query) : ''));
        }) ?>
    </div>
</div>

