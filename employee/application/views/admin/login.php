<!-- application/views/admin/login.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https:</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="col-md-6 offset-md-3">
            <h2 class="mb-4">Admin Login</h2>

            <?php echo form_open('admin/authenticate'); ?>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <script src="https:    <script src="https:    <script src="https:
</body>
</html>

