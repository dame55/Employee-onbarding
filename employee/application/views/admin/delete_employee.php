<!-- application/views/admin/delete_employee.php -->
<?php
$language = $this->session->userdata('language') ?? 'english';
$this->lang->load('dashboard_lang', $language);
?>
<!DOCTYPE html>
<html lang="<?php echo $language ?? 'en'; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->lang->line('delete_employee_title'); ?></title>
    <link rel="stylesheet" href="https:</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="col-md-6 offset-md-3">
            <h2 class="mb-4"><?php echo $this->lang->line('delete_employee_heading'); ?></h2>

            <?php echo form_open('admin/delete_employee'); ?>

            <div class="form-group">
                <label for="id"><?php echo $this->lang->line('employee_id_label'); ?></label>
                <input type="text" name="id" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('<?php echo $this->lang->line('delete_confirmation_message'); ?>')">
                <?php echo $this->lang->line('delete_employee_button'); ?>
            </button>

            <?php echo form_close(); ?>
        </div>
    </div>

    <script src="https:    <script src="https:    <script src="https:
</body>
</html>
