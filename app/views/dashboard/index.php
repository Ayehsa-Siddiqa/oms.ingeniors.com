<?php
$dashboardUrl = function(string $param, int $page) {
    $params = $_GET;
    $params[$param] = $page;
    return url('dashboard?' . http_build_query($params));
};

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
?>

<?php if (!empty($showAnnouncementToast)): ?>
    <!-- One-time login toast notification for announcements -->
    <div class="alert alert-primary alert-dismissible fade show d-flex flex-wrap align-items-center justify-content-between gap-3 shadow-sm mb-4" role="alert" style="border-left: 4px solid var(--oms-primary); background: var(--oms-panel); border-color: var(--oms-border);">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(var(--oms-primary-rgb), 0.15); width: 38px; height: 38px; flex-shrink: 0;">
                <i class="bi bi-megaphone-fill text-primary" style="font-size: 16px;"></i>
            </div>
            <div>
                <strong style="color: var(--oms-text);">Welcome back! See latest announcements.</strong>
                <div class="small text-muted">Stay up to date with new engineering ops, meetings, and updates.</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="#announcementsBox" id="toastAnnouncementLink" class="btn btn-sm btn-primary">
                <i class="bi bi-megaphone me-1"></i>See Announcements
            </a>
            <button type="button" class="btn-close position-static" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<!-- 1. Recent Projects (Full Width) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card dashboard-card" id="dashboardProjectsCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Recent Projects</strong>
                <?php if (Auth::can('projects')): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('projects') ?>">Manage</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="opacity: 0.45; cursor: not-allowed;" title="Access restricted">Manage</button>
                <?php endif; ?>
            </div>
            <?php
            ob_start();
            foreach ($recentProjects['rows'] as $project): 
                $pVal = (int) ($project['progress'] ?? 0);
                $priority = $project['priority'] ?? 'Low';
                $priorityCls = match(strtolower((string)$priority)) {
                    'high' => 'badge-soft-danger',
                    'medium' => 'badge-soft-warning',
                    default => 'badge-soft-secondary',
                };
                $source = $project['source'] ?? 'Direct';
                $sourceColor = match(strtolower((string)$source)) {
                    'upwork' => '#14a800',
                    'fiverr' => '#1dbf73',
                    'whatsapp' => '#25d366',
                    'direct' => 'var(--text-primary)',
                    default => 'var(--text-secondary)',
                };
            ?>
                <tr>
                    <td class="col-title"><strong class="cell-title d-inline-block"><?= e($project['project_name']) ?></strong></td>
                    <td>
                        <?php
                        $cat = $project['category'] ?? 'In House';
                        $isHouse = strtolower(str_replace([' ', '-', '_'], '', (string)$cat)) === 'inhouse';
                        $cls = $isHouse ? 'badge-soft-primary' : 'badge-soft-warning';
                        ?>
                        <span class="badge <?= $cls ?>"><?= e($cat) ?></span>
                    </td>
                    <td><strong style="color: <?= $sourceColor ?>;"><?= e($source) ?></strong></td>
                    <td>
                        <?php
                        $names = array_filter(array_map('trim', explode(',', $project['assigned_names'] ?? '')));
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
                    <td class="status-col">
                        <?php
                        $stLower = strtolower((string)($project['status'] ?? ''));
                        $statusCls = match($stLower) {
                            'completed', 'delivered', 'finished' => 'badge-soft-success',
                            'in progress', 'in review', 'waiting on client' => 'badge-soft-info',
                            'revision', 'pending' => 'badge-soft-warning',
                            'on hold' => 'badge-soft-danger',
                            default => 'badge-soft-secondary',
                        };
                        ?>
                        <span class="badge <?= $statusCls ?>"><?= e($project['status'] ?? '—') ?></span>
                    </td>
                    <td><span class="badge <?= $priorityCls ?>"><?= e($priority) ?></span></td>
                    <td><span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($project['deadline']) ?></span></td>
                    <td><?= $formatDaysLeft($project) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2" style="min-width: 120px;">
                            <div class="progress flex-grow-1" style="height: 6px;"><div class="progress-bar" data-progress="<?= $pVal ?>"></div></div>
                            <small class="text-muted fw-semibold" style="min-width: 32px;"><?= $pVal ?>%</small>
                        </div>
                    </td>
                </tr>
            <?php endforeach;
            if (!$recentProjects['rows']): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No active projects found.</td></tr>
            <?php endif;
            $tbody1 = ob_get_clean();

            component('table', [
                'headers' => [['label' => 'Project', 'class' => 'col-title'], 'Category', 'Source', 'Assigned', ['label' => 'Status', 'class' => 'status-col'], 'Priority', 'Deadline', 'Days Left', 'Progress'],
                'slot' => $tbody1,
            ]);
            ?>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted small"><?= (int)($recentProjects['total'] ?? 0) ?> active projects</span>
                <?= render_pagination((int)($recentProjects['page'] ?? 1), (int)($recentProjects['pages'] ?? 1), function($p) use ($dashboardUrl) {
                    return $dashboardUrl('proj_page', $p);
                }) ?>
            </div>
        </div>
    </div>
