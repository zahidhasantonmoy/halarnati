    </div>
    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date("Y") ?> Halarnati | All rights reserved.</span>
            <p class="mb-0 mt-2">Total Views: <span id="total-views">Loading...</span></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Prism.js for code highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script>
        // Function to copy text to clipboard
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                const textToCopy = element.innerText || element.value;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    alert('Content copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            }
        }

        // Fetch total views (will be implemented in index.php)
        // This is a placeholder for now
        document.addEventListener('DOMContentLoaded', () => {
            // In a real scenario, you'd fetch this via AJAX or embed it directly
            // For now, it will be updated by PHP in index.php
        });
    </script>
</body>
</html>