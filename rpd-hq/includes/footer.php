    </div><!-- /container -->
    <!-- Bootstrap Bundle with Popper -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    // JavaScript for Toast messages
    document.addEventListener('DOMContentLoaded', function() {
        const toastElList = [].slice.call(document.querySelectorAll('.toast'));
        const toastList = toastElList.map(function(toastEl) {
            return new bootstrap.Toast(toastEl);
        });
        toastList.forEach(toast => toast.show());

        // Clear toast messages from session after they are shown
        <?php
        if (isset($_SESSION['toast_message'])) {
            unset($_SESSION['toast_message']);
            unset($_SESSION['toast_type']);
        }
        ?>
    });
    </script>
    <script src="js/script.js"></script>
</body>
</html>