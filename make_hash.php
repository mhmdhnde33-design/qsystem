<?php
// Temporary helper to generate bcrypt hash for employee passwords.
// Use once, copy the output, then delete this file.

$password = $_GET['password'] ?? '';
if ($password === '') {
    echo 'Usage: make_hash.php?password=YOUR_PASSWORD';
    exit;
}

echo password_hash($password, PASSWORD_BCRYPT);

