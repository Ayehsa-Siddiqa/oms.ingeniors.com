<?php

class ReportsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('reports');
        BaseModel::runGlobalAutoMaintenance();

        $db = Database::connection();
        $preset = $_GET['preset'] ?? '';
        
        if ($preset === 'this_month') {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        } elseif ($preset === 'last_30') {
            $from = date('Y-m-d', strtotime('-30 days'));
            $to = date('Y-m-d');
        } elseif ($preset === 'this_year') {
            $from = date('Y-01-01');
            $to = date('Y-12-31');
        } elseif ($preset === 'all') {
            $from = '2020-01-01';
            $to = date('Y-m-d', strtotime('+1 year'));
        } else {
            $from = $_GET['from'] ?? date('Y-01-01');
            $to = $_GET['to'] ?? date('Y-m-d');
        }

        // 1. Projects with assigned members
        $stmtProjects = $db->prepare('
            SELECT p.*,
                   GROUP_CONCAT(e.name ORDER BY e.name ASC SEPARATOR ", ") AS assigned_names
            FROM projects p
            LEFT JOIN project_assignments pa ON pa.project_id = p.id
            LEFT JOIN employees e ON e.id = pa.employee_id
            WHERE p.deleted_at IS NULL
              AND (
                  (p.created_at IS NOT NULL AND DATE(p.created_at) BETWEEN :from1 AND :to1)
                  OR (p.deadline IS NOT NULL AND DATE(p.deadline) BETWEEN :from2 AND :to2)
                  OR (p.start_date IS NOT NULL AND DATE(p.start_date) BETWEEN :from3 AND :to3)
              )
            GROUP BY p.id
            ORDER BY p.deadline ASC
        ');
        $stmtProjects->execute([
            'from1' => $from, 'to1' => $to,
            'from2' => $from, 'to2' => $to,
            'from3' => $from, 'to3' => $to,
        ]);
        $projects = $stmtProjects->fetchAll();

        // If range returns nothing (e.g. initial demo setup), fetch all active projects
        if (empty($projects)) {
            $projects = $db->query('
                SELECT p.*,
                       GROUP_CONCAT(e.name ORDER BY e.name ASC SEPARATOR ", ") AS assigned_names
                FROM projects p
                LEFT JOIN project_assignments pa ON pa.project_id = p.id
                LEFT JOIN employees e ON e.id = pa.employee_id
                WHERE p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY p.deadline ASC
            ')->fetchAll();
        }

        // 2. Employees & Department distribution
        $employees = $db->query('SELECT * FROM employees WHERE deleted_at IS NULL ORDER BY department ASC, name ASC')->fetchAll();
        
        // 3. Requirements (Procurement)
        $requirements = $db->query('SELECT * FROM requirements WHERE deleted_at IS NULL ORDER BY required_date DESC')->fetchAll();
        
        // 4. Employee Fines
        $fines = $db->query('SELECT * FROM employee_fines WHERE deleted_at IS NULL ORDER BY fine_date DESC')->fetchAll();

        // 5. Tasks
        $tasks = $db->query('SELECT * FROM pending_tasks WHERE deleted_at IS NULL ORDER BY required_date ASC')->fetchAll();

        // --- Aggregations & Analytics ---

        // Project Status Counts
        $projectStatusCounts = [
            'In Progress' => 0,
            'Completed' => 0,
            'In Review' => 0,
            'Not Started' => 0,
            'On Hold' => 0,
            'Revision' => 0,
        ];
        $projectPriorityCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];
        $projectSourceCounts = [];
        $totalProjectProgress = 0;

        foreach ($projects as $p) {
            $st = trim((string)($p['status'] ?? 'Not Started'));
            if (in_array(strtolower($st), ['delivered', 'finished', 'completed'])) {
                $projectStatusCounts['Completed'] = ($projectStatusCounts['Completed'] ?? 0) + 1;
            } elseif (isset($projectStatusCounts[$st])) {
                $projectStatusCounts[$st]++;
            } else {
                $projectStatusCounts[$st] = ($projectStatusCounts[$st] ?? 0) + 1;
            }

            $pri = ucfirst(strtolower(trim((string)($p['priority'] ?? 'Medium'))));
            if (isset($projectPriorityCounts[$pri])) {
                $projectPriorityCounts[$pri]++;
            } else {
                $projectPriorityCounts['Medium']++;
            }

            $src = trim((string)($p['source'] ?? 'Direct')) ?: 'Direct';
            $projectSourceCounts[$src] = ($projectSourceCounts[$src] ?? 0) + 1;

            $totalProjectProgress += (int)($p['progress'] ?? 0);
        }

        $avgProjectProgress = count($projects) > 0 ? (int)round($totalProjectProgress / count($projects)) : 0;

        // Department Distribution
        $deptCounts = [];
        foreach ($employees as $e) {
            $d = trim((string)($e['department'] ?? 'General')) ?: 'General';
            $deptCounts[$d] = ($deptCounts[$d] ?? 0) + 1;
        }

        // Requirements Cost Calculations
        $reqTotalCost = 0;
        $reqPurchasedCost = 0;
        $reqPendingCost = 0;
        $reqStatusCounts = ['Pending' => 0, 'Ordered' => 0, 'Purchased' => 0, 'Received' => 0];

        foreach ($requirements as $r) {
            $cost = (float)($r['estimated_cost'] ?? 0);
            $reqTotalCost += $cost;
            $rst = ucfirst(strtolower(trim((string)($r['status'] ?? 'Pending'))));
            if (in_array($rst, ['Purchased', 'Received'])) {
                $reqPurchasedCost += $cost;
            } else {
                $reqPendingCost += $cost;
            }
            if (isset($reqStatusCounts[$rst])) {
                $reqStatusCounts[$rst]++;
            } else {
                $reqStatusCounts['Pending']++;
            }
        }

        // Fines Calculations
        $finesTotal = 0;
        $finesPaid = 0;
        $finesUnpaid = 0;
        foreach ($fines as $f) {
            $amt = (float)($f['amount'] ?? 0);
            $finesTotal += $amt;
            if (strtolower(trim((string)($f['paid'] ?? ''))) === 'yes') {
                $finesPaid += $amt;
            } else {
                $finesUnpaid += $amt;
            }
        }

        // Tasks Calculations
        $tasksCompleted = 0;
        $tasksPending = 0;
        foreach ($tasks as $t) {
            if (strtolower(trim((string)($t['status'] ?? ''))) === 'completed') {
                $tasksCompleted++;
            } else {
                $tasksPending++;
            }
        }

        $this->view('reports/index', [
            'title' => 'Reports & Analytics',
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'projects' => $projects,
            'employees' => $employees,
            'requirements' => $requirements,
            'fines' => $fines,
            'tasks' => $tasks,
            'projectStatusCounts' => $projectStatusCounts,
            'projectPriorityCounts' => $projectPriorityCounts,
            'projectSourceCounts' => $projectSourceCounts,
            'avgProjectProgress' => $avgProjectProgress,
            'deptCounts' => $deptCounts,
            'reqTotalCost' => $reqTotalCost,
            'reqPurchasedCost' => $reqPurchasedCost,
            'reqPendingCost' => $reqPendingCost,
            'reqStatusCounts' => $reqStatusCounts,
            'finesTotal' => $finesTotal,
            'finesPaid' => $finesPaid,
            'finesUnpaid' => $finesUnpaid,
            'tasksCompleted' => $tasksCompleted,
            'tasksPending' => $tasksPending,
        ]);
    }
}