</div>

<!-- 2. Pending Tasks & Purchase Requests (33.33% Width Each) -->
<div class="row g-4 mb-4">
    <!-- Pending Tasks (33.33% width) -->
    <div class="col-xl-4 col-lg-6">
        <div class="card dashboard-card h-100" id="dashboardTasksCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Pending Tasks</strong>
                <?php if (Auth::can('pending_tasks')): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('pending-tasks') ?>">Manage</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="opacity: 0.45; cursor: not-allowed;" title="Access restricted">Manage</button>
                <?php endif; ?>
            </div>
            <?php
            ob_start();
            foreach ($pendingTasks['rows'] as $task): 
                $tStatus = $task['status'] ?? 'Pending';
                $isTaskCompleted = ($tStatus === 'Completed');
                $taskRemDays = 7;
                if ($isTaskCompleted) {
                    try {
                        $tDate = new DateTime(substr((string)($task['updated_at'] ?? $task['created_at'] ?? 'now'), 0, 10));
                        $tDiff = max(0, (int) $tDate->diff(new DateTime('today'))->format('%r%a'));
                        $taskRemDays = max(0, 7 - $tDiff);
                    } catch (\Throwable $e) {}
                }
            ?>
                <tr>
                    <td class="col-title">
                        <strong class="cell-title d-inline-block"><?= e($task['task_title']) ?></strong>
                        <?php if (!empty($task['category'])): ?>
                            <div class="text-muted small"><?= e($task['category']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($task['assigned_to'])):
                            $taskAssignees = array_filter(array_map('trim', explode(',', $task['assigned_to'])));
                            if (!empty($taskAssignees)): ?>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php foreach ($taskAssignees as $ta): ?>
                                        <span class="assignee-chip" style="font-size: 11px; padding: 1px 6px;"><?= e($ta) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="status-col">
                        <?php if ($isTaskCompleted): ?>
                            <div class="d-flex flex-column" style="line-height: 1.25;">
                                <strong style="color: #10b981;"><i class="bi bi-check-lg me-1"></i>Completed</strong>
                                <small class="text-muted" style="font-size: 11px;">auto-removes in <?= $taskRemDays ?> <?= $taskRemDays === 1 ? 'day' : 'days' ?></small>
                            </div>
                        <?php else: 
                            $tCls = match(strtolower((string)$tStatus)) {
                                'in progress' => 'badge-soft-info',
                                'pending' => 'badge-soft-warning',
                                default => 'badge-soft-secondary',
                            };
                        ?>
                            <span class="badge <?= $tCls ?>"><?= e($tStatus) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach;
            if (!$pendingTasks['rows']): ?>
                <tr><td colspan="2" class="text-center text-muted py-4">No pending tasks found.</td></tr>
            <?php endif;
            $tbody2 = ob_get_clean();

            component('table', [
                'headers' => [['label' => 'Task', 'class' => 'col-title'], ['label' => 'Status', 'class' => 'status-col']],
                'slot' => $tbody2,
            ]);
            ?>
            <div class="card-footer mt-auto d-flex justify-content-between align-items-center">
                <span class="text-muted small"><?= (int)($pendingTasks['total'] ?? 0) ?> tasks</span>
                <?= render_pagination((int)($pendingTasks['page'] ?? 1), (int)($pendingTasks['pages'] ?? 1), function($p) use ($dashboardUrl) {
                    return $dashboardUrl('task_page', $p);
                }) ?>
            </div>
        </div>
    </div>

    <!-- Purchase Requests (33.33% width) -->
    <div class="col-xl-4 col-lg-6">
        <div class="card dashboard-card h-100" id="dashboardPurchasesCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Purchase Requests</strong>
                <?php if (Auth::can('requirements')): ?>
                    <a class="btn btn-sm btn-outline-warning" href="<?= url('requirements') ?>">Manage</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="opacity: 0.45; cursor: not-allowed;" title="Access restricted">Manage</button>
                <?php endif; ?>
            </div>
            <?php
            ob_start();
            foreach ($pendingPurchases['rows'] as $purchase): 
                $pStatus = $purchase['status'] ?? 'Pending';
                $pCls = match(strtolower((string)$pStatus)) {
                    'purchased', 'received' => 'badge-soft-success',
                    'ordered', 'pending' => 'badge-soft-warning',
                    default => 'badge-soft-secondary',
                };
            ?>
                <tr>
                    <td class="col-title">
                        <strong class="cell-title d-inline-block"><?= e($purchase['item']) ?></strong>
                        <div class="text-muted small"><?= (int) $purchase['quantity'] ?> qty · <?= e($purchase['required_date']) ?></div>
                    </td>
                    <td class="status-col">
                        <span class="badge <?= $pCls ?>"><?= e($pStatus) ?></span>
                    </td>
                </tr>
            <?php endforeach;
            if (!$pendingPurchases['rows']): ?>
                <tr><td colspan="2" class="text-center text-muted py-4">No purchase requests found.</td></tr>
            <?php endif;
            $tbody3 = ob_get_clean();

            component('table', [
                'headers' => [['label' => 'Item', 'class' => 'col-title'], ['label' => 'Status', 'class' => 'status-col']],
                'slot' => $tbody3,
            ]);
            ?>
            <div class="card-footer mt-auto d-flex justify-content-between align-items-center">
                <span class="text-muted small"><?= (int)($pendingPurchases['total'] ?? 0) ?> requests</span>
                <?= render_pagination((int)($pendingPurchases['page'] ?? 1), (int)($pendingPurchases['pages'] ?? 1), function($p) use ($dashboardUrl) {
                    return $dashboardUrl('pur_page', $p);
                }) ?>
            </div>
        </div>
    </div>

    <!-- In-House Project Progress (Remaining 33.33% Width) -->
    <div class="col-xl-4 col-lg-12">
        <div class="card dashboard-card h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-wrench text-primary"></i>
                    <strong>In-House Project Progress</strong>
                </div>
                <span class="text-muted small"><?= count($inHouseProjects) ?> projects</span>
            </div>
            <div class="card-body p-3 overflow-y-auto" style="max-height: 285px; flex: 1 1 auto;">
                <?php if (empty($inHouseProjects)): ?>
                    <div class="text-center text-muted py-4 small">No in-house projects in progress.</div>
                <?php else: ?>
                    <div class="inhouse-project-list">
                        <?php foreach ($inHouseProjects as $ihp): 
                            $ihpProgress = max(0, min(100, (int) ($ihp['progress'] ?? 0)));
                        ?>
                            <div class="inhouse-project-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="inhouse-project-name text-truncate" title="<?= e($ihp['project_name']) ?>">
                                        <?= e($ihp['project_name']) ?>
                                    </span>
                                    <span class="inhouse-project-pct"><?= $ihpProgress ?>%</span>
                                </div>
                                <div class="progress inhouse-progress-bar">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $ihpProgress ?>%;" aria-valuenow="<?= $ihpProgress ?>" aria-valuemin="0" aria-valuemax="100" data-progress="<?= $ihpProgress ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer mt-auto d-flex justify-content-between align-items-center">
                <span class="text-muted small">In-House operations</span>
                <?php if (Auth::can('projects')): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('projects') ?>">Manage</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="opacity: 0.45; cursor: not-allowed;" title="Access restricted">Manage</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Announcements (Full Width under In-House Project Progress) -->
