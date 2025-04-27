<?php
$language = $this->session->userdata('language') ?? 'english';
$this->lang->load('dashboard_lang', $language);
?>



<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->lang->line('admin_dashboard'); ?></title>
    <link rel="stylesheet" href="https:    <link rel="stylesheet" id="themeStylesheet" href="https:



</head>
<body class="bg-light">

    <!-- ... (your existing code) -->
    

    <div class="container mt-5">
        <div class="col-md-6 offset-md-3">
            <h2 class="mb-4"><?php echo $this->lang->line('admin_dashboard'); ?></h2>

            <!-- Language Change Button -->
            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#languageModal">
                <?php echo $this->lang->line('change_language'); ?>
            </button>

            <!--button for night mode-->




            <!-- Buttons for CRUD operations -->
            <a href="<?php echo site_url('admin/create_employee'); ?>" class="btn btn-primary btn-block mb-2"><?php echo $this->lang->line('create_employee'); ?></a>
            <a href="<?php echo site_url('admin/read_employees'); ?>" class="btn btn-success btn-block mb-2"><?php echo $this->lang->line('read_employees'); ?></a>
            <a href="<?php echo site_url('admin/update_employee'); ?>" class="btn btn-warning btn-block mb-2"><?php echo $this->lang->line('update_employee'); ?></a>
            <a href="<?php echo site_url('admin/delete_employee'); ?>" class="btn btn-danger btn-block mb-2"><?php echo $this->lang->line('delete_employee'); ?></a>

            <!-- Change Password Button -->
            <a href="<?php echo site_url('admin/change_password'); ?>" class="btn btn-info btn-block mb-2"><?php echo $this->lang->line('change_password'); ?></a>

            <!-- Logout Button -->
            <a href="<?php echo site_url('admin/logout'); ?>" class="btn btn-secondary btn-block"><?php echo $this->lang->line('logout'); ?></a>

           
        </div>
    </div>

    <!-- Language Modal -->
    <div class="modal fade" id="languageModal" tabindex="-1" role="dialog" aria-labelledby="languageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="languageModalLabel"><?php echo $this->lang->line('choose_language'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <a href="<?php echo site_url('admin/change_language/english'); ?>" class="btn btn-link">English</a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo site_url('admin/change_language/amharic'); ?>" class="btn btn-link">አማርኛ</a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo site_url('admin/change_language/oromo'); ?>" class="btn btn-link">Afaan Oromoo</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ... (rest of your HTML) -->

    <script src="https:    <script src="https:    <script src="https:


</body>
</html>
