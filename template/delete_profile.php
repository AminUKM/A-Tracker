<?php
session_start();
include 'db.php';
if (!isset($_SESSION['matric'])) { header("Location: Login.php"); exit(); }
$matric = $_SESSION['matric'];

// Delete user from Table_Students and optionally related tables
$stmt = $conn->prepare("DELETE FROM Table_Students WHERE matric_number = ?");
$stmt->execute([$matric]);
// Optionally delete from other tables (targets, subjects, etc.)

session_destroy();
header("Location: Login.php");
exit();
