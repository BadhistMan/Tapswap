<?php
// api.php
header('Content-Type: application/json');
ini_set('display_errors', 0); // Suppress direct error output

session_start();
require_once 'db_config.php';

// --- Constants ---
define('ENERGY_REGEN_RATE', 1); // 1 energy per second
define('POINTS_PER_TASK', 1000);
define('MIN_WITHDRAW_POINTS', 100);

// --- Helper Functions ---
function json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

function get_user_data($pdo, $user_id) {
    // Regenerate energy before fetching data
    $stmt_update_energy = $pdo->prepare("SELECT energy, max_energy, last_energy_update FROM users WHERE user_id = ?");
    $stmt_update_energy->execute([$user_id]);
    $user = $stmt_update_energy->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $now = new DateTime();
        $last_update = new DateTime($user['last_energy_update']);
        $seconds_diff = $now->getTimestamp() - $last_update->getTimestamp();
        
        $energy_to_add = floor($seconds_diff * ENERGY_REGEN_RATE);
        $new_energy = min($user['max_energy'], $user['energy'] + $energy_to_add);

        if ($energy_to_add > 0) {
            $stmt_set_energy = $pdo->prepare("UPDATE users SET energy = ?, last_energy_update = NOW() WHERE user_id = ?");
            $stmt_set_energy->execute([$new_energy, $user_id]);
        }
    }
    
    // Fetch updated user data
    $stmt = $pdo->prepare("SELECT user_id, username, points, energy, max_energy, last_energy_update FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


// --- API Endpoints ---
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // --- AUTHENTICATION ---
    case 'register':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) {
            json_response(false, "Username and password are required.");
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, max_energy, energy) VALUES (?, ?, 100, 100)");
            $stmt->execute([$username, $password_hash]);
            json_response(true, "Registration successful.");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                json_response(false, "Username already exists.");
            } else {
                json_response(false, "Database error during registration.");
            }
        }
        break;

    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) {
            json_response(false, "Username and password are required.");
        }
        $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            json_response(true, "Login successful.");
        } else {
            json_response(false, "Invalid username or password.");
        }
        break;

    case 'logout':
        session_destroy();
        json_response(true, "Logged out successfully.");
        break;

    case 'check_session':
        if (isset($_SESSION['user_id'])) {
            $user_data = get_user_data($pdo, $_SESSION['user_id']);
            $constants = [
                'ENERGY_REGEN_RATE' => ENERGY_REGEN_RATE,
                'POINTS_PER_TASK' => POINTS_PER_TASK,
                'MIN_WITHDRAW_POINTS' => MIN_WITHDRAW_POINTS,
            ];
            json_response(true, "Session active.", ['user' => $user_data, 'constants' => $constants]);
        } else {
            json_response(false, "No active session.");
        }
        break;
        
    // --- GAMEPLAY ---
    case 'tap':
        if (!isset($_SESSION['user_id'])) json_response(false, "Not logged in.");
        
        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();
        try {
            $user = get_user_data($pdo, $user_id);

            if ($user['energy'] > 0) {
                $stmt = $pdo->prepare("UPDATE users SET points = points + 1, energy = energy - 1, last_energy_update = NOW() WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $pdo->commit();
                
                $updated_user = get_user_data($pdo, $user_id);
                json_response(true, "+1 point!", ['points' => $updated_user['points'], 'energy' => $updated_user['energy']]);
            } else {
                $pdo->rollBack();
                json_response(false, "Out of energy!");
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            json_response(false, "Database error during tap.");
        }
        break;

    case 'completeTask':
        if (!isset($_SESSION['user_id'])) json_response(false, "Not logged in.");
        
        $user_id = $_SESSION['user_id'];
        // In a real app, you would verify the task completion. Here, we just award points.
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE user_id = ?");
            $stmt->execute([POINTS_PER_TASK, $user_id]);
            $pdo->commit();

            $user = get_user_data($pdo, $user_id);
            json_response(true, "Task completed! +" . POINTS_PER_TASK . " points.", ['points' => $user['points']]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            json_response(false, "Database error completing task.");
        }
        break;
        
    // --- WITHDRAWAL ---
    case 'withdraw':
        if (!isset($_SESSION['user_id'])) json_response(false, "Not logged in.");

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();
        try {
            $user = get_user_data($pdo, $user_id);

            if ($user['points'] >= MIN_WITHDRAW_POINTS) {
                // Deduct points from user
                $stmt_deduct = $pdo->prepare("UPDATE users SET points = points - ? WHERE user_id = ?");
                $stmt_deduct->execute([MIN_WITHDRAW_POINTS, $user_id]);

                // Log withdrawal
                $stmt_log = $pdo->prepare("INSERT INTO withdrawals (user_id, points_withdrawn) VALUES (?, ?)");
                $stmt_log->execute([$user_id, MIN_WITHDRAW_POINTS]);

                $pdo->commit();
                $updated_user = get_user_data($pdo, $user_id);
                json_response(true, "Withdrawal successful.", ['points' => $updated_user['points']]);
            } else {
                $pdo->rollBack();
                json_response(false, "Not enough points to withdraw.");
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            json_response(false, "Database error during withdrawal.");
        }
        break;
        
    case 'getWithdrawalHistory':
        if (!isset($_SESSION['user_id'])) json_response(false, "Not logged in.");
        
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT points_withdrawn, withdrawal_timestamp FROM withdrawals WHERE user_id = ? ORDER BY withdrawal_timestamp DESC LIMIT 50");
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(true, "History fetched.", ['history' => $history]);
        break;
        
    default:
        json_response(false, "Invalid action.");
        break;
}