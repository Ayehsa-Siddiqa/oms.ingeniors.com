<main class="public-dashboard">
    <header class="public-hero">
        <div class="public-brand-block">
            <img class="public-logo" src="<?= asset('images/ingeniors-dark-logo.png') ?>" alt="INGENIORS logo">
            <p class="public-kicker">Engineering Operations</p>
            <h1>INGENIORS Live Dashboard</h1>
            <span>Auto-refreshes every 60 seconds</span>
        </div>
        <div class="public-clock">
            <strong data-live-clock><?= e(date('h:i A')) ?></strong>
            <span><?= e(date('l, M d, Y')) ?></span>
        </div>
    </header>

    <section class="public-grid">
        <div class="public-panel public-panel-wide public-active-projects">
            <div class="public-panel-title">
                <h2>Active Projects</h2>
                <span>Deadline ordered</span>
            </div>
            <?php
            ob_start();
            foreach ($activeProjects as $project): ?>
                <tr>
                    <td><strong><?= e($project['project_name']) ?></strong></td>
                    <td><?= e($project['assigned_to']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= e($project['status']) ?></span></td>
                    <td><?= e($project['priority']) ?></td>
                    <td><?= e($project['deadline']) ?></td>
                    <td>
                        <div class="public-progress-cell">
                            <span><?= (int) $project['progress'] ?>%</span>
                            <div class="progress"><div class="progress-bar" data-progress="<?= (int) $project['progress'] ?>"></div></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach;
            if (!$activeProjects): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No active projects.</td></tr>
            <?php endif;
            $publicTbody = ob_get_clean();

            component('table', [
                'tableClass' => 'table table-lg align-middle mb-0 public-project-table',
                'headers' => ['Project', 'Assigned To', 'Status', 'Priority', 'Deadline', 'Progress'],
                'slot' => $publicTbody,
            ]);
            ?>

        </div>

        <div class="public-panel">
            <div class="public-panel-title">
                <h2>Pending Tasks</h2>
                <span>General work</span>
            </div>
            <div class="public-list">
                <?php foreach ($pendingTasks as $task): ?>
                    <div class="public-list-row">
                        <div>
                            <strong><?= e($task['task_title']) ?></strong>
                            <span><?= e($task['category']) ?> · <?= e($task['assigned_to']) ?> · <?= e($task['required_date']) ?></span>
                        </div>
                        <em><?= e($task['status']) ?></em>
                    </div>
                <?php endforeach; ?>
                <?php if (!$pendingTasks): ?>
                    <div class="public-empty">No pending tasks.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="public-panel">
            <div class="public-panel-title">
                <h2>Pending Purchases</h2>
                <span>Office requirements</span>
            </div>
            <div class="public-list">
                <?php foreach ($pendingPurchases as $purchase): ?>
                    <div class="public-list-row">
                        <div>
                            <strong><?= e($purchase['item']) ?></strong>
                            <span><?= e($purchase['quantity']) ?> qty · <?= e($purchase['required_date']) ?></span>
                        </div>
                        <em><?= e($purchase['status']) ?></em>
                    </div>
                <?php endforeach; ?>
                <?php if (!$pendingPurchases): ?>
                    <div class="public-empty">No pending purchases.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="public-panel">
            <div class="public-panel-title">
                <h2>Fine Details</h2>
                <span>Unpaid records</span>
            </div>
            <div class="public-list">
                <?php foreach ($unpaidFines as $fine): ?>
                    <div class="public-list-row">
                        <div>
                            <strong><?= e($fine['employee_name']) ?></strong>
                            <span><?= e($fine['reason']) ?></span>
                        </div>
                        <em><?= money($fine['amount']) ?></em>
                    </div>
                <?php endforeach; ?>
                <?php if (!$unpaidFines): ?>
                    <div class="public-empty">No unpaid fines.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
