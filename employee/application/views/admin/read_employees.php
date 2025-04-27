<!-- application/views/admin/read_employees.php -->
<?php
$language = $this->session->userdata('language') ?? 'english';
$this->lang->load('dashboard_lang', $language);
?>


<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->lang->line('read_employees_title'); ?></title>
    
    <link rel="stylesheet" href="https:    <script src="https:    <script src="https:    <script src="https:    <script src="https:    
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
    <form id="importForm" action="<?php echo base_url('admin/save_imported_data'); ?>" method="post" enctype="multipart/form-data" onsubmit="console.log('Form submitted');">
        <div class="text-right mb-3 no-print">
            <button class="btn btn-danger" onclick="printTable()"><?php echo $this->lang->line('print_button'); ?></button>
            <button class="btn btn-info" onclick="exportToPDF()"><?php echo $this->lang->line('export_pdf_button'); ?></button>
            <button class="btn btn-secondary" onclick="exportToExcel()"><?php echo $this->lang->line('export_excel_button'); ?></button>
            <button class="btn btn-success" onclick="exportToWord()"><?php echo $this->lang->line('export_word_button'); ?></button>

            <input type="file" name="importFile" id="importFile" class="btn btn-danger" required>
            <button type="submit" class="btn btn-primary" ><?php echo $this->lang->line('submit_button'); ?></button>
        </div>
    </form>
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center"><?php echo $this->lang->line('employee_list_title'); ?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($employees)): ?>
                        <table id="employeeTable" class="table table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th><?php echo $this->lang->line('name_label'); ?></th>
                                    <th><?php echo $this->lang->line('email_label'); ?></th>
                                    <th><?php echo $this->lang->line('phone_label'); ?></th>
                                    <th><?php echo $this->lang->line('job_title_label'); ?></th>
                                    <th><?php echo $this->lang->line('salary_label'); ?></th>
                                    <th><?php echo $this->lang->line('hire_date_label'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo $employee['id']; ?></td>
                                        <td><?php echo $employee['name']; ?></td>
                                        <td><?php echo $employee['email']; ?></td>
                                        <td><?php echo $employee['phone']; ?></td>
                                        <td><?php echo $employee['job_title']; ?></td>
                                        <td><?php echo $employee['salary']; ?></td>
                                        <td><?php echo $employee['hire_date']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center"><?php echo $this->lang->line('no_employees_found'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https:    <script src="https:    <script src="https:
    <script>
        function exportToPDF() {
            var element = document.getElementById('employeeTable');
            html2pdf(element);
        }

        function exportToExcel() {
            var element = document.getElementById('employeeTable');
            var ws = XLSX.utils.table_to_sheet(element);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
            XLSX.writeFile(wb, 'employee_list.xlsx');
        }

        function exportToWord() {
            var htmlContent = document.getElementById('employeeTable').outerHTML;
            var blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });

                        var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'employee_list.docx';
            link.click();
        }

        function printTable() {
            window.print();
        }



        function importEmployees() {
    console.log('Import Employees function triggered.'); 
    var fileInput = document.getElementById('importFile');
    var file = fileInput.files[0];

    if (file) {
        var reader = new FileReader();

        reader.onload = function (e) {
                        var importedData = e.target.result;

                        processImportedData(importedData);
        };

        reader.readAsText(file);
    }
}

     

        function processImportedData(importedData) {
                                    console.log(importedData);

                        $.ajax({
                type: 'POST',
                url: '/admin/save_imported_data',                  data: { importedData: importedData },
                success: function(response) {
                    console.log('Data successfully sent to the server:', response);
                },
                error: function(error) {
                    console.error('Error sending data to the server:', error);
                }
            });
        }

        
    </script>
</body>
</html>

