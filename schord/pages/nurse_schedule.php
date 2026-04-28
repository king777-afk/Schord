<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// NURSE ONLY
if (strtolower($user['role']) !== 'nurse') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';
$edit_schedule = null;

// Add or Update Schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = isset($_POST['student_id']) ? sanitize($_POST['student_id']) : '';
    $schedule_date = isset($_POST['schedule_date']) ? sanitize($_POST['schedule_date']) : '';
    $schedule_time = isset($_POST['schedule_time']) ? sanitize($_POST['schedule_time']) : '';
    $reason = isset($_POST['reason']) ? sanitize($_POST['reason']) : '';
    $priority = isset($_POST['priority']) ? sanitize($_POST['priority']) : 'normal';
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';

    if (empty($student_id) || empty($schedule_date) || empty($schedule_time) || empty($reason)) {
        $error = '❌ All required fields must be filled';
    } else {
        // Combine date and time
        $schedule_datetime = $schedule_date . ' ' . $schedule_time;

        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = sanitize($_POST['edit_id']);
            if ($conn->query("UPDATE nurse_schedules SET student_id='$student_id', schedule_date='$schedule_datetime', reason='$reason', priority='$priority', notes='$notes' WHERE id='$edit_id'")) {
                $success = '✅ Schedule updated successfully!';
                $_POST = array();
                $edit_schedule = null;
            } else {
                $error = '❌ Error updating schedule: ' . $conn->error;
            }
        } else {
            if ($conn->query("INSERT INTO nurse_schedules (student_id, schedule_date, reason, priority, notes, status) VALUES ('$student_id', '$schedule_datetime', '$reason', '$priority', '$notes', 'pending')")) {
                $success = '✅ Schedule created successfully!';
                $_POST = array();
            } else {
                $error = '❌ Error creating schedule: ' . $conn->error;
            }
        }
    }
}

// Delete Schedule
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM nurse_schedules WHERE id='$id'")) {
        $success = '✅ Schedule deleted successfully!';
    } else {
        $error = '❌ Error deleting schedule';
    }
}

// Update Schedule Status
if (isset($_POST['update_status'])) {
    $id = sanitize($_POST['schedule_id']);
    $status = sanitize($_POST['new_status']);
    if ($conn->query("UPDATE nurse_schedules SET status='$status' WHERE id='$id'")) {
        $success = '✅ Status updated successfully!';
    } else {
        $error = '❌ Error updating status';
    }
}

