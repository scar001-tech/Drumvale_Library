<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="book_import_template.csv"');

// Create CSV template
$output = fopen('php://output', 'w');

// Add header row
fputcsv($output, [
    'accession_number',
    'title',
    'author',
    'subject',
    'category',
    'total_copies',
    'shelf_location',
    'isbn',
    'publisher',
    'publication_year',
    'price'
]);

// Add sample rows
fputcsv($output, [
    'ACC000100',
    'Mathematics Form 1',
    'KLB Publishers',
    'Mathematics',
    'Textbook',
    '50',
    'A1-Math',
    '978-9966-00-123-4',
    'Kenya Literature Bureau',
    '2023',
    '450.00'
]);

fputcsv($output, [
    'ACC000101',
    'Things Fall Apart',
    'Chinua Achebe',
    'Literature',
    'Novel',
    '25',
    'C3-Lit',
    '978-0-385-47454-2',
    'Penguin Books',
    '1994',
    '350.00'
]);

fputcsv($output, [
    '',
    'Biology Form 3',
    'Longhorn Publishers',
    'Biology',
    'Textbook',
    '40',
    'D1-Sci',
    '',
    'Longhorn Publishers',
    '2022',
    '520.00'
]);

fclose($output);
exit();
?>
