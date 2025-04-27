
<?php
$language = $this->session->userdata('language') ?? 'english';
$this->lang->load('dashboard_lang', $language);
?>
<!DOCTYPE html>
<html lang="<?php echo $language ?? 'en'; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->lang->line('change_password_title'); ?></title>
    <link rel="stylesheet" href="https:</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="col-md-6 offset-md-3">
            <h2 class="mb-4"><?php echo $this->lang->line('change_password_heading'); ?></h2>

            <?php echo form_open('admin/change_password'); ?>

            <div class="form-group">
                <label for="current_password"><?php echo $this->lang->line('current_password_label'); ?></label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="new_password"><?php echo $this->lang->line('new_password_label'); ?></label>
                <input type="password" name="new_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password"><?php echo $this->lang->line('confirm_password_label'); ?></label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?php echo $this->lang->line('change_password_button'); ?></button>

            <?php echo form_close(); ?>
        </div>
    </div>

    <script src="https:    <script src="https:    <script src="https:
</body>
</html>

