<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if admin - redirect based on role
if (strtolower($user['role']) !== 'admin') {
    if (strtolower($user['role']) === 'nurse') {
        header("Location: nurse_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$success = '';
$error = '';
$edit_student = null;

// Add or Update Student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_no = sanitize($_POST['student_no']);
    $name = sanitize($_POST['name']);
    $course = sanitize($_POST['course']);
    $age = sanitize($_POST['age']);

    if (empty($student_no) || empty($name) || empty($course) || empty($age)) {
        $error = '❌ All fields are required';
    } elseif (!is_numeric($age) || $age < 1 || $age > 100) {
        $error = '❌ Age must be between 1 and 100';
    } else {
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = sanitize($_POST['edit_id']);
            if ($conn->query("UPDATE students SET student_no='$student_no', name='$name', course='$course', age='$age' WHERE id='$edit_id'")) {
                $success = '✅ Student updated successfully!';
                $_POST = array();
                $edit_student = null;
            } else {
                $error = '❌ Error updating student';
            }
        } else {
            $check = $conn->query("SELECT id FROM students WHERE student_no='$student_no'");
            if ($check->num_rows > 0) {
                $error = '❌ Student number already exists';
            } else {
                if ($conn->query("INSERT INTO students (student_no, name, course, age) VALUES ('$student_no', '$name', '$course', $age)")) {
                    $success = '✅ Student added successfully!';
                    $_POST = array();
                } else {
                    $error = '❌ Error adding student';
                }
            }
        }
    }
}

// Delete Student
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM students WHERE id='$id'")) {
        $success = '✅ Student deleted successfully!';
    } else {
        $error = '❌ Error deleting student';
    }
}

// Get student data
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$students = $conn->query("SELECT * FROM students ORDER BY name ASC");

if (isset($_GET['edit'])) {
    $id = sanitize($_GET['edit']);
    $result = $conn->query("SELECT * FROM students WHERE id='$id'");
    if ($result && $result->num_rows > 0) {
        $edit_student = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
    <style>
        /* Hide any stray CSS text that might appear */
        body::before,
        body::after,
        .container::before,
        .container::after {
            display: none !important;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #6366f1;
            --accent: #ec4899;
            --success: #10b981;
            --danger: #ef4444;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
        }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3f2 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid;
            font-weight: 600;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); border-color: var(--success); color: var(--success); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger); }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3); }
        .btn-danger { background: var(--danger); color: white; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
        th { background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.05)); padding: 16px 20px; text-align: left; font-weight: 700; border-bottom: 2px solid var(--border-color); }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border-color); }
        tbody tr:hover { background: #f9fafb; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-card { background: var(--bg-card); padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🎓 Manage Students</h1>
            <a href="dashboard_admin.php">← Back to Dashboard</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="form-card">
            <h2><?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?></h2>
            <form method="POST">
                <?php if ($edit_student): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_student['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Student Number</label>
                        <input type="text" name="student_no" value="<?php echo $_POST['student_no'] ?? ($edit_student['student_no'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo $_POST['name'] ?? ($edit_student['name'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Course</label>
                        <input type="text" name="course" value="<?php echo $_POST['course'] ?? ($edit_student['course'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" value="<?php echo $_POST['age'] ?? ($edit_student['age'] ?? ''); ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $edit_student ? 'Update Student' : 'Add Student'; ?></button>
                <?php if ($edit_student): ?>
                    <a href="admin_students.php" class="btn" style="background: #e2e8f0; color: var(--text-dark);">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>Students List (<?php echo $total_students; ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Student No.</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Age</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students && $students->num_rows > 0): ?>
                    <?php while ($s = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['student_no']); ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['course']); ?></td>
                            <td><?php echo $s['age']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 8px 14px; font-size: 12px;">Edit</a>
                                <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-danger" style="padding: 8px 14px; font-size: 12px;" onclick="return confirm('Delete this student?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">No students found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
