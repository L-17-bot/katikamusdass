<?php
// submit.php - handle public application form
require_once __DIR__ . '/config.php';

function respond($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Method not allowed');
}

// Honeypot
$hp = $_POST['hp_name'] ?? '';
if (!empty($hp)) {
    respond(false, 'Spam detected');
}

$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$fullname = trim($firstname . ' ' . $lastname);
$email = trim($_POST['email'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$age = null;
if ($dob) {
    $dob_ts = strtotime($dob);
    if ($dob_ts !== false) {
        $age = (int)floor((time() - $dob_ts) / (31556926));
    }
}
$student_class = trim($_POST['student_class'] ?? '');
$parent_contact = trim($_POST['parent_contact'] ?? '');
$former_school = trim($_POST['former_school'] ?? '');
$results = trim($_POST['results'] ?? '');

if ($firstname === '' || $lastname === '' || $email === '') {
    respond(false, 'First name, last name and email are required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Invalid email address');
}

// Rate limiting (simple)
if (!isset($_SESSION['last_submit_time'])) $_SESSION['last_submit_time'] = 0;
if (time() - $_SESSION['last_submit_time'] < 5) {
    respond(false, 'You are submitting too quickly. Please wait a few seconds.');
}
$_SESSION['last_submit_time'] = time();

try {
    $stmt = $pdo->prepare("INSERT INTO applicants
        (fullname, email, gender, age, student_class, parent_contact, former_school, results)
        VALUES (:fullname, :email, :gender, :age, :student_class, :parent_contact, :former_school, :results)");
    $stmt->execute([
        ':fullname' => $fullname,
        ':email' => $email,
        ':gender' => $gender,
        ':age' => $age ?: null,
        ':student_class' => $student_class,
        ':parent_contact' => $parent_contact,
        ':former_school' => $former_school,
        ':results' => $results,
    ]);
    // capture the inserted applicant id so we can save uploaded files
    $applicantId = $pdo->lastInsertId();
    // handle file uploads (attachments[])
    if (!empty($_FILES['attachments'])) {
        // Validation rules
        $allowedExt = ['jpg','jpeg','png','gif','pdf','doc','docx'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB per file
        $maxFiles = 5;

        // count files safely
        $fileCount = 0;
        if (is_array($_FILES['attachments']['name'])) {
            foreach ($_FILES['attachments']['name'] as $name) {
                if ($name !== '') $fileCount++;
            }
        }
        if ($fileCount > $maxFiles) {
            respond(false, "Too many files uploaded. Maximum is {$maxFiles} files.");
        }
        $uploadBase = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploadBase)) mkdir($uploadBase, 0755, true);
        $targetDir = $uploadBase . DIRECTORY_SEPARATOR . $applicantId;
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $files = $_FILES['attachments'];
        // Prepare attachment insert
        $attachStmt = $pdo->prepare("INSERT INTO attachments
            (applicant_id, stored_name, original_name, mime, size, path)
            VALUES (:applicant_id, :stored_name, :original_name, :mime, :size, :path)");

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $orig = basename($files['name'][$i]);
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                $destName = sprintf('%s_%s.%s', $safe, bin2hex(random_bytes(6)), $ext);
                $dest = $targetDir . DIRECTORY_SEPARATOR . $destName;
                if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                    // store metadata in attachments table
                    $relPath = 'uploads/' . $applicantId . '/' . $destName;
                    try {
                        $attachStmt->execute([
                            ':applicant_id' => $applicantId,
                            ':stored_name' => $destName,
                            ':original_name' => $orig,
                            ':mime' => $files['type'][$i] ?? null,
                            ':size' => $files['size'][$i] ?? null,
                            ':path' => $relPath,
                        ]);
                    } catch (Exception $e) {
                        error_log('Failed to insert attachment record: ' . $e->getMessage());
                    }
                } else {
                    error_log("Failed to move uploaded file: {$files['name'][$i]}");
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("DB Insert error: " . $e->getMessage());
    respond(false, 'Server error, please try again later');
}

respond(true, 'Application submitted successfully. We will contact you by email.');
