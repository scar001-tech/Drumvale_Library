<?php
session_start();

// Help page for Drumvale Library Management System
$page_title = "Help & Support";
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-question-circle"></i> Help & Support</h1>
        <p class="page-subtitle">Frequently asked questions and system usage guide</p>
    </div>

    <div class="help-grid">
        <!-- Quick Start -->
        <div class="help-section card">
            <h3><i class="fas fa-rocket"></i> Quick Start Guide</h3>
            <div class="help-content">
                <p>Welcome to the Drumvale Library Management System. Here's how to get started:</p>
                <ul>
                    <li><strong>Registering Books:</strong> Use the "Add New Book" or "Bulk Upload" feature in the Books menu.</li>
                    <li><strong>Managing Members:</strong> Register students and teachers in the Members section to enable borrowing.</li>
                    <li><strong>Issuing Books:</strong> Go to Transactions → Issue Book to check out a book to a member.</li>
                    <li><strong>Returning Books:</strong> Use Transactions → Return Book or search for the active transaction.</li>
                </ul>
            </div>
        </div>

        <!-- Common Tasks -->
        <div class="help-section card">
            <h3><i class="fas fa-tasks"></i> Common Tasks</h3>
            <div class="accordion">
                <div class="accordion-item">
                    <div class="accordion-header">How do I perform a bulk upload?</div>
                    <div class="accordion-content">
                        <p>Go to <strong>Books > Bulk Upload</strong>. Select your CSV or Excel file. The system will automatically map the columns. If any required fields like "Title" are missing, it will assign default values to ensure the import succeeds.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">How are fines calculated?</div>
                    <div class="accordion-content">
                        <p>Fines are automatically calculated based on the overdue days. The default rate is defined in <strong>Settings</strong>. When a book is returned after its due date, a fine record is automatically created.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">Can I export reports?</div>
                    <div class="accordion-content">
                        <p>Yes, all report tables have a "Print" or "Export" button (coming soon in full version) that allows you to save data for offline use.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="help-section card">
            <h3><i class="fas fa-tools"></i> Troubleshooting</h3>
            <div class="help-content">
                <p><strong>Login Issues:</strong> Ensure you are using the correct administrator credentials. If you've forgotten your password, contact the system administrator.</p>
                <p><strong>Database Errors:</strong> If you see SQL errors, ensure the database server (MySQL) is running in your XAMPP control panel.</p>
                <p><strong>File Upload Errors:</strong> Check that your file is in a supported format (.csv, .xlsx) and does not exceed the maximum upload size.</p>
            </div>
        </div>

        <!-- System Requirements -->
        <div class="help-section card">
            <h3><i class="fas fa-info-circle"></i> System Information</h3>
            <div class="help-content">
                <p><strong>Version:</strong> v1.0.0</p>
                <p><strong>Environment:</strong> PHP 7.4+ / MySQL 5.7+ / SQLite 3</p>
                <p><strong>Browser:</strong> Optimized for modern browsers (Chrome, Edge, Firefox, Safari)</p>
            </div>
        </div>
    </div>
</div>

<style>
.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.help-section {
    padding: 2rem;
    height: 100%;
}

.help-section h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 1rem;
}

.help-content p {
    margin-bottom: 1rem;
    line-height: 1.6;
    color: var(--gray-700);
}

.help-content ul {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

.help-content li {
    margin-bottom: 0.75rem;
    color: var(--gray-700);
}

.accordion-item {
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.accordion-header {
    background: var(--gray-50);
    padding: 1rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.accordion-header:hover {
    background: var(--gray-100);
}

.accordion-content {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .help-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
