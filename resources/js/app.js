document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                window.location.reload();
                return;
            }

            if (status >= 500) {
                preventDefault();
                console.error('Operation could not be completed. Please refresh the page or try again.');
            }
        });
    });
});