// Get schedule to edit
if (isset($_GET['edit'])) {
    $id = sanitize($_GET['edit']);
    $result = $conn->query("SELECT * FROM nurse_schedules WHERE id='$id'");
    if ($result && $result->num_rows > 0) {
        $edit_schedule = $result->fetch_assoc();
    }
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

$query = "SELECT ns.*, s.name, s.student_no FROM nurse_schedules ns 
          JOIN students s ON ns.student_id = s.id WHERE 1=1";

if ($search) {
    $query .= " AND (s.name LIKE '%$search%' OR s.student_no LIKE '%$search%' OR ns.reason LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND ns.status = '$status_filter'";
}
if ($date_filter) {
    $query .= " AND DATE(ns.schedule_date) = '$date_filter'";
}

$query .= " ORDER BY ns.schedule_date DESC";
// Safe query execution with error handling
$schedules = false;
$schedules_result = $conn->query($query);
if ($schedules_result) {
    $schedules = $schedules_result;
}

// Get statistics - safely handle potential query failures
$total_result = $conn->query("SELECT COUNT(*) as count FROM nurse_schedules");
$total_schedules = ($total_result && $total_result->num_rows > 0) ? $total_result->fetch_assoc()['count'] : 0;

$pending_result = $conn->query("SELECT COUNT(*) as count FROM nurse_schedules WHERE status='pending'");
$pending_schedules = ($pending_result && $pending_result->num_rows > 0) ? $pending_result->fetch_assoc()['count'] : 0;

$completed_result = $conn->query("SELECT COUNT(*) as count FROM nurse_schedules WHERE status='completed'");
$completed_schedules = ($completed_result && $completed_result->num_rows > 0) ? $completed_result->fetch_assoc()['count'] : 0;

$high_priority_result = $conn->query("SELECT COUNT(*) as count FROM nurse_schedules WHERE priority='high' AND status='pending'");
$high_priority = ($high_priority_result && $high_priority_result->num_rows > 0) ? $high_priority_result->fetch_assoc()['count'] : 0;

// Get all students for dropdown
$students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name ASC");
$students_data = [];
if ($students && $students->num_rows > 0) {
    while ($row = $students->fetch_assoc()) {
        $students_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Nurse Dashboard - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0891b2;
            --primary-light: #06b6d4;
            --primary-dark: #0e7490;
            --accent: #00d9ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #082f49;
            --darker: #051e2a;
            --text-dark: #0c2340;
            --text-light: #64748b;
            --bg-light: #ecf9ff;
            --bg-card: #ffffff;
            --border-color: #e0f2fe;
            --shadow: 0 10px 40px rgba(8, 47, 73, 0.1);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #ecf9ff 0%, #cff0f9 50%, #e0f9ff 100%);
            color: var(--text-dark);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
        }

        h1 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .alerts {
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 15px;
            animation: slideIn 0.3s ease;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border-top: 3px solid var(--primary);
        }

        .stat-card.warning {
            border-top-color: var(--warning);
        }

        .stat-card.danger {
            border-top-color: var(--danger);
        }

        .stat-card h3 {
            color: var(--text-light);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(8, 145, 178, 0.3);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 600px;
            overflow-y: auto;
        }

        .schedule-item {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }

        .schedule-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(8, 145, 178, 0.15);
        }

        .schedule-item.high-priority {
            border-left-color: var(--danger);
            background: rgba(239, 68, 68, 0.05);
        }

        .schedule-item.completed {
            opacity: 0.6;
            background: rgba(16, 185, 129, 0.05);
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .schedule-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 15px;
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .priority-badge.high {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-badge.normal {
            background: #dbeafe;
            color: #1e40af;
        }

        .priority-badge.low {
            background: #dbeafe;
            color: #0c4a6e;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .schedule-details {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .schedule-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .schedule-actions button {
            padding: 6px 12px;
            font-size: 12px;
        }

        .search-filters {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-filters input,
        .search-filters select {
            padding: 10px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .search-filters {
                grid-template-columns: 1fr;
            }
        }

        .schedules-container {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📅 Schedule Management</h1>
            <p style="color: var(--text-light);">View and manage patient schedules</p>
        </header>

        <?php if ($success || $error): ?>
            <div class="alerts">
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Schedules</h3>
                <div class="number"><?php echo $total_schedules; ?></div>
            </div>
            <div class="stat-card warning">
                <h3>Pending</h3>
                <div class="number"><?php echo $pending_schedules; ?></div>
            </div>
            <div class="stat-card">
                <h3>Completed</h3>
                <div class="number"><?php echo $completed_schedules; ?></div>
            </div>
            <div class="stat-card danger">
                <h3>High Priority</h3>
                <div class="number"><?php echo $high_priority; ?></div>
            </div>
        </div>

        <div class="main-content">
            <!-- Create/Edit Form -->
            <div class="card">
                <div class="card-header">
                    ✏️ <?php echo $edit_schedule ? 'Edit Schedule' : 'Create New Schedule'; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_schedule): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_schedule['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="student_id">Student *</label>
                            <select name="student_id" id="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students_data as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                        <?php echo ($edit_schedule && $edit_schedule['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name'] . ' (' . $student['student_no'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="schedule_date">Date *</label>
                                <input type="date" name="schedule_date" id="schedule_date" required
                                    value="<?php echo $edit_schedule ? explode(' ', $edit_schedule['schedule_date'])[0] : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="schedule_time">Time *</label>
                                <input type="time" name="schedule_time" id="schedule_time" required
                                    value="<?php echo $edit_schedule ? explode(' ', $edit_schedule['schedule_date'])[1] : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reason">Reason for Visit *</label>
                            <input type="text" name="reason" id="reason" placeholder="e.g., Follow-up, Check-up, Vaccination" required
                                value="<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['reason']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select name="priority" id="priority">
                                <option value="low" <?php echo ($edit_schedule && $edit_schedule['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="normal" <?php echo (!$edit_schedule || $edit_schedule['priority'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                                <option value="high" <?php echo ($edit_schedule && $edit_schedule['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Additional Notes</label>
                            <textarea name="notes" id="notes" placeholder="Any additional notes..."><?php echo ($edit_schedule && !empty($edit_schedule['notes'])) ? htmlspecialchars($edit_schedule['notes']) : ''; ?></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn-primary">
                                <?php echo $edit_schedule ? '✅ Update Schedule' : '➕ Create Schedule'; ?>
                            </button>
                            <?php if ($edit_schedule): ?>
                                <button type="button" class="btn-secondary" onclick="location.href='nurse_schedule.php';">Cancel</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Schedules List -->
            <div class="card">
                <div class="card-header">
                    📋 Scheduled Appointments
                </div>
                <div class="card-body">
                    <div class="search-filters">
                        <form method="GET" style="display: contents;">
                            <input type="text" name="search" placeholder="Search patient or reason..." value="<?php echo htmlspecialchars($search); ?>">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                            <button type="submit" class="btn-primary full-width">🔍 Filter</button>
                        </form>
                    </div>

                    <div class="schedule-list">
                        <?php if ($schedules && $schedules->num_rows > 0): ?>
                            <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                <div class="schedule-item <?php echo ($schedule['priority'] ?? 'normal') == 'high' ? 'high-priority' : ''; ?> <?php echo ($schedule['status'] ?? 'pending') == 'completed' ? 'completed' : ''; ?>">
                                    <div class="schedule-header">
                                        <div class="schedule-title">
                                            🏥 <?php echo htmlspecialchars($schedule['name'] ?? 'Unknown'); ?>
                                            <span style="color: var(--text-light); font-weight: 400; font-size: 12px;">
                                                (<?php echo htmlspecialchars($schedule['student_no'] ?? 'N/A'); ?>)
                                            </span>
                                        </div>
                                        <div>
                                            <span class="priority-badge <?php echo $schedule['priority'] ?? 'normal'; ?>">
                                                <?php echo ucfirst($schedule['priority'] ?? 'normal'); ?>
                                            </span>
                                            <span class="status-badge <?php echo $schedule['status'] ?? 'pending'; ?>">
                                                <?php echo ucfirst($schedule['status'] ?? 'pending'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="schedule-details">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($schedule['reason'] ?? 'N/A'); ?><br>
                                        <strong>Date & Time:</strong> <?php echo date('M d, Y - g:i A', strtotime($schedule['schedule_date'] ?? '2026-01-01 00:00:00')); ?><br>
                                        <?php if (!empty($schedule['notes'])): ?>
                                            <strong>Notes:</strong> <?php echo htmlspecialchars($schedule['notes']); ?><br>
                                        <?php endif; ?>
                                    </div>
                                    <div class="schedule-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <select name="new_status" onchange="this.form.submit();" style="padding: 6px; font-size: 12px; inline-size: auto;">
                                                <option value="pending" <?php echo $schedule['status'] == 'pending' ? 'selected' : ''; ?>>Mark Pending</option>
                                                <option value="completed" <?php echo $schedule['status'] == 'completed' ? 'selected' : ''; ?>>Mark Completed</option>
                                            </select>
                                        </form>
                                        <button class="btn-primary" onclick="location.href='nurse_schedule.php?edit=<?php echo $schedule['id']; ?>';">✏️ Edit</button>
                                        <button class="btn-danger" onclick="if(confirm('Are you sure?')) location.href='nurse_schedule.php?delete=<?php echo $schedule['id']; ?>';">🗑️ Delete</button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-light);">
                                <p style="font-size: 48px; margin-bottom: 10px;">📭</p>
                                <p>No schedules found. Create one to get started!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
