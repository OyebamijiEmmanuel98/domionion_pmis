<?php
require_once __DIR__ . '/../config/db.php';

$leaves = [
    ['Short Leave', 5, 'Short period of absence for personal errands'],
    ['Annual Leave', 30, 'Yearly vacation entitlement'],
    ['Sick Leave', 14, 'Leave granted for medical reasons'],
    ['Maternity Leave', 90, 'Leave for expectant and new mothers'],
    ['Paternity Leave', 14, 'Leave for new fathers'],
    ['Study Leave', 0, 'Leave granted for educational pursuits'],
    ['Sabbatical Leave', 0, 'Extended leave for research or study, typically for academic staff'],
    ['Casual Leave', 5, 'Brief leave for unforeseen circumstances'],
    ['Compassionate / Bereavement Leave', 7, 'Leave granted due to the death of a close relative'],
    ['Marriage Leave', 7, 'Leave for an employee\'s wedding'],
    ['Duty / Official Leave', 0, 'Leave granted for official assignments outside the university'],
    ['Conference Leave', 0, 'Leave to attend professional conferences or seminars'],
    ['Research Leave', 0, 'Leave strictly for academic research purposes'],
    ['Examination Leave', 0, 'Leave to prepare for and take examinations'],
    ['Leave Without Pay', 0, 'Approved absence without salary'],
    ['Terminal Leave', 0, 'Leave taken shortly before retirement or resignation']
];

try {
    $pdo->beginTransaction();
    
    // Optional: Only clear if you definitely want to wipe existing.
    // The screenshot showed "No leave types found" so it's likely empty.
    
    $stmt = $pdo->prepare("INSERT INTO leave_types (leave_name, max_days, description, created_at) VALUES (?, ?, ?, NOW())");
    
    foreach ($leaves as $leave) {
        $stmt->execute([$leave[0], $leave[1], $leave[2]]);
    }
    
    $pdo->commit();
    echo "Successfully inserted all requested leave types.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
