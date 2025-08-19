        </main>
        <footer class="main-footer">
            <?php
                // Fetch global stats
                $totalPastes = $conn->query("SELECT COUNT(*) AS total FROM entries")->fetch_assoc()["total"];
                $totalViews = $conn->query("SELECT SUM(view_count) AS total FROM entries")->fetch_assoc()["total"];
            ?>
            <div class="stats-container">
                <div class="stat-item">
                    <i class="fas fa-paste"></i> Total Pastes: <span><?= $totalPastes ?? 0 ?></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-eye"></i> Total Views: <span><?= $totalViews ?? 0 ?></span>
                </div>
            </div>
            <p>&copy; <?= date('Y') ?> CodeBin. All rights reserved.</p>
        </footer>
    </div>

    <!-- Prism JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
