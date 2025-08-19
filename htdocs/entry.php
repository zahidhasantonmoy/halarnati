<?php
require_once 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Add a New Entry</h3>
            </div>
            <div class="card-body">
                <form action="add_entry_handler.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="text" class="form-label">Text Content</label>
                        <textarea id="text" name="text" rows="5" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">Upload File (Optional)</label>
                        <input type="file" id="file" name="file" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="lock_key" class="form-label">Set a Password (Optional)</label>
                        <input type="password" id="lock_key" name="lock_key" class="form-control" placeholder="Leave blank for no password">
                    </div>
                    <button type="submit" name="submit_entry" class="btn btn-primary"><i class="fas fa-upload"></i> Submit Entry</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>