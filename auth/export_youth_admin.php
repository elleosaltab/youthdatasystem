<?php 
require_once __DIR__ . '/../config/db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

$sql = "SELECT 
        first_name, 
        middle_name, 
        last_name, 
        birth_date, 
        age, 
        gender, 
        address, 
        barangay,  
        municipality, 
        phone, 
        email, 
        status, 
        school, 
        course_grade, 
        work
    FROM kk_members
    ORDER BY municipality, barangay, last_name
";

$res = $conn->query($sql);

if (!$res) {
    die("Query failed: " . $conn->error);
}

$data = $res->fetch_all(MYSQLI_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray([
    'First Name',
    'Middle Name',
    'Last Name',
    'Birth Date',
    'Age',
    'Gender',
    'Address',
    'Barangay',
    'Municipality',
    'Phone',
    'Email',
    'Status',
    'School',
    'Course / Grade',
    'Work'
], NULL, 'A1');

$row = 2;
foreach ($data as $y) {
    $sheet->setCellValue("A$row", $y['first_name']);
    $sheet->setCellValue("B$row", $y['middle_name']);
    $sheet->setCellValue("C$row", $y['last_name']);
    $sheet->setCellValue("D$row", $y['birth_date']);
    $sheet->setCellValue("E$row", $y['age']);
    $sheet->setCellValue("F$row", $y['gender']);
    $sheet->setCellValue("G$row", $y['address']);
    $sheet->setCellValue("H$row", $y['barangay']);
    $sheet->setCellValue("I$row", $y['municipality']);
    $sheet->setCellValue("J$row", $y['phone']);
    $sheet->setCellValue("K$row", $y['email']);
    $sheet->setCellValue("L$row", $y['status']);
    $sheet->setCellValue("M$row", $y['school']);
    $sheet->setCellValue("N$row", $y['course_grade']);
    $sheet->setCellValue("O$row", $y['work']);
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="All_Registered_Youth.xlsx"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
