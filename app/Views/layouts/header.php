<?php
$appName = $GLOBALS['config']['app']['name'] ?? 'AeroBook';
$title = isset($title) ? $title . ' | ' . $appName : $appName;
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#183251">
    <title><?= $escape($title) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/login" aria-label="AeroBook home">AeroBook</a>
    <span class="header-note">Your journey starts here</span>
</header>
<main class="page-main">
