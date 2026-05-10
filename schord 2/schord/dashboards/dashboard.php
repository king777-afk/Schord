<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Redirect admin to admin dashboard
if (strtolower($user['role']) === 'admin') {
    header("Location: dashboard_admin.php");
    exit();
}

// Redirect nurse to nurse dashboard
if (strtolower($user['role']) === 'nurse') {
    header("Location: nurse_dashboard.php");
    exit();
}
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$ongoing_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Get recent visits
$recent_visits = $conn->query("SELECT cv.*, s.name as student_name, s.student_no 
    FROM clinic_visits cv 
    JOIN students s ON cv.student_id = s.id 
    ORDER BY cv.visit_date DESC 
    LIMIT 5");
?>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1>👋 Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <p style="color: #666; font-size: 1.1rem;">You are logged in as: <strong><?php echo ucfirst($user['role']); ?></strong></p>
    </div>

    <!-- Dashboard Stats -->
    <div class="grid">
        <div class="stat-card">
            <div style="font-size: 2rem;">👥</div>
            <h3><?php echo $total_students; ?></h3>
            <p>Total Students</p>
        </div>
        <div class="stat-card">
            <div style="font-size: 2rem;">📅</div>
            <h3><?php echo $total_visits; ?></h3>
            <p>Total Visits</p>
        </div>
        <div class="stat-card">
            <div style="font-size: 2rem;">⏳</div>
            <h3><?php echo $ongoing_visits; ?></h3>
            <p>Ongoing Visits</p>
        </div>
        <div class="stat-card">
            <div style="font-size: 2rem;">👥</div>
            <h3><?php echo $total_users; ?></h3>
            <p>Total Staff</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <h2>🚀 Quick Actions</h2>
        <div class="grid" style="margin-top: 2rem;">
            <a href="../pages/students.php" class="btn" style="text-align: center; text-decoration: none;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                Manage Students
            </a>
            <a href="../pages/visits.php" class="btn" style="text-align: center; text-decoration: none;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📝</div>
                Record Visit
            </a>
            <a href="../pages/health_records.php" class="btn" style="text-align: center; text-decoration: none;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>
                Health Records
            </a>
        </div>
    </div>

    <!-- Recent Visits -->
    <div class="card">
        <h2>📊 Recent Clinic Visits</h2>
        <?php if ($recent_visits->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Visit Date</th>
                        <th>Complaint</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($visit = $recent_visits->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($visit['student_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($visit['student_no']); ?></small>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($visit['visit_date'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($visit['complaint'], 0, 50)); ?>...</td>
                            <td>
                                <span class="badge <?php echo ($visit['status'] == 'ongoing') ? 'badge-warning' : 'badge-success'; ?>">
                                    <?php echo ucfirst($visit['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="../pages/visits.php?edit=<?php echo $visit['id']; ?>" class="action-link action-edit">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No recent visits recorded</p>
                <a href="../pages/visits.php" class="btn">Record First Visit</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>