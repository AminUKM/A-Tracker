<?php
include 'db.php';
session_start();
if (!isset($_SESSION['matric'])) { header("Location: Login.php"); exit(); }

if (isset($_POST['subject_code'])) {
    $subject_code = $_POST['subject_code'];

    // Delete the subject for this student
    $stmt = $conn->prepare("DELETE FROM Table_Subjects WHERE matric_number = ? AND subject_code = ?");
    $stmt->execute([$_SESSION['matric'], $subject_code]);

    // Optional: Add a success message via session
    $_SESSION['drop_message'] = "<div class='success-message'>Subject dropped successfully.</div>";
}

header("Location: register_subject.php");
exit();
