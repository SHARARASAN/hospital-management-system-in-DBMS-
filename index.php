<?php
// Database Connection
$host = 'localhost';
$db = 'hmisphp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $pat_fname = $_POST['pat_fname'];
    $pat_lname = $_POST['pat_lname'];
    $pat_age = $_POST['pat_age'];
    $pat_addr = $_POST['pat_addr'];
    $pat_phone = $_POST['pat_phone'] ?? null;
    $pat_email = $_POST['pat_email'] ?? null;

    $sql = "INSERT INTO his_patients (pat_fname, pat_lname, pat_age, pat_addr, pat_phone, pat_email) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pat_fname, $pat_lname, $pat_age, $pat_addr, $pat_phone, $pat_email]);

    header("Location: index.php?page=patients");
    exit();
}

// Handle Appointment Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $pat_id = $_POST['pat_id'];
    $doc_id = $_POST['doc_id'];
    $app_date = $_POST['app_date'];
    $app_time = $_POST['app_time'];
    $app_reason = $_POST['app_reason'];
    
    // Get doctor's name
    $stmt = $pdo->prepare("SELECT CONCAT(doc_fname, ' ', doc_lname) AS doc_name FROM his_docs WHERE doc_id = ?");
    $stmt->execute([$doc_id]);
    $doctor = $stmt->fetch();
    $doctor_name = $doctor['doc_name'];
    
    // Get current patient history
    $stmt = $pdo->prepare("SELECT pat_history FROM his_patients WHERE pat_id = ?");
    $stmt->execute([$pat_id]);
    $patient = $stmt->fetch();
    
    // Decode existing history or create new
    $history = $patient['pat_history'] ? json_decode($patient['pat_history'], true) : ['visits' => []];
    
    // Add new visit
    $new_visit = [
        'date' => $app_date,
        'time' => $app_time,
        'doctor' => $doctor_name,
        'reason' => $app_reason,
        'status' => 'Scheduled'
    ];
    $history['visits'][] = $new_visit;
    
    // Update patient record
    $stmt = $pdo->prepare("UPDATE his_patients SET pat_history = ? WHERE pat_id = ?");
    $stmt->execute([json_encode($history), $pat_id]);
    
    header("Location: index.php?page=patients");
    exit();
}

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $subject = $_POST['subject'] ?? null;
    $message = $_POST['message'];

    $sql = "INSERT INTO contact_messages (name, email, phone, subject, message) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $phone, $subject, $message]);

    header("Location: index.php?page=contact");
    exit();
}