<div class="row g-4 mb-4" id="announcementsBox">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3" style="gap: 12px;">
                    <i class="bi bi-megaphone text-primary" style="font-size: 1.15rem;"></i>
                    <strong class="fs-6">Announcements</strong>
                    <span class="badge rounded-pill" style="background: rgba(148, 163, 184, 0.2); color: var(--oms-text-secondary); padding: 4px 10px; font-size: 12px; font-weight: 600;"><?= count($announcements) ?></span>
                </div>
                <?php if (Auth::can('announcements')): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('announcements') ?>">Manage</a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="opacity: 0.45; cursor: not-allowed;" title="Access restricted">Manage</button>
                <?php endif; ?>
            </div>
            <div class="card-body p-3">
                <?php if (empty($announcements)): ?>
                    <div class="text-center text-muted py-4 small">No active announcements at this time.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($announcements as $ann): 
                            $typeLower = strtolower(trim((string)($ann['type'] ?? 'general')));
                            $stripeClass = match($typeLower) {
                                'urgent' => 'stripe-urgent',
                                'important', 'warning' => 'stripe-important',
                                'update', 'success' => 'stripe-update',
                                default => 'stripe-general',
                            };
                            $pillClass = match($typeLower) {
                                'urgent' => 'pill-urgent',
                                'important', 'warning' => 'pill-important',
                                'update', 'success' => 'pill-update',
                                default => 'pill-general',
                            };
                        ?>
                            <a href="<?= url('announcements') ?>" class="announcement-item-row <?= $stripeClass ?> text-decoration-none">
                                <div class="d-flex flex-column flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-1">
                                        <span class="announcement-item-title"><?= e($ann['title']) ?></span>
                                        <span class="announcement-type-pill <?= $pillClass ?> flex-shrink-0"><?= e(strtoupper((string)($ann['type'] ?? 'GENERAL'))) ?></span>
                                    </div>
                                    <?php if (!empty($ann['content'])): ?>
                                        <div class="announcement-item-desc"><?= e($ann['content']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 3. Key Operational & Status Metric Boxes (Unified Responsive Strip) -->
<?php
$allMetricBoxes = [
    ['label' => 'Projects', 'value' => $projects, 'icon' => 'bi-kanban', 'color' => 'text-primary'],
    ['label' => 'Employees', 'value' => $employees, 'icon' => 'bi-people', 'color' => 'text-success'],
    ['label' => 'Delivered', 'value' => $completed, 'icon' => 'bi-check2-circle', 'color' => 'text-success'],
    ['label' => 'Overdue', 'value' => $overdue, 'icon' => 'bi-exclamation-triangle', 'color' => 'text-danger'],
];

foreach ($statusRows as $row) {
    $stName = trim((string)($row['status'] ?? ''));
    if ($stName === '') continue;
    $stLower = strtolower($stName);
    if (in_array($stLower, ['delivered', 'completed', 'finished'], true)) {
        continue;
    }
    $iconInfo = match($stLower) {
        'in progress' => ['bi-play-circle', 'text-info'],
        'pending' => ['bi-hourglass-split', 'text-warning'],
        'in review' => ['bi-eye', 'text-primary'],
        'on hold' => ['bi-pause-circle', 'text-danger'],
        'revision' => ['bi-arrow-repeat', 'text-warning'],
        'waiting on client' => ['bi-person-clock', 'text-warning'],
        default => ['bi-kanban', 'text-secondary'],
    };
    $allMetricBoxes[] = [
        'label' => $stName,
        'value' => (int)$row['total'],
        'icon' => $iconInfo[0],
        'color' => $iconInfo[1],
    ];
}
?>

<div class="dashboard-metrics-grid mb-4" style="--box-count: <?= count($allMetricBoxes) ?>;">
    <?php foreach ($allMetricBoxes as $box): ?>
        <div class="metric-box">
            <i class="bi <?= e($box['icon']) ?> <?= e($box['color']) ?> metric-box-icon"></i>
            <span class="metric-box-label"><?= e(strtoupper($box['label'])) ?></span>
            <strong class="metric-box-value"><?= e($box['value']) ?></strong>
        </div>
    <?php endforeach; ?>
</div>

<!-- 4. Recent Activity (Full Width) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card dashboard-card" id="dashboardActivitiesCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Recent Activity</strong>
                <span class="text-muted small">Last 30 Days</span>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($activities['rows'] as $activity): ?>
                    <div class="list-group-item">
                        <strong class="activity-title"><?= e($activity['action']) ?></strong>
                        <div class="activity-desc"><?= e($activity['module']) ?> by <?= e($activity['name'] ?? 'System') ?> · <?= e($activity['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($activities['rows'])): ?>
                    <div class="list-group-item text-center text-muted py-4 small">No recent activity in the last 30 days.</div>
                <?php endif; ?>
            </div>
            <div class="card-footer mt-auto d-flex justify-content-between align-items-center">
                <span class="text-muted small">Page <?= (int)($activities['page'] ?? 1) ?> of <?= (int)($activities['pages'] ?? 1) ?></span>
                <?= render_pagination((int)($activities['page'] ?? 1), (int)($activities['pages'] ?? 1), function($p) use ($dashboardUrl) {
                    return $dashboardUrl('act_page', $p);
                }) ?>
            </div>
        </div>
    </div>
</div>
