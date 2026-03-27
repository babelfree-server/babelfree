<?php
header('Content-Type: application/json');

$base = '/home/babelfree.com/public_html/content/literary/';
$allowed = ['macias','carroll','dickinson','whitman','pombo','rivera','carrasquilla','mejia','obeso','acosta','austen','kipling','lear','poe','shakespeare','twain','wilde','woolf'];

$folder = $_POST['folder'] ?? 'macias';
$filename = $_POST['filename'] ?? ($_FILES['file']['name'] ?? 'uploaded-file.txt');

if (!in_array($folder, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid folder']);
    exit;
}

$filename = basename($filename);
$dest = $base . $folder . '/' . $filename;

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    echo json_encode([
        'success' => true,
        'path' => "content/literary/$folder/$filename",
        'size' => filesize($dest)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
