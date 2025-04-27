<!-- application/views/admin/update_employee.php -->
<?php
$language = $this->session->userdata('language') ?? 'english';
$this->lang->load('dashboard_lang', $language);
?>

<!DOCTYPE html>
<html lang="<?php echo $language ?? 'en'; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->lang->line('update_employee_title'); ?></title>
    <link rel="stylesheet" href="https:</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="col-md-6 offset-md-3">
            <h2 class="mb-4"><?php echo $this->lang->line('update_employee_heading'); ?></h2>

            <?php echo form_open('admin/update_employee'); ?>

            <div class="form-group">
                <label for="id"><?php echo $this->lang->line('employee_id_label'); ?></label>
                <input type="text" name="id" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="name"><?php echo $this->lang->line('name_label'); ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email"><?php echo $this->lang->line('email_label'); ?></label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="phone"><?php echo $this->lang->line('phone_label'); ?></label>
                <input type="text" name="phone" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="job_title"><?php echo $this->lang->line('job_title_label'); ?></label>
                <input type="text" name="job_title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="salary"><?php echo $this->lang->line('salary_label'); ?></label>
                <input type="text" name="salary" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="hire_date"><?php echo $this->lang->line('hire_date_label'); ?></label>
                <input type="date" name="hire_date" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-warning btn-block"><?php echo $this->lang->line('update_employee_button'); ?></button>

            <?php echo form_close(); ?>
        </div>
    </div>

    <script src="https:    <script src="https:    <script src="https:
</body>
</html>
