<?php
/**
 * Common Header Include
 * Use this in pages that need consistent header/navigation
 *
 * Usage:
 * require_once 'includes/header.php';
 */

if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dormitory Management System' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/sidebar.css">
    <?php if (isset($custom_css)): ?>
        <link rel="stylesheet" href="css/<?= htmlspecialchars($custom_css) ?>">
    <?php endif; ?>

    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            display: flex;
            background-color: #f0f4f3;
        }
        .main-content {
            margin-left: 225px;
            padding: 30px;
            background-color: #fff;
            min-height: 100vh;
            width: calc(100% - 225px);
            border-left: 2px solid #5A7D7C;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php
        // Display flash messages
        $flash = Session::getMessage();
        if ($flash):
        ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
