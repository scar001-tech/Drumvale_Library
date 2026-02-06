    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-info">
                <p>&copy; <?php echo date('Y'); ?> Drumvale Secondary School. All rights reserved.</p>
                <p>Library Management System v1.0</p>
            </div>
            <div class="footer-links">
                <a href="<?php echo $basePath; ?>help.php">Help</a>
                <a href="<?php echo $basePath; ?>about.php">About</a>
                <a href="<?php echo $basePath; ?>contact.php">Contact</a>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo $basePath; ?>assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Inline JavaScript -->
    <?php if (isset($inline_js)): ?>
        <script><?php echo $inline_js; ?></script>
    <?php endif; ?>

</body>
</html>