<?php
// includes/auth.php

require_once 'config.php';

// Ensure this is at the top
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('redirectIfNotLoggedIn')) {
    function redirectIfNotLoggedIn() {
        if (!isLoggedIn()) {
            header("Location: " . BASE_URL . "/index.php");
            exit();
        }
    }
}

if (!function_exists('redirectIfNotAdmin')) {
    function redirectIfNotAdmin() {
        redirectIfNotLoggedIn();
        if (!isAdmin()) {
            header("Location: " . BASE_URL . "/user_dashboard.php");
            exit();
        }
    }
}

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "/dashboard.php");
        exit();
    }
}