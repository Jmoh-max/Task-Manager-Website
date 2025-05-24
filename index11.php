<?php
session_start();

// DB config
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'must_transcript';

// Connect to DB
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Constants
$adminRegNo = '123456789';
$adminPass = '123';
$studentPass = '123';

$errorMsg = "";

// Helpers
function getTranscript($regNo) {
    global $conn;
    $stmt = $conn->prepare("SELECT transcript FROM transcripts WHERE reg_no = ?");
    $stmt->bind_param("s", $regNo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['transcript'];
    }
    return null;
}

function insertTranscript($regNo, $transcript) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO transcripts (reg_no, transcript) VALUES (?, ?) ON DUPLICATE KEY UPDATE transcript=?");
    $stmt->bind_param("sss", $regNo, $transcript, $transcript);
    return $stmt->execute();
}

// Process login
if (isset($_POST['login'])) {
    $regNo = trim($_POST['regNo']);
    $password = trim($_POST['password']);

    if ($regNo === $adminRegNo && $password === $adminPass) {
        $_SESSION['user'] = 'admin';
        $_SESSION['regNo'] = $regNo;
        header("Location: ?page=insert");
        exit;
    } elseif (preg_match('/^231005\d{8}$/', $regNo) && $password === $studentPass) {
        $transcript = getTranscript($regNo);
        if ($transcript !== null) {
            $_SESSION['user'] = 'student';
            $_SESSION['regNo'] = $regNo;
            header("Location: ?page=view");
            exit;
        } else {
            $errorMsg = "Transcript not found for this registration number.";
        }
    } else {
        $errorMsg = "Invalid registration number or password.";
    }
}

// Admin inserts transcript
if (isset($_POST['insert_transcript']) && isset($_SESSION['user']) && $_SESSION['user'] === 'admin') {
    $studentReg = trim($_POST['studentReg']);
    $transcriptText = trim($_POST['transcriptText']);

    if (preg_match('/^231005\d{8}$/', $studentReg) && !empty($transcriptText)) {
        if (insertTranscript($studentReg, $transcriptText)) {
            echo "<div>Transcript uploaded successfully.</div>";
            //  echo "<script>alert('Transcript uploaded successfully.'); 
            //  window.location.href = 'index.php';</script>";
            session_destroy();
            exit;
        } else {
            $insert_error = "Failed to save transcript.";
        }
    } else {
        $insert_error = "Invalid registration number or empty transcript.";
    }
}

// Student downloads transcript
if (isset($_POST['download_transcript']) && isset($_SESSION['user']) && $_SESSION['user'] === 'student') {
    $regNo = $_SESSION['regNo'];
    $transcript = getTranscript($regNo);
    if ($transcript) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Transcript_'.$regNo.'.html"');
        header('Content-Length: ' . strlen($transcript));
        echo $transcript;
        session_destroy();
        exit;
    } else {
        echo "<p>Transcript not found.</p>";
        exit;
    }
}

// Views
function showLoginForm($error) {
    echo "<html><head><title>Login</title><style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(to right, #667eea, #764ba2); }
        form { background: white; padding: 2em; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 300px; }
        input { display: block; margin: 1em 0; padding: 0.6em; width: 100%; border: 1px solid #ccc; border-radius: 5px; }
        input[type=submit] { background: #667eea; color: white; border: none; cursor: pointer; transition: background 0.3s; }
        input[type=submit]:hover { background: #5a67d8; }
        h2 { text-align: center; color: #333; }
    </style></head><body>
        <form method='post'>
            <h2>Login</h2>
            <input type='text' name='regNo' placeholder='Registration Number' required />
            <input type='password' name='password' placeholder='Password' required />
            <input type='submit' name='login' value='Login' />
            <p style='color:red;'>$error</p>
        </form>
    </body></html>";
}

function showViewPage($regNo) {
    echo "<html><head><title>Transcript</title><style>
        body { font-family: 'Segoe UI', sans-serif; background: #edf2f7; display: flex; justify-content: center; align-items: center; height: 100vh; }
        form { background: white; padding: 2em; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); text-align: center; }
        input[type=submit] { padding: 0.6em 1.5em; background: #48bb78; color: white; border: none; border-radius: 6px; cursor: pointer; }
    </style></head><body>
        <form method='post'>
            <h2>Welcome $regNo</h2>
            <input type='submit' name='download_transcript' value='Download Transcript' />
        </form>
    </body></html>";
}

function showInsertPage($success = '', $error = '') {
    echo "<html><head><title>Insert Transcript</title><style>
        body { font-family: 'Segoe UI', sans-serif; background: #f7fafc; display: flex; justify-content: center; align-items: center; height: 100vh; }
        form { background: white; padding: 2em; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); width: 400px; }
        textarea { width: 100%; height: 150px; border-radius: 6px; border: 1px solid #ccc; }
        input, textarea { margin: 1em 0; padding: 0.6em; width: 100%; }
        input[type=submit] { background: #3182ce; color: white; border: none; border-radius: 6px; cursor: pointer; }
        h2 { text-align: center; color: #2d3748; }
    </style></head><body>
        <form method='post'>
            <h2>Upload Transcript</h2>
            <input type='text' name='studentReg' placeholder='Student Reg. Number' required />
            <textarea name='transcriptText' placeholder='Paste MUST transcript HTML content here...' required></textarea>
            <input type='submit' name='insert_transcript' value='Upload Transcript' />
            <p style='color:green;'>$success</p>
            <p style='color:red;'>$error</p>
        </form>
    </body></html>";
}

// Routing
$page = $_GET['page'] ?? '';

if (!isset($_SESSION['user'])) {
    showLoginForm($errorMsg);
} else {
    if ($_SESSION['user'] === 'admin') {
        if ($page === 'insert') {
            showInsertPage($insert_success ?? '', $insert_error ?? '');
        } else {
            header("Location: ?page=insert");
            exit;
        }
    } elseif ($_SESSION['user'] === 'student') {
        if ($page === 'view') {
            showViewPage($_SESSION['regNo']);
        } else {
            header("Location: ?page=view");
            exit;
        }
    } else {
        session_destroy();
        // header("Location: index.php");
        exit;
    }
}
?>


