<?php
declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'youthdatasys_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$municipalities = [
    "Bagamanoc" => ["Antipolo","Bacak","Bagatabao","Bugao","Cahan","Hinipaan","Magsaysay","Poblacion","Quigaray","Quezon (Pancayanan)","Sagrada","Salvacion (Panuto)","San Isidro","San Rafael (Mahantod)","San Vicente","Santa Mesa","Santa Teresa","Suchan"],
    "Baras" => ["Agban","Abihao","Eastern (Poblacion)","Western (Poblacion)","Puraran","Putsan","Benticayan","Batolinao","Bagong Sirang","Quezon","Rizal","Sagrada","Rural","Salvacion","San Lorenzo","San Miguel","Santa Maria","Tilod","Ginitligan","Paniquihan","Macutal","Moning","Nagbarorong","P. Teston","Danao","Caragumihan","Guinsaanan"],
    "Bato" => ["Aroyao Pequeño","Bagumbayan","Banawang","Batalay","Binanuahan","Bote","Buenavista","Cabugao","Cagraray","Carorian","Guinobatan","Ilawod","Libjo","Libod (Poblacion)","Marinawa","Mintay","Oguis","Pananaogan","San Andres","San Pedro","San Roque","Sta. Isabel","Sibacungan","Sipi","Talisay","Tamburan","Tilis"],
    "Caramoran" => ["Baybay (Pob.)","Bocon","Bothoan (Pob.)","Buenavista","Bulalacao","Camburo","Dariao","Datag East","Datag West","Guiamlong","Hitoma","Icanbato (Pob.)","Inalmasinan","Iyao","Mabini","Maui","Maysuran","Milaviga","Panique","Sabangan","Sabloyon","Salvacion","Supang","Toytoy (Pob.)","Tubli","Tucao","Obi"],
    "Gigmoto" => ["Biong","Dororian","Poblacion District I","Poblacion District II","Poblacion District III","San Pedro","San Vicente","Sicmil","Sioron"],
    "Pandan" => ["Bagawang","Balagñonan","Baldoc","Canlubi","Catamban","Cobo","Hiyop","Lourdes","Lumabao","Libod","Marambong","Napo","Pandan del Norte","Pandan del Sur","Oga","Panuto","Porot (San Jose)","Salvacion (Tariwara)","San Andres (Dinungsuran)","San Isidro (Langob)","San Rafael (Bogtong)","San Roque","Santa Cruz (Catagbacan)","Tabugoc","Tokio","Wagdas"],
    "Panganiban" => ["Alinawan","Babaguan","Bagong Bayan","Burabod","Cabuyoan","Cagdarao","Mabini","Maculiw","Panay","Tapon (Pangcayanan)","Salvacion","San Joaquin","San Jose","San Juan","San Miguel","San Nicolas","San Vicente","Santa Ana","Santa Maria","Santo Tomas","Santo Niño","Santo Cristo","Santo Niño"],
    "San Andres" => ["Agojo","Alibuag","Asgad (Juan M. Alberto)","Bagong Sirang","Barihay","Batong Paloway","Belmonte (Poblacion)","Bislig","Bon-ot","Cabungahan","Cabcab","Carangag","Catagbacan","Codon","Comagaycay","Datag","Divino Rostro (Poblacion)","Esperanza (Poblacion)","Hilawan","Lictin","Lubas","Manambrag","Mayngaway","Palawig","Puting Baybay","Rizal","Salvacion (Poblacion)","San Isidro","San Jose","San Roque (Poblacion)","San Vicente","Santa Cruz (Poblacion)","Sapang Palay (Poblacion)","Tibang","Timbaan","Tominawog","Wagdas (Poblacion)","Yocti"],
    "San Miguel" => ["Atsan (District I)","Balatohan","Boton","Buhi","Dayawa","J. M. Alberto","Katipunan","Kilikilihan","Mabato","Obo","Pacogon","Pagsangahan","Pangilao","Paraiso","Poblacion District II","Poblacion District III","Progreso","Salvacion (Patagan)","San Juan (Aroyao)","San Marcos","Santa Elena (Patagan)","Siay","Solong","Tobrehon"],
    "Viga" => ["Ananong","Asuncion","Batohonan","Begonia","Botinagan","Buenavista","Mabini","Magsaysay","Ogbong","Peñafrancia","Quirino","San Isidro (Poblacion)","San Jose (Poblacion)","San Pedro (Poblacion)","Santa Rosa","Soboc","Tambongon","Tinago","Villa Aurora","San Roque (Poblacion)","San Vicente (Poblacion)"],
    "Virac" => ["Antipolo del Norte","Antipolo del Sur","Balite","Batag","Bigaa","Buenavista","Buyo","Cabihian","Calabnigan","Calampong","Calatagan Proper","Cawit","Calatagan Tibang","Capilihan","Casoocan","Cavinitan","Concepcion (Poblacion)","Constantino (Poblacion)","Danicop","Dugui Wala","Dugui Too","F. Tacorda Village","Francia (Poblacion)","Gogon Centro","Gogon Sirangan","Hawan Grande","Hawan Ilaya","Hicming","Igang","GMA Poniton","Lanao (Poblacion)","Mislagan","Magnesia del Norte","Magnesia del Sur","Marcelo Alberto (Poblacion)","Marilima","Pajo Baguio","Pajo San Isidro","Palnab del Norte","Palnab del Sur","Palta Salvacion","Palta Small","Rawis (Poblacion)","Salvacion","San Isidro Village","San Jose (Poblacion)","San Juan (Poblacion)","San Pablo (Poblacion)","San Pedro (Poblacion)","San Roque (Poblacion)","San Vicente","Ibong Sapa (San Vicente Sur)","Santa Cruz (Poblacion)","Santa Elena (Poblacion)","Sto. Cristo","Sto. Domingo","Sto. Niño","Simamla","Sogod Bliss","Sogod-Simamla","Talisoy","Sogod-Tibgao","Tubaon","Valencia"]
];

try {
   
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
    $conn->set_charset('utf8mb4');
    $conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($DB_NAME);
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            barangay VARCHAR(100),
            municipality VARCHAR(100),
            role ENUM('superadmin','admin','sk','youth') NOT NULL DEFAULT 'youth',
            is_approved TINYINT(1) NOT NULL DEFAULT 0,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS sk_officials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            barangay VARCHAR(100),
            municipality VARCHAR(100),
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            position VARCHAR(100),
            term_start DATE,
            term_end DATE,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100),
            role VARCHAR(50),
            action VARCHAR(100),
            description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
 $defaultEmail = 'superadmin@youthsystem.com';
    $defaultPass = 'SuperAdmin@123';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $defaultEmail);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $hashed = password_hash($defaultPass, PASSWORD_DEFAULT);

        $insert = $conn->prepare("
            INSERT INTO users (email, password, first_name, last_name, role, is_approved, is_verified)
            VALUES (?, ?, 'System', 'Admin', 'superadmin', 1, 1)
        ");
        $insert->bind_param('ss', $defaultEmail, $hashed);
        $insert->execute();
        $insert->close();

        error_log('SUPERADMIN CREATED');


        echo "✅ Superadmin created! Email: <b>$defaultEmail</b>, Password: <b>$defaultPass</b><br>";
    } else {
        $stmt->close();
    }

} catch (mysqli_sql_exception $e) {
    die("❌ Database setup failed (MySQLi): " . $e->getMessage());
}

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "❌ Database connection failed (PDO): " . $e->getMessage()]));
}
?>
