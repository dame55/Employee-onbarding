<!-- employee_registration_form.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employee Registration Form</title>
    <link href="https:   

</head>
<body>

<div class="container">
    <h2>Employee Registration Form</h2>

    <?php echo form_open('employee/register', 'class="form-horizontal"'); ?>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="name">Name:</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="name" required>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="email">Email:</label>
        <div class="col-sm-10">
            <input type="email" class="form-control" name="email" required>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="phone">Phone:</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="phone">
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="job_title">Job Title:</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="job_title" required>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="salary">Salary:</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="salary" required>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="hire_date">Hire Date:</label>
        <div class="col-sm-10">
            <input type="date" class="form-control" name="hire_date" required>
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>
    </div>

    <?php echo form_close(); ?>
</div>

<script src="https:<script src="https:<!-- Add these lines at the bottom of your employee_registration_form.php view, before the closing </body> tag -->
<script src="<?php echo base_url('assets/js/script.js'); ?>"></script>

</body>
</html>
