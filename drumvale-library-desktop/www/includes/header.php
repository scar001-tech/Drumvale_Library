<?php
// Determine the base path for assets and links
function getBasePath() {
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $depth = substr_count($currentDir, '/') - 1; // -1 because root is already at depth 1
    return str_repeat('../', max(0, $depth));
}

$basePath = getBasePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Drumvale Library Management</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $basePath; ?>assets/images/favicon.ico">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/main.css">
    
    <!-- Page-specific CSS -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $basePath . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
    <!-- Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <a href="<?php echo $basePath; ?>index.php" class="nav-brand">
                <i class="fas fa-book-open"></i>
                <span>Drumvale Library</span>
            </a>
            
            <div class="nav-menu">
                <a href="<?php echo $basePath; ?>index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="nav-dropdown">
                    <a href="#" class="nav-item dropdown-toggle">
                        <i class="fas fa-book"></i>
                        <span>Books</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?php echo $basePath; ?>books/index.php">View All Books</a>
                        <a href="<?php echo $basePath; ?>books/add.php">Add New Book</a>
                        <a href="<?php echo $basePath; ?>books/bulk_upload.php">Bulk Upload</a>
                    </div>
                </div>
                
                <div class="nav-dropdown">
                    <a href="#" class="nav-item dropdown-toggle">
                        <i class="fas fa-users"></i>
                        <span>Members</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?php echo $basePath; ?>members/index.php">View All Members</a>
                        <a href="<?php echo $basePath; ?>members/add.php">Register Member</a>
                        <a href="<?php echo $basePath; ?>members/students.php">Students Only</a>
                        <a href="<?php echo $basePath; ?>members/teachers.php">Teachers Only</a>
                    </div>
                </div>
                
                <div class="nav-dropdown">
                    <a href="#" class="nav-item dropdown-toggle">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?php echo $basePath; ?>transactions/issue.php">Issue Book</a>
                        <a href="<?php echo $basePath; ?>transactions/return.php">Return Book</a>
                        <a href="<?php echo $basePath; ?>transactions/index.php">View All Transactions</a>
                        <a href="<?php echo $basePath; ?>transactions/overdue.php">Overdue Books</a>
                    </div>
                </div>
                
                <a href="<?php echo $basePath; ?>fines/index.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Fines</span>
                </a>
                
                <div class="nav-dropdown">
                    <a href="#" class="nav-item dropdown-toggle">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?php echo $basePath; ?>reports/books.php">Book Reports</a>
                        <a href="<?php echo $basePath; ?>reports/members.php">Member Reports</a>
                        <a href="<?php echo $basePath; ?>reports/transactions.php">Transaction Reports</a>
                        <a href="<?php echo $basePath; ?>reports/fines.php">Fine Reports</a>
                        <a href="<?php echo $basePath; ?>reports/inventory.php">Inventory Report</a>
                    </div>
                </div>
                
                <a href="<?php echo $basePath; ?>settings/index.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
            
            <div class="nav-user">
                <div class="user-dropdown">
                    <a href="#" class="user-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="user-menu">
                        <a href="<?php echo $basePath; ?>profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="<?php echo $basePath; ?>change_password.php"><i class="fas fa-key"></i> Change Password</a>
                        <div class="menu-divider"></div>
                        <a href="<?php echo $basePath; ?>logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content <?php echo isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] ? 'with-nav' : 'no-nav'; ?>">
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning_message'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $_SESSION['warning_message']; unset($_SESSION['warning_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info_message'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
            </div>
        <?php endif; ?>