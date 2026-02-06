<?php
session_start();

// Contact page for Drumvale Library Management System
$page_title = "Contact Us";
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-envelope-open-text"></i> Contact Us</h1>
        <p class="page-subtitle">Get in touch with the library administration or IT support</p>
    </div>

    <div class="contact-grid">
        <!-- Contact Information -->
        <div class="contact-info card">
            <h2>Our Details</h2>
            <div class="info-list">
                <div class="info-item">
                    <div class="icon-box"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-text">
                        <strong>Address</strong>
                        <span>Drumvale Secondary School, P.O. Box 123-90100, Machakos, Kenya</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="icon-box"><i class="fas fa-phone"></i></div>
                    <div class="info-text">
                        <strong>Phone</strong>
                        <span>+254 700 000 000 / +254 711 111 111</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="icon-box"><i class="fas fa-envelope"></i></div>
                    <div class="info-text">
                        <strong>Email</strong>
                        <span>library@drumvale.edu</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="icon-box"><i class="fas fa-clock"></i></div>
                    <div class="info-text">
                        <strong>Library Hours</strong>
                        <span>Mon - Fri: 8:00 AM - 5:00 PM<br>Sat: 9:00 AM - 1:00 PM</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Team -->
        <div class="support-team card">
            <h2>Support Team</h2>
            <div class="team-list">
                <div class="team-member">
                    <div class="member-info">
                        <strong>Head Librarian</strong>
                        <span>For collection management & policy inquiries</span>
                    </div>
                    <a href="mailto:librarian@drumvale.edu" class="contact-link">Email Librarian</a>
                </div>
                <div class="team-member">
                    <div class="member-info">
                        <strong>IT Support</strong>
                        <span>For system bugs, login issues, & technical help</span>
                    </div>
                    <a href="mailto:it@drumvale.edu" class="contact-link">Email IT Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-top: 1.5rem;
}

.info-item {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

.icon-box {
    width: 50px;
    height: 50px;
    background: #e0f2fe;
    color: #0284c7;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.info-text strong {
    display: block;
    font-size: 1.1rem;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.info-text span {
    color: var(--gray-600);
    line-height: 1.5;
}

.team-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.team-member {
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.member-info strong {
    display: block;
    margin-bottom: 0.25rem;
}

.member-info span {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.contact-link {
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: background 0.2s;
}

.contact-link:hover {
    background: var(--primary-dark);
}

@media (max-width: 600px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
    .team-member {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
