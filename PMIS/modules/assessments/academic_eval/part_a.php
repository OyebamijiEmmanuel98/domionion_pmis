<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ACADEMIC APPRAISAL - PART A (Staff Input)
 * =====================================================
 * Staff fills: Personal details, teaching, research, publications
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireLogin();

$evalId = $_GET['id'] ?? 0;
$errors = [];

// Get evaluation with staff info
$evalStmt = $pdo->prepare("
    SELECT ae.*, s.first_name, s.last_name, s.staff_id AS sid, s.date_of_birth AS staff_dob,
           s.marital_status AS staff_marital, s.rank, s.post, s.date_recruited, s.department_id,
           d.department_name
    FROM academic_evaluations ae
    JOIN staff s ON ae.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE ae.id = ?
");
$evalStmt->execute([$evalId]);
$eval = $evalStmt->fetch();

if (!$eval) {
    setFlashMessage('error', 'Evaluation not found');
    header("Location: list.php");
    exit();
}

if ($eval['status'] !== 'part_a_pending') {
    setFlashMessage('info', 'Part A has already been completed');
    header("Location: view.php?id=" . $evalId);
    exit();
}

$pageTitle = 'Academic Appraisal — Part A';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Part A' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $data = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?? null,
            'age' => intval($_POST['age'] ?? 0),
            'marital_status' => sanitizeInput($_POST['marital_status'] ?? ''),
            'faculty' => sanitizeInput($_POST['faculty'] ?? ''),
            'department' => sanitizeInput($_POST['department'] ?? ''),
            'date_first_appointment' => $_POST['date_first_appointment'] ?? null,
            'grade_first_appointment' => sanitizeInput($_POST['grade_first_appointment'] ?? ''),
            'present_rank' => sanitizeInput($_POST['present_rank'] ?? ''),
            'date_present_rank' => $_POST['date_present_rank'] ?? null,
            'next_below_rank' => sanitizeInput($_POST['next_below_rank'] ?? ''),
            'teaching_summary' => sanitizeInput($_POST['teaching_summary'] ?? ''),
            'research_summary' => sanitizeInput($_POST['research_summary'] ?? ''),
            'admin_duties' => sanitizeInput($_POST['admin_duties'] ?? ''),
            'community_service' => sanitizeInput($_POST['community_service'] ?? '')
        ];
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Update main record
                $updateSql = "UPDATE academic_evaluations SET 
                    title=?, date_of_birth=?, age=?, marital_status=?, faculty=?, department=?,
                    date_first_appointment=?, grade_first_appointment=?, present_rank=?, date_present_rank=?,
                    next_below_rank=?, teaching_summary=?, research_summary=?, admin_duties=?, community_service=?,
                    part_a_signed_at=NOW(), part_a_signed_by=?, status='part_b_pending'
                    WHERE id=?";
                $pdo->prepare($updateSql)->execute([
                    $data['title'], $data['date_of_birth'], $data['age'], $data['marital_status'],
                    $data['faculty'], $data['department'], $data['date_first_appointment'],
                    $data['grade_first_appointment'], $data['present_rank'], $data['date_present_rank'],
                    $data['next_below_rank'], $data['teaching_summary'], $data['research_summary'],
                    $data['admin_duties'], $data['community_service'],
                    getCurrentUserId(), $evalId
                ]);
                
                // Save qualifications
                $pdo->prepare("DELETE FROM ae_qualifications WHERE evaluation_id = ?")->execute([$evalId]);
                if (!empty($_POST['qual_degree'])) {
                    $qualStmt = $pdo->prepare("INSERT INTO ae_qualifications (evaluation_id, degree, class_grade, institution, year_obtained) VALUES (?,?,?,?,?)");
                    foreach ($_POST['qual_degree'] as $i => $deg) {
                        if (!empty($deg)) {
                            $qualStmt->execute([$evalId, $deg, $_POST['qual_grade'][$i] ?? '', $_POST['qual_institution'][$i] ?? '', $_POST['qual_year'][$i] ?? null]);
                        }
                    }
                }
                
                // Save courses taught
                $pdo->prepare("DELETE FROM ae_courses_taught WHERE evaluation_id = ?")->execute([$evalId]);
                if (!empty($_POST['course_code'])) {
                    $courseStmt = $pdo->prepare("INSERT INTO ae_courses_taught (evaluation_id, course_code, course_title, level, semester) VALUES (?,?,?,?,?)");
                    foreach ($_POST['course_code'] as $i => $code) {
                        if (!empty($code)) {
                            $courseStmt->execute([$evalId, $code, $_POST['course_title'][$i] ?? '', $_POST['course_level'][$i] ?? '', $_POST['course_semester'][$i] ?? '']);
                        }
                    }
                }
                
                // Save publications
                $pdo->prepare("DELETE FROM ae_publications WHERE evaluation_id = ?")->execute([$evalId]);
                if (!empty($_POST['pub_title'])) {
                    $pubStmt = $pdo->prepare("INSERT INTO ae_publications (evaluation_id, pub_type, title, authors, journal_name, year_published) VALUES (?,?,?,?,?,?)");
                    foreach ($_POST['pub_title'] as $i => $title) {
                        if (!empty($title)) {
                            $pubStmt->execute([$evalId, $_POST['pub_type'][$i] ?? 'journal', $title, $_POST['pub_authors'][$i] ?? '', $_POST['pub_journal'][$i] ?? '', $_POST['pub_year'][$i] ?? null]);
                        }
                    }
                }
                
                // Save graduate students
                $pdo->prepare("DELETE FROM ae_graduate_students WHERE evaluation_id = ?")->execute([$evalId]);
                if (!empty($_POST['gs_name'])) {
                    $gsStmt = $pdo->prepare("INSERT INTO ae_graduate_students (evaluation_id, student_name, programme, thesis_title, status) VALUES (?,?,?,?,?)");
                    foreach ($_POST['gs_name'] as $i => $name) {
                        if (!empty($name)) {
                            $gsStmt->execute([$evalId, $name, $_POST['gs_programme'][$i] ?? '', $_POST['gs_thesis'][$i] ?? '', $_POST['gs_status'][$i] ?? 'ongoing']);
                        }
                    }
                }
                
                $pdo->commit();
                logActivity('FILL_ACADEMIC_EVAL_PART_A', 'academic_evaluations', $evalId, 'Completed Part A');
                setFlashMessage('success', 'Part A submitted successfully. Awaiting HOD assessment (Part B).');
                header("Location: list.php");
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Part A Error: " . $e->getMessage());
                $errors[] = 'Error saving Part A. Please try again.';
            }
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<style>
.appraisal-form { max-width: 950px; margin: 0 auto; }
.part-header { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: #fff; padding: 1.5rem 2rem; border-radius: 12px 12px 0 0; }
.part-header h2 { margin: 0; font-size: 1.2rem; }
.part-header p { margin: 0.5rem 0 0; opacity: 0.85; font-size: 0.85rem; }
.part-body { background: #fff; padding: 2rem; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; margin-bottom: 1.5rem; }
.section-title { font-size: 1rem; font-weight: 700; color: #1e3a5f; margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #edf2f7; display: flex; align-items: center; gap: 0.5rem; }
.section-title i { color: #3182ce; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.full-width { grid-column: 1 / -1; }
.dynamic-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
.dynamic-table th, .dynamic-table td { padding: 8px 10px; border: 1px solid #e2e8f0; font-size: 0.85rem; }
.dynamic-table th { background: #f7fafc; color: #4a5568; font-weight: 600; text-align: left; }
.dynamic-table input, .dynamic-table select { width: 100%; padding: 6px 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.85rem; }
.btn-add-row { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; background: #ebf8ff; color: #2b6cb0; border: 1px solid #bee3f8; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; margin-top: 0.5rem; }
.btn-add-row:hover { background: #bee3f8; }
.btn-remove-row { color: #e53e3e; cursor: pointer; border: none; background: none; font-size: 1rem; }
@media (max-width: 640px) { .form-grid, .form-grid-3 { grid-template-columns: 1fr; } }
</style>

<div class="appraisal-form">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:20px;"><?php foreach ($errors as $e): ?><li><?php echo escapeOutput($e); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo csrfField(); ?>
        
        <div class="part-header">
            <h2><i class="fas fa-graduation-cap"></i> ANNUAL APPRAISAL FOR ACADEMIC STAFF — PART A</h2>
            <p>DOMINION UNIVERSITY, IBADAN · To be completed by individual member of staff</p>
        </div>
        
        <div class="part-body">
            <!-- Personal Information -->
            <h3 class="section-title"><i class="fas fa-user"></i> Personal Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <select name="title" class="form-control">
                        <option value="">-- Select --</option>
                        <?php foreach(['Prof.','Dr.','Mr.','Mrs.','Ms.','Engr.'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo ($eval['title'] ?? '') == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" value="<?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name']); ?>" readonly style="background:#f7fafc;">
                </div>
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo $eval['staff_dob'] ?? ''; ?>" onchange="calcAge(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" id="ageField" class="form-control" value="<?php echo $eval['age'] ?? ''; ?>" readonly style="background:#f7fafc;">
                </div>
                <div class="form-group">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-control">
                        <option value="">-- Select --</option>
                        <?php foreach(['Single','Married','Divorced','Widowed'] as $ms): ?>
                            <option value="<?php echo $ms; ?>" <?php echo ($eval['staff_marital'] ?? '') == $ms ? 'selected' : ''; ?>><?php echo $ms; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Employment Details -->
            <h3 class="section-title"><i class="fas fa-briefcase"></i> Employment Details</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Faculty</label>
                    <input type="text" name="faculty" class="form-control" value="<?php echo escapeOutput($eval['faculty'] ?? ''); ?>" placeholder="e.g. Faculty of Science">
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?php echo escapeOutput($eval['department_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Date of First Appointment</label>
                    <input type="date" name="date_first_appointment" class="form-control" value="<?php echo $eval['date_recruited'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Grade on First Appointment</label>
                    <input type="text" name="grade_first_appointment" class="form-control" value="<?php echo escapeOutput($eval['grade_first_appointment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Present Rank</label>
                    <input type="text" name="present_rank" class="form-control" value="<?php echo escapeOutput($eval['rank'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Date of Present Rank</label>
                    <input type="date" name="date_present_rank" class="form-control" value="<?php echo $eval['date_present_rank'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Rank Next Below Present Rank</label>
                    <input type="text" name="next_below_rank" class="form-control" value="<?php echo escapeOutput($eval['next_below_rank'] ?? ''); ?>">
                </div>
            </div>
            
            <!-- Qualifications -->
            <h3 class="section-title"><i class="fas fa-award"></i> Academic Qualifications</h3>
            <table class="dynamic-table" id="qualTable">
                <thead><tr><th>Degree/Qualification</th><th>Class/Grade</th><th>Institution</th><th>Year</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="qual_degree[]" placeholder="e.g. B.Sc Computer Science"></td>
                        <td><input type="text" name="qual_grade[]" placeholder="e.g. First Class"></td>
                        <td><input type="text" name="qual_institution[]" placeholder="University"></td>
                        <td><input type="number" name="qual_year[]" placeholder="2020" min="1950" max="2030"></td>
                        <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn-add-row" onclick="addQualRow()"><i class="fas fa-plus"></i> Add Qualification</button>
            
            <!-- Teaching -->
            <h3 class="section-title"><i class="fas fa-chalkboard-teacher"></i> Teaching Activities</h3>
            <div class="form-group">
                <label class="form-label">Summary of Teaching Activities During Period</label>
                <textarea name="teaching_summary" class="form-control" rows="3" placeholder="Describe your teaching activities..."><?php echo escapeOutput($eval['teaching_summary'] ?? ''); ?></textarea>
            </div>
            
            <h4 style="color:#4a5568;font-size:0.9rem;margin-top:1rem;">Courses Taught</h4>
            <table class="dynamic-table" id="courseTable">
                <thead><tr><th>Code</th><th>Title</th><th>Level</th><th>Semester</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="course_code[]" placeholder="CSC301"></td>
                        <td><input type="text" name="course_title[]" placeholder="Course title"></td>
                        <td><input type="text" name="course_level[]" placeholder="300L"></td>
                        <td><select name="course_semester[]"><option>1st</option><option>2nd</option></select></td>
                        <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn-add-row" onclick="addCourseRow()"><i class="fas fa-plus"></i> Add Course</button>
            
            <!-- Research -->
            <h3 class="section-title"><i class="fas fa-flask"></i> Research & Publications</h3>
            <div class="form-group">
                <label class="form-label">Summary of Research Activities</label>
                <textarea name="research_summary" class="form-control" rows="3" placeholder="Describe your research work..."><?php echo escapeOutput($eval['research_summary'] ?? ''); ?></textarea>
            </div>
            
            <h4 style="color:#4a5568;font-size:0.9rem;margin-top:1rem;">Publications</h4>
            <table class="dynamic-table" id="pubTable">
                <thead><tr><th>Type</th><th>Title</th><th>Authors</th><th>Journal/Venue</th><th>Year</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><select name="pub_type[]"><option value="journal">Journal</option><option value="conference">Conference</option><option value="book">Book</option><option value="chapter">Chapter</option><option value="other">Other</option></select></td>
                        <td><input type="text" name="pub_title[]" placeholder="Title"></td>
                        <td><input type="text" name="pub_authors[]" placeholder="Authors"></td>
                        <td><input type="text" name="pub_journal[]" placeholder="Journal name"></td>
                        <td><input type="number" name="pub_year[]" placeholder="2024" min="1950" max="2030"></td>
                        <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn-add-row" onclick="addPubRow()"><i class="fas fa-plus"></i> Add Publication</button>
            
            <!-- Graduate Students -->
            <h4 style="color:#4a5568;font-size:0.9rem;margin-top:1.5rem;">Graduate Students Supervised</h4>
            <table class="dynamic-table" id="gsTable">
                <thead><tr><th>Student Name</th><th>Programme</th><th>Thesis Title</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="gs_name[]" placeholder="Name"></td>
                        <td><select name="gs_programme[]"><option value="MSc">M.Sc</option><option value="PhD">Ph.D</option><option value="MPhil">M.Phil</option><option value="PGD">PGD</option></select></td>
                        <td><input type="text" name="gs_thesis[]" placeholder="Thesis title"></td>
                        <td><select name="gs_status[]"><option value="ongoing">Ongoing</option><option value="completed">Completed</option></select></td>
                        <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn-add-row" onclick="addGsRow()"><i class="fas fa-plus"></i> Add Student</button>
            
            <!-- Admin & Community -->
            <h3 class="section-title"><i class="fas fa-building"></i> Administrative & Community Service</h3>
            <div class="form-group">
                <label class="form-label">Administrative Duties</label>
                <textarea name="admin_duties" class="form-control" rows="3" placeholder="List administrative duties held..."><?php echo escapeOutput($eval['admin_duties'] ?? ''); ?></textarea>
            </div>
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label">Community Service</label>
                <textarea name="community_service" class="form-control" rows="3" placeholder="Community service activities..."><?php echo escapeOutput($eval['community_service'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Part A</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
function calcAge(inp) {
    var dob = new Date(inp.value);
    var today = new Date();
    var age = today.getFullYear() - dob.getFullYear();
    var m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    document.getElementById('ageField').value = age;
}

function addQualRow() {
    var tbody = document.querySelector('#qualTable tbody');
    var row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
}
function addCourseRow() {
    var tbody = document.querySelector('#courseTable tbody');
    var row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
}
function addPubRow() {
    var tbody = document.querySelector('#pubTable tbody');
    var row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
}
function addGsRow() {
    var tbody = document.querySelector('#gsTable tbody');
    var row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
