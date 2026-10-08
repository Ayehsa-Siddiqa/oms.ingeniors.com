<?php

class PublicDashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $data = [
            'title' => 'INGENIORS Dashboard',
            'activeProjects' => $db->query(
                'SELECT projects.*, COALESCE(assignments.assigned_to, "Unassigned") AS assigned_to
                 FROM projects
                 LEFT JOIN (
                    SELECT project_assignments.project_id, GROUP_CONCAT(employees.name ORDER BY employees.name SEPARATOR ", ") AS assigned_to
                    FROM project_assignments
                    JOIN employees ON employees.id = project_assignments.employee_id
                    GROUP BY project_assignments.project_id
                 ) assignments ON assignments.project_id = projects.id
                 WHERE projects.status != "Delivered"
                 ORDER BY projects.deadline ASC
                 LIMIT 10'
            )->fetchAll(),
            'pendingPurchases' => $db->query('SELECT * FROM requirements WHERE status IN ("Pending", "Ordered") ORDER BY required_date ASC LIMIT 8')->fetchAll(),
            'pendingTasks' => $db->query('SELECT * FROM pending_tasks WHERE status IN ("Pending", "In Progress") ORDER BY required_date ASC, created_at DESC LIMIT 8')->fetchAll(),
            'unpaidFines' => $db->query('SELECT * FROM employee_fines WHERE paid = "No" ORDER BY fine_date DESC LIMIT 8')->fetchAll(),
        ];

        $this->view('public/dashboard', $data, 'public');
    }
}
