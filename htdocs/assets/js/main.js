document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.querySelector('.copy-button');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const targetId = this.dataset.clipboardTarget;
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const textToCopy = targetElement.textContent;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    // Optional: Provide user feedback
                    this.textContent = 'Copied!';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                    alert('Failed to copy text.');
                });
            }
        });
    }
});