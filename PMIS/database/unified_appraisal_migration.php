<?php
require_once __DIR__ . '/../config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS performance_appraisals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    appraisal_type ENUM('academic', 'non_academic') NOT NULL,
    period_from DATE NOT NULL,
    period_to DATE NOT NULL,
    status ENUM('draft', 'pending_hod', 'pending_staff_review', 'pending_dean', 'pending_hr', 'pending_committee', 'completed', 'rejected') DEFAULT 'draft',
    form_data LONGTEXT NULL COMMENT 'Stores full JSON of all form parts',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "Successfully created unified `performance_appraisals` table!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
