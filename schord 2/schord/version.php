<?php
header('Content-Type: application/json; charset=utf-8');

$commit = getenv('RAILWAY_GIT_COMMIT_SHA')
    ?: getenv('RAILWAY_GIT_COMMIT')
    ?: 'unknown';

echo json_encode([
    'app' => 'SCHORD',
    'commit' => $commit,
    'deployed_marker' => 'staff-menu-header-fix',
    'generated_at_utc' => gmdate('c')
], JSON_UNESCAPED_SLASHES);
