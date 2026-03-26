    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Bất Động Sản. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
    <?php
    // Xác định base path
    $base_path = '';
    if (strpos($_SERVER['PHP_SELF'], '/tenant/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/landlord/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
        $base_path = '../';
    }
    ?>
    <script src="<?php echo $base_path; ?>assets/js/main.js"></script>
</body>
</html>

