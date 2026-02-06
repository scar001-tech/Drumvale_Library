<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// If form was submitted, redirect to process page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    // Let bulk_upload_process.php handle it
    include 'bulk_upload_process.php';
    exit();
}

$page_title = "Bulk Upload Books";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-upload"></i> Bulk Upload Books</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active">
            <div class="step-number">1</div>
            <div class="step-label">Upload File</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-number">2</div>
            <div class="step-label">Map Columns</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-number">3</div>
            <div class="step-label">Results</div>
        </div>
    </div>

    <div class="form-container">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Upload any spreadsheet file!</strong><br>
                Your file can have any column names. In the next step, you'll map your columns to the book fields.
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" action="bulk_upload_process.php">
            <input type="hidden" name="step" value="1">
            
            <h3><i class="fas fa-file-alt"></i> Select Your File</h3>
            
            <div class="form-group">
                <label for="import_file">Choose File <span class="required">*</span></label>
                <div class="file-upload-wrapper">
                    <input type="file" name="import_file" id="import_file" class="form-input" 
                           accept=".csv,.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
                </div>
                <small class="form-help">Accepted formats: CSV (.csv), Excel (.xls, .xlsx) — Any column format accepted</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="has_header" value="1" checked>
                    First row contains column headers
                </label>
                <small class="form-help">Uncheck if your data starts from the first row</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-magic"></i> Upload & Import Books
                </button>
            </div>
        </form>

        <div class="info-cards">
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div class="info-card-content">
                    <h4>Any Format Accepted</h4>
                    <p>Your spreadsheet can have any column names. We'll help you map them in the next step.</p>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-magic"></i>
                </div>
                <div class="info-card-content">
                    <h4>Smart Detection</h4>
                    <p>We automatically detect common column names like "Title", "Author", "ISBN", etc.</p>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="info-card-content">
                    <h4>Only Title Required</h4>
                    <p>Only the book title is required. All other fields are optional and will use defaults.</p>
                </div>
            </div>
        </div>

        <div class="download-template">
            <h3><i class="fas fa-download"></i> Need a Template?</h3>
            <p>Download a sample template (optional — you can use your own format):</p>
            <a href="download_template.php" class="btn btn-success">
                <i class="fas fa-file-download"></i> Download CSV Template
            </a>
        </div>
    </div>
</div>

<style>
/* Progress Steps */
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: #2563eb;
    color: white;
}

.step.completed .step-number {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.step.active .step-label {
    color: #2563eb;
}

.step-line {
    width: 80px;
    height: 3px;
    background: #e5e7eb;
    margin: 0 1rem;
    margin-bottom: 1.5rem;
}

/* Form Container */
.form-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-container h3 {
    margin: 0 0 1.5rem 0;
    color: #374151;
    font-size: 1.125rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-input:hover {
    border-color: #2563eb;
    background: #eff6ff;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    border-style: solid;
}

.form-help {
    display: block;
    margin-top: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
    accent-color: #2563eb;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.required { color: #ef4444; }

/* Info Cards */
.info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.info-card {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: #f9fafb;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.info-card:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
}

.info-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.info-card-content h4 {
    margin: 0 0 0.25rem 0;
    color: #374151;
    font-size: 1rem;
}

.info-card-content p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.4;
}

/* Download Template */
.download-template {
    margin-top: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-radius: 10px;
    border: 1px solid #86efac;
}

.download-template h3 {
    margin: 0 0 0.5rem 0;
    color: #166534;
}

.download-template p {
    margin: 0 0 1rem 0;
    color: #15803d;
}

/* Alert */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.alert i {
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.alert-info {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #1e40af;
    border: 1px solid #93c5fd;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(135deg, #059669, #047857);
}

@media (max-width: 768px) {
    .progress-steps {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .step-line {
        display: none;
    }
    
    .info-cards {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-lg {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
