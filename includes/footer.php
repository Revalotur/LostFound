<?php
// includes/footer.php
?>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2026 Lost & Found System. Developed by <strong>FabioGanteng</strong></p>
        </div>
    </footer>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        lucide.createIcons();

        // Theme Toggle Logic
        const themeToggle = document.getElementById('theme-toggle');
        const darkIcon = document.querySelector('.dark-icon');
        const lightIcon = document.querySelector('.light-icon');
        
        // Check saved theme
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcons(currentTheme);

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcons(newTheme);
            });
        }

        function updateThemeIcons(theme) {
            if (theme === 'dark') {
                if (darkIcon) darkIcon.style.display = 'none';
                if (lightIcon) lightIcon.style.display = 'block';
            } else {
                if (darkIcon) darkIcon.style.display = 'block';
                if (lightIcon) lightIcon.style.display = 'none';
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Notification Bell Toggle
        const notifBell = document.getElementById('notif-bell');
        const notifDropdown = document.getElementById('notif-dropdown');

        if (notifBell) {
            notifBell.addEventListener('click', (e) => {
                e.stopPropagation();
                notifDropdown.style.display = notifDropdown.style.display === 'none' ? 'block' : 'none';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }

        // Close dropdown when clicked outside
        document.addEventListener('click', () => {
            if (notifDropdown) {
                notifDropdown.style.display = 'none';
            }
        });

        // Mark notification as read
        function markNotificationAsRead(notifId, reportId) {
            fetch('<?php echo BASE_URL; ?>api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_id: notifId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect ke detail laporan
                    window.location.href = '<?php echo BASE_URL; ?>pages/detail.php?id=' + reportId;
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
