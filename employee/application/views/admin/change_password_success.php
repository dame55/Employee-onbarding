<?php
$language = $this->session->userdata('language') ?? 'english';
$this->lang->load('dashboard_lang', $language);
?>
<!DOCTYPE html>
<html lang="<?php echo $language ?? 'en'; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->lang->line('change_password_success_heading'); ?></title>
    <link rel="stylesheet" href="https:</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="col-md-6 offset-md-3">
            <h2 class="mb-4"><?php echo $this->lang->line('change_password_success_heading'); ?></h2>
            <p><?php echo $this->lang->line('change_password_success_message'); ?></p>
        </div>
    </div>

    <script src="https:    <script src="https:    <script src="https:
</body>
</html>
