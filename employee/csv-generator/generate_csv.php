<?php

$data = array(
    array('Name', 'Email', 'Phone', 'Job Title', 'Salary', 'Hire Date'),
    array('John Doe', 'john@example.com', '123-456-7890', 'Developer', '50000', '2022-01-21'),
    array('Jane Doe', 'jane@example.com', '987-654-3210', 'Designer', '60000', '2022-01-21'),
);

$file = fopen('output.csv', 'w');

foreach ($data as $row) {
    fputcsv($file, $row);
}

fclose($file);

echo 'CSV file created successfully: output.csv';
