<?php require_once 'header.php'; ?>

<form action="add_paste.php" method="post">
    <div class="form-group full-width">
        <label for="content"><i class="fas fa-code"></i> Your Code / Text</label>
        <p class="description">Paste your content here. Select a language for syntax highlighting.</p>
        <textarea id="content" name="content" class="form-control" required></textarea>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Paste Title (Optional)</label>
            <input type="text" id="title" name="title" class="form-control" placeholder="e.g., My Database Connection">
        </div>

        <div class="form-group">
            <label for="language"><i class="fas fa-language"></i> Language</label>
            <select id="language" name="language" class="form-control">
                <option value="plaintext">Plain Text</option>
                <option value="php">PHP</option>
                <option value="javascript">JavaScript</option>
                <option value="css">CSS</option>
                <option value="html">HTML</option>
                <option value="sql">SQL</option>
                <option value="python">Python</option>
                <option value="java">Java</option>
                <option value="c">C</option>
                <option value="cpp">C++</option>
            </select>
        </div>

        <div class="form-group">
            <label for="expiration"><i class="fas fa-clock"></i> Expiration</label>
            <select id="expiration" name="expiration" class="form-control">
                <option value="never">Never</option>
                <option value="10m">10 Minutes</option>
                <option value="1h">1 Hour</option>
                <option value="1d">1 Day</option>
                <option value="1w">1 Week</option>
            </select>
        </div>

        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password (Optional)</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Lock your paste">
        </div>
    </div>

    <div class="form-group full-width">
        <button type="submit" class="btn-submit"><i class="fas fa-rocket"></i> Create Paste</button>
    </div>
</form>

<?php require_once 'footer.php'; ?>