$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        header { background-color: #333; color: #fff; padding: 20px 0; text-align: center; }
        nav { background-color: #444; padding: 10px; text-align: center; }
        nav ul { list-style-type: none; margin: 0; padding: 0; }
        nav ul li { display: inline; margin: 0 15px; }
        nav ul li a { color: #fff; text-decoration: none; font-weight: bold; }
        nav ul li a:hover { color:rgb(26, 214, 220); }
        main { padding: 20px; margin-bottom: 60px; }
        footer { background-color: #333; color: #fff; text-align: center; padding: 10px 0; position: fixed; bottom: 0; width: 100%; }
        .section { background-color: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .section h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background-color:rgb(239, 227, 227); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; 
            box-sizing: border-box; font-size: 16px;
        }
        .form-group textarea { min-height: 100px; }
        .form-group button { 
            padding: 12px 24px; background-color: #4CAF50; color: white; 
            border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        .form-group button:hover { background-color: #45a049; }
        .status-scheduled { color: #2196F3; }
        .status-completed { color: #4CAF50; }
        .status-cancelled { color: #f44336; }
        .visit-details { display: none; margin-top: 10px; }
        .visit-details table { background-color: #f9f9f9; }
        .toggle-details { cursor: pointer; color:rgb(139, 7, 221); }
        .toggle-details:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header>
        <h1>Hospital Management System</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php?page=home">Home</a></li>
            <li><a href="index.php?page=patients">Patients</a></li>
            <li><a href="index.php?page=doctors">Doctors</a></li>
            <li><a href="index.php?page=pharmaceuticals">Pharmaceuticals</a></li>
            <li><a href="index.php?page=contact">Contact Us</a></li>
        </ul>
    </nav>
    <main>
        <?php
        switch ($page) {
            case 'patients':
                $patients = $pdo->query("SELECT * FROM his_patients ORDER BY pat_id DESC")->fetchAll();
                $doctors = $pdo->query("SELECT doc_id, CONCAT(doc_fname, ' ', doc_lname) AS doc_name FROM his_docs")->fetchAll();
                ?>
                <div class="section">
                    <h2>Patients</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): 
                                $history = $patient['pat_history'] ? json_decode($patient['pat_history'], true) : ['visits' => []];
                                $visit_count = count($history['visits']);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($patient['pat_id']) ?></td>
                                    <td><?= htmlspecialchars($patient['pat_fname'] . ' ' . $patient['pat_lname']) ?></td>
                                    <td><?= htmlspecialchars($patient['pat_age']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($patient['pat_phone'] ?? 'N/A') ?><br>
                                        <?= htmlspecialchars($patient['pat_email'] ?? 'N/A') ?>
                                    </td>
                                    <td><?= htmlspecialchars($patient['pat_addr']) ?></td>
                                    <td>
                                        <?php if ($visit_count > 0): ?>
                                            <span class="toggle-details" onclick="toggleVisitDetails(<?= $patient['pat_id'] ?>)">
                                                <?= $visit_count ?> visit(s)
                                            </span>
                                            <div id="details-<?= $patient['pat_id'] ?>" class="visit-details">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Time</th>
                                                            <th>Doctor</th>
                                                            <th>Reason</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($history['visits'] as $visit): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($visit['date']) ?></td>
                                                                <td><?= htmlspecialchars($visit['time']) ?></td>
                                                                <td><?= htmlspecialchars($visit['doctor']) ?></td>
                                                                <td><?= htmlspecialchars($visit['reason']) ?></td>
                                                                <td class="status-<?= strtolower($visit['status']) ?>">
                                                                    <?= htmlspecialchars($visit['status']) ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            No visits
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <h2>Add New Patient</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="pat_fname">First Name:</label>
                            <input type="text" id="pat_fname" name="pat_fname" required>
                        </div>
                        <div class="form-group">
                            <label for="pat_lname">Last Name:</label>
                            <input type="text" id="pat_lname" name="pat_lname" required>
                        </div>
                        <div class="form-group">
                            <label for="pat_age">Age:</label>
                            <input type="number" id="pat_age" name="pat_age" required min="0" max="120">
                        </div>
                        <div class="form-group">
                            <label for="pat_addr">Address:</label>
                            <input type="text" id="pat_addr" name="pat_addr" required>
                        </div>
                        <div class="form-group">
                            <label for="pat_phone">Phone:</label>
                            <input type="tel" id="pat_phone" name="pat_phone">
                        </div>
                        <div class="form-group">
                            <label for="pat_email">Email:</label>
                            <input type="email" id="pat_email" name="pat_email">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_patient">Add Patient</button>
                        </div>
                    </form>
                </div>

                <div class="section">
                    <h2>Book Appointment</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="app_pat_id">Patient:</label>
                            <select id="app_pat_id" name="pat_id" required>
                                <option value="">Select Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?= $patient['pat_id'] ?>">
                                        <?= htmlspecialchars($patient['pat_fname'] . ' ' . $patient['pat_lname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="doc_id">Doctor:</label>
                            <select id="doc_id" name="doc_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['doc_id'] ?>">
                                        <?= htmlspecialchars($doctor['doc_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="app_date">Date:</label>
                            <input type="date" id="app_date" name="app_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="app_time">Time:</label>
                            <input type="time" id="app_time" name="app_time" required>
                        </div>
                        <div class="form-group">
                            <label for="app_reason">Reason:</label>
                            <textarea id="app_reason" name="app_reason" required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="book_appointment">Book Appointment</button>
                        </div>
                    </form>
                </div>

                <script>
                function toggleVisitDetails(patId) {
                    const detailsDiv = document.getElementById(`details-${patId}`);
                    if (detailsDiv.style.display === 'none' || !detailsDiv.style.display) {
                        detailsDiv.style.display = 'block';
                    } else {
                        detailsDiv.style.display = 'none';
                    }
                }
                </script>
                <?php
                break;

            case 'doctors':
                $doctors = $pdo->query("
                    SELECT d.*, dept.dept_name 
                    FROM his_docs d
                    JOIN departments dept ON d.dept_id = dept.dept_id
                    ORDER BY d.doc_id DESC
                ")->fetchAll();
                ?>
                <div class="section">
                    <h2>Doctors</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doctor['doc_id']) ?></td>
                                    <td><?= htmlspecialchars($doctor['doc_fname'] . ' ' . $doctor['doc_lname']) ?></td>
                                    <td><?= htmlspecialchars($doctor['doc_email']) ?></td>
                                    <td><?= htmlspecialchars($doctor['doc_phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($doctor['dept_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                break;

            case 'pharmaceuticals':
                $pharmaceuticals = $pdo->query("
                    SELECT p.*, cat.cat_name 
                    FROM his_pharmaceuticals p
                    JOIN pharmaceutical_categories cat ON p.cat_id = cat.cat_id
                    ORDER BY p.phar_id DESC
                ")->fetchAll();
                ?>
                <div class="section">
                    <h2>Pharmaceuticals</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Barcode</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Supplier</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pharmaceuticals as $pharma): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pharma['phar_id']) ?></td>
                                    <td><?= htmlspecialchars($pharma['phar_name']) ?></td>
                                    <td><?= htmlspecialchars($pharma['phar_bcode']) ?></td>
                                    <td><?= htmlspecialchars($pharma['cat_name']) ?></td>
                                    <td><?= htmlspecialchars($pharma['phar_qty']) ?></td>
                                    <td>$<?= number_format($pharma['phar_price'], 2) ?></td>
                                    <td><?= htmlspecialchars($pharma['supplier'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($pharma['expiry_date'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                break;

            case 'contact':
                $messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
                ?>
                <div class="section">
                    <h2>Contact Us</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text" id="subject" name="subject">
                        </div>
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="send_message">Send Message</button>
                        </div>
                    </form>
                </div>

                <div class="section">
                    <h2>Messages Received</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?= htmlspecialchars($message['msg_id']) ?></td>
                                    <td><?= htmlspecialchars($message['name']) ?></td>
                                    <td><?= htmlspecialchars($message['email']) ?></td>
                                    <td><?= htmlspecialchars($message['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($message['subject'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...</td>
                                    <td><?= date('M j, Y', strtotime($message['created_at'])) ?></td>
                                    <td class="status-<?= strtolower($message['status']) ?>">
                                        <?= htmlspecialchars($message['status']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                break;

            default:
                $patientCount = $pdo->query("SELECT COUNT(*) FROM his_patients")->fetchColumn();
                $doctorCount = $pdo->query("SELECT COUNT(*) FROM his_docs")->fetchColumn();
                $pharmaCount = $pdo->query("SELECT COUNT(*) FROM his_pharmaceuticals")->fetchColumn();
                ?>
                <div class="section">
                    <h2>Welcome to Hospital Management System</h2>
                    <p>Select a section from the navigation menu to get started.</p>
                    
                    <div style="display: flex; gap: 20px; margin-top: 20px;">
                        <div class="section" style="flex: 1;">
                            <h3>Quick Stats</h3>
                            <p>Total Patients: <strong><?= $patientCount ?></strong></p>
                            <p>Total Doctors: <strong><?= $doctorCount ?></strong></p>
                            <p>Pharmaceutical Items: <strong><?= $pharmaCount ?></strong></p>
                        </div>
                    </div>
                </div>
                <?php
                break;
        }
        ?>
    </main>
    <footer>
        <p>&copy; <?= date('Y') ?> Hospital Management System</p>
    </footer>
</body>
</html>