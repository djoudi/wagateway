import './bootstrap';

window.addEventListener('copy-to-clipboard', (e) => {
    navigator.clipboard.writeText(e.detail.text).then(() => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: [{ type: 'success', message: 'Copied to clipboard!' }]
        }));
    });
});
