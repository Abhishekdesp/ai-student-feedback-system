<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Data Import - Student Feedback System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding-bottom: 50px;
        }
        .main-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 50px auto;
        }
        .dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            background-color: #f8fafc;
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dropzone:hover, .dropzone.dragover {
            background-color: #eff6ff;
            border-color: #2563eb;
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 15px; left: 15px;">
  <a class="btn btn-outline-secondary btn-sm" href="Homepage.php" title="Back">&larr; Back</a>
</div>

<div class="container">
    <div class="main-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark mb-2">Student Data Import</h4>
            <p class="text-muted small mb-0">Batch import student records into the system using Excel or CSV file</p>
        </div>

        <div class="d-flex justify-content-center mb-4">
            <a href="download_template.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-download me-1"></i>Download CSV Template
            </a>
        </div>

        <form action="import.php" method="post" enctype="multipart/form-data" id="importForm">
            <input type="file" name="file" id="file" class="d-none" accept=".xlsx, .xls, .csv" required>
            
            <div class="dropzone mb-4" id="dropzone" onclick="document.getElementById('file').click()">
                <div class="fs-2 text-primary mb-2">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </div>
                <h6 class="fw-semibold text-dark mb-1" id="dropzoneText">Click or Drag & Drop file here</h6>
                <p class="text-muted small mb-0">Supports .xlsx, .xls, .csv files</p>
                <div id="fileInfo" class="mt-2 text-primary fw-bold small d-none"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-medium">
                Import Student Data
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file');
    const dropzoneText = document.getElementById('dropzoneText');
    const fileInfo = document.getElementById('fileInfo');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileInfo(files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            updateFileInfo(fileInput.files[0]);
        }
    });

    function updateFileInfo(file) {
        dropzoneText.innerText = "Selected File:";
        fileInfo.innerText = "📄 " + file.name + " (" + (file.size / 1024).toFixed(1) + " KB)";
        fileInfo.classList.remove('d-none');
    }
</script>
</body>
</html>
