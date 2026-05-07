<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register &mdash; Course Management</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
    <div class="auth-side">
        <div class="logo-big">&#127979;</div>
        <h1>Join as an Instructor</h1>
        <p>Create your account to start managing courses on the platform.</p>
        <ul class="feature-list">
            <li>Add and edit courses</li>
            <li>Track seats and pricing</li>
            <li>Search instantly with AJAX</li>
        </ul>
    </div>

    <div class="auth-form-wrap">
        <div class="auth-card">
            <h2>Create Account</h2>
            <p class="muted">Register as an instructor</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=register" class="form" novalidate>
                <div class="field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name"
                           value="<?= htmlspecialchars($old['name']) ?>"
                           placeholder="e.g. John Doe" required>
                </div>
                <div class="field">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact"
                           value="<?= htmlspecialchars($old['contact']) ?>"
                           placeholder="e.g. +880 1XXXXXXXXX" required>
                </div>
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?= htmlspecialchars($old['username']) ?>"
                           placeholder="Choose a username" required>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="Min 6 chars" required>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Repeat" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <p class="auth-foot">Already registered?
                <a href="index.php?page=login">Sign in</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
