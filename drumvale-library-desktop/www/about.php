<?php
session_start();

// About page for Drumvale Library Management System
$page_title = "About System";
include 'includes/header.php';
?>

<div class="page-container">
    <div class="about-hero">
        <div class="hero-content">
            <h1><i class="fas fa-book-open"></i> Drumvale Library Management System</h1>
            <p class="tagline">Empowering Education Through Better Resource Management</p>
            <span class="version-badge">Version 1.0.0</span>
        </div>
    </div>

    <div class="about-content">
        <div class="about-section card">
            <h2>Our Mission</h2>
            <p>The Drumvale Library Management System is designed to streamline the operations of our school library, making it easier for administrators to manage resources and for students and teachers to access the information they need.</p>
            <p>By digitizing our library records, we ensure better tracking of books, eliminate manual errors, and provide valuable insights into our library's usage patterns.</p>
        </div>

        <div class="features-grid">
            <div class="feature-item card">
                <i class="fas fa-bolt"></i>
                <h3>Fast & Efficient</h3>
                <p>Built for speed with a modern, responsive interface that works on desktops and tablets.</p>
            </div>
            <div class="feature-item card">
                <i class="fas fa-file-import"></i>
                <h3>Smart Bulk Import</h3>
                <p>Zero-click automated processing for large volumes of book records from any file format.</p>
            </div>
            <div class="feature-item card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Locking</h3>
                <p>Robust admin controls and audit logs to track all sensitive library activities.</p>
            </div>
            <div class="feature-item card">
                <i class="fas fa-database"></i>
                <h3>Hybrid Database</h3>
                <p>Optimized to run on both high-performance MySQL servers and standalone SQLite environments.</p>
            </div>
        </div>

        <div class="about-footer-info card">
            <h2>School Information</h2>
            <div class="school-details">
                <div class="detail-group">
                    <strong>Institution:</strong>
                    <span>Drumvale Secondary School</span>
                </div>
                <div class="detail-group">
                    <strong>Department:</strong>
                    <span>Information Resource Centre / Library</span>
                </div>
                <div class="detail-group">
                    <strong>Location:</strong>
                    <span>Machakos County, Kenya</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.about-hero {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
    padding: 4rem 2rem;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 2rem;
}

.about-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.hero-content .tagline {
    font-size: 1.25rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
}

.version-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.about-section {
    padding: 2.5rem;
    margin-bottom: 2rem;
}

.about-section h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.about-section p {
    font-size: 1.1rem;
    line-height: 1.7;
    margin-bottom: 1.5rem;
    color: var(--gray-700);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.feature-item {
    padding: 2rem;
    text-align: center;
}

.feature-item i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.feature-item h3 {
    margin-bottom: 1rem;
}

.about-footer-info {
    padding: 2.5rem;
}

.school-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-top: 1.5rem;
}

.detail-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-group strong {
    color: var(--gray-500);
    font-size: 0.875rem;
    text-transform: uppercase;
}

.detail-group span {
    font-size: 1.1rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .about-hero h1 {
        font-size: 1.75rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
