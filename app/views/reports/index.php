<?php
$projectCount = count($projects);
$empCount = count($employees);
$reqCount = count($requirements);
$fineCount = count($fines);
$taskCount = count($tasks);

$completedProjects = $projectStatusCounts['Completed'] ?? 0;
$activeProjects = $projectCount - $completedProjects;
$completionRate = $projectCount > 0 ? round(($completedProjects / $projectCount) * 100) : 0;
$fineRecoveryRate = $finesTotal > 0 ? round(($finesPaid / $finesTotal) * 100) : 0;
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="reports-container pb-4">
    <!-- Header & Action Ribbon -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="mb-1 d-flex align-items-center gap-2 fw-bold">
                <i class="bi bi-graph-up-arrow text-primary"></i> Operations & Performance Reports
            </h3>
            <p class="text-muted mb-0 small">
                Real-time operational intelligence, workload distribution, procurement expenditure, and milestone analytics.
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm" onclick="window.print()" type="button">
                <i class="bi bi-printer"></i>
                <span>Print Report</span>
            </button>
            <button class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="exportCurrentCsvBtn" type="button">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Filter & Date Preset Bar -->
    <div class="card mb-4 shadow-sm reports-filter-card">
        <div class="card-body p-2 p-md-3">
            <form class="d-flex flex-wrap align-items-center justify-content-between gap-3" method="get">
                <!-- Left: Presets Group -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-bold d-inline-flex align-items-center gap-1 me-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                        <i class="bi bi-funnel text-primary"></i> Presets:
                    </span>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <a class="preset-pill <?= $preset === 'this_month' ? 'active' : '' ?>" href="<?= url('reports?preset=this_month') ?>">This Month</a>
                        <a class="preset-pill <?= $preset === 'last_30' ? 'active' : '' ?>" href="<?= url('reports?preset=last_30') ?>">Last 30 Days</a>
                        <a class="preset-pill <?= ($preset === 'this_year' || (empty($preset) && empty($_GET['from']))) ? 'active' : '' ?>" href="<?= url('reports?preset=this_year') ?>">This Year</a>
                        <a class="preset-pill <?= $preset === 'all' ? 'active' : '' ?>" href="<?= url('reports?preset=all') ?>">All Time</a>
                    </div>
                </div>

                <!-- Right: Custom Date Range Form -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm reports-date-input-group">
                            <span class="input-group-text"><i class="bi bi-calendar3 me-1"></i> From</span>
                            <input class="form-control" name="from" type="date" value="<?= e($from) ?>" aria-label="From Date">
                        </div>
                        <div class="input-group input-group-sm reports-date-input-group">
                            <span class="input-group-text">To</span>
                            <input class="form-control" name="to" type="date" value="<?= e($to) ?>" aria-label="To Date">
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-3 shadow-sm fw-semibold" type="submit">
                        <i class="bi bi-filter"></i> Apply Range
                    </button>
                    <?php if (!empty($_GET['from']) || !empty($_GET['preset'])): ?>
                        <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center shadow-sm" href="<?= url('reports') ?>" title="Reset Filters" style="width: 32px; height: 31px;">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Top KPI Cards Ribbon -->
    <div class="row g-3 mb-4">
        <!-- Projects KPI -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="reports-kpi-card h-100" style="--kpi-color: #3b82f6; --kpi-rgb: 59, 130, 246;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Projects Overview</span>
                        <div class="reports-kpi-value mt-1"><?= $projectCount ?></div>
                    </div>
                    <div class="reports-kpi-icon">
                        <i class="bi bi-kanban"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top border-secondary-subtle small">
                    <span class="text-success"><i class="bi bi-check2-circle me-1"></i><?= $completedProjects ?> Completed</span>
                    <span class="text-primary fw-bold"><?= $activeProjects ?> Active</span>
                    <span class="badge badge-soft-primary"><?= $completionRate ?>% Done</span>
                </div>
            </div>
        </div>

        <!-- Workforce KPI -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="reports-kpi-card h-100" style="--kpi-color: #10b981; --kpi-rgb: 16, 185, 129;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Active Workforce</span>
                        <div class="reports-kpi-value mt-1"><?= $empCount ?></div>
                    </div>
                    <div class="reports-kpi-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top border-secondary-subtle small">
                    <span class="text-muted"><i class="bi bi-building me-1"></i><?= count($deptCounts) ?> Departments</span>
                    <span class="badge badge-soft-success">100% Operational</span>
                </div>
            </div>
        </div>

        <!-- Procurement / Cost KPI -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="reports-kpi-card h-100" style="--kpi-color: #f59e0b; --kpi-rgb: 245, 158, 11;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Procurement Spend</span>
                        <div class="reports-kpi-value mt-1"><?= money($reqTotalCost) ?></div>
                    </div>
                    <div class="reports-kpi-icon">
                        <i class="bi bi-cart3"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top border-secondary-subtle small">
                    <span class="text-success"><i class="bi bi-bag-check me-1"></i><?= money($reqPurchasedCost) ?></span>
                    <span class="text-warning"><?= money($reqPendingCost) ?> Req.</span>
                </div>
            </div>
        </div>

        <!-- Fines & Penalties KPI -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="reports-kpi-card h-100" style="--kpi-color: #ef4444; --kpi-rgb: 239, 68, 68;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Fines & Penalties</span>
                        <div class="reports-kpi-value mt-1"><?= money($finesTotal) ?></div>
                    </div>
                    <div class="reports-kpi-icon">
                        <i class="bi bi-shield-slash"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top border-secondary-subtle small">
                    <span class="text-success"><i class="bi bi-check-lg me-1"></i>Paid: <?= money($finesPaid) ?></span>
                    <span class="text-danger">Unpaid: <?= money($finesUnpaid) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphics / Charts Section -->
    <div class="row g-3 mb-4">
        <!-- Chart 1: Project Status Pipeline (Bar Chart) -->
        <div class="col-12 col-lg-7">
            <div class="reports-chart-card shadow-sm">
                <div class="reports-chart-header">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line text-primary me-2"></i>Projects Status Distribution</h5>
                        <small class="text-muted">Breakdown of projects by lifecycle stage</small>
                    </div>
                    <span class="badge badge-soft-primary"><?= $projectCount ?> Total</span>
                </div>
                <div class="reports-chart-body">
                    <canvas id="projectStatusBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Workforce by Department (Doughnut Chart) -->
        <div class="col-12 col-lg-5">
            <div class="reports-chart-card shadow-sm">
                <div class="reports-chart-header">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-success me-2"></i>Department Headcount</h5>
                        <small class="text-muted">Staff allocation across departments</small>
                    </div>
                    <span class="badge badge-soft-success"><?= $empCount ?> Members</span>
                </div>
                <div class="reports-chart-body d-flex align-items-center justify-content-center">
                    <canvas id="departmentDoughnutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 3: Project Priority & Urgency (Doughnut Chart) -->
        <div class="col-12 col-md-6 col-lg-6">
            <div class="reports-chart-card shadow-sm">
                <div class="reports-chart-header">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge text-warning me-2"></i>Project Priorities</h5>
                        <small class="text-muted">Workload urgency breakdown</small>
                    </div>
                </div>
                <div class="reports-chart-body d-flex align-items-center justify-content-center">
                    <canvas id="projectPriorityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 4: Procurement Pipeline (Horizontal Bar Chart) -->
        <div class="col-12 col-md-6 col-lg-6">
            <div class="reports-chart-card shadow-sm">
                <div class="reports-chart-header">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check text-info me-2"></i>Procurement Items by Status</h5>
                        <small class="text-muted">Purchase requests flow and fulfillment</small>
                    </div>
                    <span class="badge badge-soft-info"><?= $reqCount ?> Items</span>
                </div>
                <div class="reports-chart-body">
                    <canvas id="procurementStatusBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Detailed Tables Section -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-x-auto reports-tabs-nav" id="reportTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold text-nowrap" id="tab-projects-btn" data-bs-toggle="tab" data-bs-target="#tab-projects" type="button" role="tab">
                        <i class="bi bi-folder2-open me-1"></i> Projects
                        <span class="badge bg-primary text-white rounded-pill ms-1"><?= $projectCount ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-nowrap" id="tab-employees-btn" data-bs-toggle="tab" data-bs-target="#tab-employees" type="button" role="tab">
                        <i class="bi bi-people me-1"></i> Workforce
                        <span class="badge bg-secondary-subtle text-body rounded-pill ms-1"><?= $empCount ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-nowrap" id="tab-requirements-btn" data-bs-toggle="tab" data-bs-target="#tab-requirements" type="button" role="tab">
                        <i class="bi bi-cart3 me-1"></i> Procurement
                        <span class="badge bg-secondary-subtle text-body rounded-pill ms-1"><?= $reqCount ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-nowrap" id="tab-fines-btn" data-bs-toggle="tab" data-bs-target="#tab-fines" type="button" role="tab">
                        <i class="bi bi-shield-slash me-1"></i> Fines & Penalties
                        <span class="badge bg-secondary-subtle text-body rounded-pill ms-1"><?= $fineCount ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-nowrap" id="tab-tasks-btn" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button" role="tab">
                        <i class="bi bi-check2-square me-1"></i> Pending Tasks
                        <span class="badge bg-secondary-subtle text-body rounded-pill ms-1"><?= $taskCount ?></span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="reportTabsContent">
                <!-- 1. Projects Tab -->
                <div class="tab-pane fade show active" id="tab-projects" role="tabpanel">
                    <div class="table-responsive oms-table-wrapper">
                        <table class="table align-middle mb-0" id="projectsReportTable">
                            <thead>
                                <tr>
                                    <th class="col-title">Project Name</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Assigned Team</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $p): 
                                    $pStatus = trim((string)($p['status'] ?? 'Not Started'));
                                    $pStatusLower = strtolower($pStatus);
                                    $stBadge = match($pStatusLower) {
                                        'completed', 'delivered', 'finished' => 'badge-soft-success',
                                        'in progress', 'revision' => 'badge-soft-info',
                                        'in review' => 'badge-soft-warning',
                                        'on hold' => 'badge-soft-danger',
                                        default => 'badge-soft-secondary',
                                    };
                                    $priBadge = match(strtolower((string)($p['priority'] ?? ''))) {
                                        'high' => 'badge-soft-danger',
                                        'medium' => 'badge-soft-warning',
                                        default => 'badge-soft-secondary',
                                    };
                                    $progressVal = (int)($p['progress'] ?? 0);
                                ?>
                                    <tr>
                                        <td class="col-title">
                                            <strong><?= e($p['project_name']) ?></strong>
                                            <?php if (!empty($p['source'])): ?>
                                                <span class="badge bg-secondary-subtle text-body ms-1" style="font-size: 10px;"><?= e($p['source']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($p['category'] ?? '—') ?></td>
                                        <td><span class="badge <?= $priBadge ?>"><?= e($p['priority'] ?? 'Medium') ?></span></td>
                                        <td><span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($p['deadline'] ?? '—') ?></span></td>
                                        <td><span class="badge <?= $stBadge ?>"><?= e($pStatus) ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2" style="min-width: 110px;">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar <?= $progressVal >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $progressVal ?>%;"></div>
                                                </div>
                                                <small class="text-muted fw-semibold"><?= $progressVal ?>%</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($p['assigned_names'])): ?>
                                                <div class="d-flex flex-wrap gap-1" style="max-width: 260px;">
                                                    <?php foreach (explode(',', (string)$p['assigned_names']) as $nm): ?>
                                                        <span class="assignee-chip"><?= e(trim($nm)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($projects)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No project records found in this range.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Employees Tab -->
                <div class="tab-pane fade" id="tab-employees" role="tabpanel">
                    <div class="table-responsive oms-table-wrapper">
                        <table class="table align-middle mb-0" id="employeesReportTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th class="col-title">Employee Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Email</th>
                                    <th>Joining Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><span class="badge-code"><?= e($emp['employee_code'] ?? '—') ?></span></td>
                                        <td class="col-title"><strong><?= e($emp['name']) ?></strong></td>
                                        <td><span class="badge badge-soft-primary"><?= e($emp['department'] ?? 'General') ?></span></td>
                                        <td><?= e($emp['designation'] ?? '—') ?></td>
                                        <td>
                                            <a class="email-link text-nowrap" href="mailto:<?= e($emp['email'] ?? '') ?>">
                                                <i class="bi bi-envelope me-1 text-muted"></i><?= e($emp['email'] ?? '—') ?>
                                            </a>
                                        </td>
                                        <td><span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($emp['joining_date'] ?? '—') ?></span></td>
                                        <td>
                                            <span class="badge <?= strtolower((string)($emp['status'] ?? '')) === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' ?>">
                                                <?= e($emp['status'] ?? 'Active') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($employees)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No employee records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. Requirements (Procurement) Tab -->
                <div class="tab-pane fade" id="tab-requirements" role="tabpanel">
                    <div class="table-responsive oms-table-wrapper">
                        <table class="table align-middle mb-0" id="requirementsReportTable">
                            <thead>
                                <tr>
                                    <th class="col-title">Item</th>
                                    <th>Qty</th>
                                    <th>Priority</th>
                                    <th>Required Date</th>
                                    <th>Vendor</th>
                                    <th>Estimated Cost</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $req): 
                                    $reqSt = ucfirst(strtolower((string)($req['status'] ?? 'Pending')));
                                    $reqStBadge = match($reqSt) {
                                        'Purchased', 'Received' => 'badge-soft-success',
                                        'Ordered' => 'badge-soft-info',
                                        default => 'badge-soft-warning',
                                    };
                                ?>
                                    <tr>
                                        <td class="col-title"><strong><?= e($req['item']) ?></strong></td>
                                        <td><?= (int)($req['quantity'] ?? 1) ?></td>
                                        <td><span class="badge <?= strtolower((string)($req['priority'] ?? '')) === 'high' ? 'badge-soft-danger' : 'badge-soft-secondary' ?>"><?= e($req['priority'] ?? 'Medium') ?></span></td>
                                        <td><span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($req['required_date'] ?? '—') ?></span></td>
                                        <td><?= e($req['vendor'] ?? '—') ?></td>
                                        <td><strong><?= money($req['estimated_cost'] ?? 0) ?></strong></td>
                                        <td><span class="badge <?= $reqStBadge ?>"><?= e($reqSt) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($requirements)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No procurement records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Fines & Penalties Tab -->
                <div class="tab-pane fade" id="tab-fines" role="tabpanel">
                    <div class="table-responsive oms-table-wrapper">
                        <table class="table align-middle mb-0" id="finesReportTable">
                            <thead>
                                <tr>
                                    <th class="col-title">Employee</th>
                                    <th class="col-desc">Reason</th>
                                    <th>Fine Date</th>
                                    <th>Amount</th>
                                    <th>Paid Status</th>
                                    <th>Approved By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fines as $fn): 
                                    $isPaid = strtolower((string)($fn['paid'] ?? '')) === 'yes';
                                ?>
                                    <tr>
                                        <td class="col-title"><strong><?= e($fn['employee_name']) ?></strong></td>
                                        <td class="col-desc"><?= nl2br(e($fn['reason'] ?? '—')) ?></td>
                                        <td><span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($fn['fine_date'] ?? '—') ?></span></td>
                                        <td><strong class="text-danger"><?= money($fn['amount'] ?? 0) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $isPaid ? 'badge-soft-success' : 'badge-soft-danger' ?>">
                                                <i class="bi <?= $isPaid ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i><?= $isPaid ? 'Paid' : 'Unpaid' ?>
                                            </span>
                                        </td>
                                        <td><?= e($fn['approved_by'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($fines)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No fine records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5. Tasks Tab -->
                <div class="tab-pane fade" id="tab-tasks" role="tabpanel">
                    <div class="table-responsive oms-table-wrapper">
                        <table class="table align-middle mb-0" id="tasksReportTable">
                            <thead>
                                <tr>
                                    <th class="col-title">Task Title</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Required Date</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $tsk): 
                                    $tStatus = ucfirst(strtolower((string)($tsk['status'] ?? 'Pending')));
                                    $tStBadge = match($tStatus) {
                                        'Completed' => 'badge-soft-success',
                                        'In progress' => 'badge-soft-info',
                                        default => 'badge-soft-warning',
                                    };
                                ?>
                                    <tr>
                                        <td class="col-title"><strong><?= e($tsk['task_title']) ?></strong></td>
                                        <td><?= e($tsk['category'] ?? '—') ?></td>
                                        <td><span class="badge <?= strtolower((string)($tsk['priority'] ?? '')) === 'high' ? 'badge-soft-danger' : 'badge-soft-secondary' ?>"><?= e($tsk['priority'] ?? 'Medium') ?></span></td>
                                        <td><span class="text-nowrap text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($tsk['required_date'] ?? '—') ?></span></td>
                                        <td><?= e($tsk['assigned_to'] ?? '—') ?></td>
                                        <td><span class="badge <?= $tStBadge ?>"><?= e($tStatus) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No task records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Chart.js Script Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    var textColor = isDark ? '#94a3b8' : '#64748b';
    var gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

    // Global Chart Defaults
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
    Chart.defaults.font.size = 12;

    // 1. Projects Status Bar Chart
    var statusLabels = <?= json_encode(array_keys($projectStatusCounts)) ?>;
    var statusData = <?= json_encode(array_values($projectStatusCounts)) ?>;
    var statusColors = [
        '#3b82f6', // In Progress (blue)
        '#10b981', // Completed (green)
        '#f59e0b', // In Review (amber)
        '#64748b', // Not Started (slate)
        '#ef4444', // On Hold (red)
        '#8b5cf6'  // Revision (purple)
    ];

    var ctxStatus = document.getElementById('projectStatusBarChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Projects',
                    data: statusData,
                    backgroundColor: statusColors,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: textColor },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Department Headcount Doughnut Chart
    var deptLabels = <?= json_encode(array_keys($deptCounts)) ?>;
    var deptData = <?= json_encode(array_values($deptCounts)) ?>;
    var deptPalette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#14b8a6', '#6366f1'];

    var ctxDept = document.getElementById('departmentDoughnutChart');
    if (ctxDept) {
        new Chart(ctxDept, {
            type: 'doughnut',
            data: {
                labels: deptLabels.length > 0 ? deptLabels : ['General'],
                datasets: [{
                    data: deptData.length > 0 ? deptData : [1],
                    backgroundColor: deptPalette.slice(0, Math.max(1, deptLabels.length)),
                    borderWidth: 2,
                    borderColor: isDark ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, padding: 12, color: textColor }
                    }
                },
                cutout: '68%'
            }
        });
    }

    // 3. Project Priorities Chart
    var priLabels = <?= json_encode(array_keys($projectPriorityCounts)) ?>;
    var priData = <?= json_encode(array_values($projectPriorityCounts)) ?>;
    var ctxPri = document.getElementById('projectPriorityChart');
    if (ctxPri) {
        new Chart(ctxPri, {
            type: 'doughnut',
            data: {
                labels: priLabels,
                datasets: [{
                    data: priData,
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6'],
                    borderWidth: 2,
                    borderColor: isDark ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 12, color: textColor }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // 4. Procurement Status Horizontal Bar Chart
    var reqLabels = <?= json_encode(array_keys($reqStatusCounts)) ?>;
    var reqData = <?= json_encode(array_values($reqStatusCounts)) ?>;
    var ctxReq = document.getElementById('procurementStatusBarChart');
    if (ctxReq) {
        new Chart(ctxReq, {
            type: 'bar',
            data: {
                labels: reqLabels,
                datasets: [{
                    label: 'Items',
                    data: reqData,
                    backgroundColor: ['#f59e0b', '#06b6d4', '#10b981', '#3b82f6'],
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: textColor },
                        grid: { color: gridColor }
                    },
                    y: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // CSV Exporter for active table
    var exportBtn = document.getElementById('exportCurrentCsvBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            var activeTabPane = document.querySelector('.tab-pane.active table');
            if (!activeTabPane) return;
            
            var rows = Array.from(activeTabPane.querySelectorAll('tr'));
            var csv = rows.map(function(r) {
                var cols = Array.from(r.querySelectorAll('th, td')).map(function(c) {
                    var txt = c.innerText.replace(/"/g, '""').trim();
                    return '"' + txt + '"';
                });
                return cols.join(',');
            }).join('\n');

            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'OMS_Report_' + new Date().toISOString().slice(0, 10) + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
});
</script>
