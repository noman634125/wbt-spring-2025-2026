<?php
// ================================================================
// CONTROLLERS - request handling + role-based logic
// ================================================================

/* ============== Login ============== */
function loginCtrl($conn) {
    $error = '';
    $prefill = $_COOKIE['remember_user'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if ($u === '' || $p === '') {
            $error = 'Please fill in both fields.';
        } else {
            $admin = authAdmin($conn, $u, $p);
            if ($admin) {
                $_SESSION['user'] = [
                    'id' => $admin['id'], 'username' => $admin['username'],
                    'name' => 'Administrator', 'role' => 'admin'
                ];
                if ($remember) setcookie('remember_user', $u, time() + 86400 * 30, '/');
                else setcookie('remember_user', '', time() - 3600, '/');
                header('Location: index.php?page=admin');
                exit;
            }
            $inst = authInstructor($conn, $u, $p);
            if ($inst) {
                $_SESSION['user'] = [
                    'id' => $inst['id'], 'username' => $inst['username'],
                    'name' => $inst['name'], 'role' => 'instructor'
                ];
                if ($remember) setcookie('remember_user', $u, time() + 86400 * 30, '/');
                else setcookie('remember_user', '', time() - 3600, '/');
                header('Location: index.php?page=instructor');
                exit;
            }
            $error = 'Invalid username or password.';
        }
    }

    require 'views/login.php';
}

/* ============== Register (instructor self-registration) ============== */
function registerCtrl($conn) {
    $error = $success = '';
    $old = ['name' => '', 'contact' => '', 'username' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name     = trim($_POST['name'] ?? '');
        $contact  = trim($_POST['contact'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $old = compact('name', 'contact', 'username');

        if ($name === '' || $contact === '' || $username === '' || $password === '') {
            $error = 'All fields are required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (instructorUsernameExists($conn, $username)) {
            $error = 'Username is already taken.';
        } else {
            if (addInstructor($conn, $name, $contact, $username, $password)) {
                $success = 'Account created! You can now log in.';
                $old = ['name' => '', 'contact' => '', 'username' => ''];
            } else {
                $error = 'Registration failed. Try again.';
            }
        }
    }

    require 'views/register.php';
}

/* ============== Admin Dashboard (manages instructors) ============== */
function adminCtrl($conn) {
    $action = $_GET['action'] ?? 'list';
    $error = '';
    $editing = null;

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name     = trim($_POST['name'] ?? '');
        $contact  = trim($_POST['contact'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $contact === '' || $username === '' || $password === '') {
            $error = 'All fields are required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (instructorUsernameExists($conn, $username)) {
            $error = 'Username is already taken.';
        } else {
            if (addInstructor($conn, $name, $contact, $username, $password)) {
                header('Location: index.php?page=admin&msg=added');
                exit;
            }
            $error = 'Failed to add instructor.';
        }
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id       = intval($_GET['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $contact  = trim($_POST['contact'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($name === '' || $contact === '' || $username === '') {
            $error = 'No field can be empty. All fields are required.';
            $editing = ['id' => $id, 'name' => $name, 'contact' => $contact, 'username' => $username];
        } elseif (instructorUsernameExists($conn, $username, $id)) {
            $error = 'That username is used by another instructor.';
            $editing = ['id' => $id, 'name' => $name, 'contact' => $contact, 'username' => $username];
        } else {
            if (updateInstructor($conn, $id, $name, $contact, $username)) {
                header('Location: index.php?page=admin&msg=updated');
                exit;
            }
            $error = 'Update failed.';
            $editing = ['id' => $id, 'name' => $name, 'contact' => $contact, 'username' => $username];
        }
    }

    if ($action === 'edit' && !$editing) {
        $id = intval($_GET['id'] ?? 0);
        $editing = getInstructor($conn, $id);
    }

    if ($action === 'delete') {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteInstructor($conn, $id);
        header('Location: index.php?page=admin&msg=deleted');
        exit;
    }

    $instructors = getInstructors($conn);
    require 'views/admin.php';
}

/* ============== Instructor Dashboard (manages courses) ============== */
function instructorCtrl($conn) {
    $action = $_GET['action'] ?? 'list';
    $error = '';
    $editing = null;

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title    = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $seats    = trim($_POST['seats'] ?? '');
        $price    = trim($_POST['price'] ?? '');

        if ($title === '' || $category === '' || $seats === '' || $price === '') {
            $error = 'All fields are required.';
        } elseif (!ctype_digit($seats) || intval($seats) < 0) {
            $error = 'Seats must be a non-negative whole number.';
        } elseif (!is_numeric($price) || floatval($price) < 0) {
            $error = 'Price must be a non-negative number.';
        } else {
            $instId = $_SESSION['user']['id'];
            if (addCourse($conn, $title, $category, intval($seats), floatval($price), $instId)) {
                header('Location: index.php?page=instructor&msg=added');
                exit;
            }
            $error = 'Failed to add course.';
        }
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id       = intval($_GET['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $seats    = trim($_POST['seats'] ?? '');
        $price    = trim($_POST['price'] ?? '');

        if ($title === '' || $category === '' || $seats === '' || $price === '') {
            $error = 'No field can be empty. All fields are required.';
            $editing = ['id' => $id, 'title' => $title, 'category' => $category,
                        'seats' => $seats, 'price' => $price];
        } elseif (!ctype_digit($seats) || intval($seats) < 0) {
            $error = 'Seats must be a non-negative whole number.';
            $editing = ['id' => $id, 'title' => $title, 'category' => $category,
                        'seats' => $seats, 'price' => $price];
        } elseif (!is_numeric($price) || floatval($price) < 0) {
            $error = 'Price must be a non-negative number.';
            $editing = ['id' => $id, 'title' => $title, 'category' => $category,
                        'seats' => $seats, 'price' => $price];
        } else {
            if (updateCourse($conn, $id, $title, $category, intval($seats), floatval($price))) {
                header('Location: index.php?page=instructor&msg=updated');
                exit;
            }
            $error = 'Update failed.';
            $editing = ['id' => $id, 'title' => $title, 'category' => $category,
                        'seats' => $seats, 'price' => $price];
        }
    }

    if ($action === 'edit' && !$editing) {
        $id = intval($_GET['id'] ?? 0);
        $editing = getCourse($conn, $id);
    }

    if ($action === 'delete') {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteCourse($conn, $id);
        header('Location: index.php?page=instructor&msg=deleted');
        exit;
    }

    $courses = getCourses($conn);
    require 'views/instructor.php';
}
?>
