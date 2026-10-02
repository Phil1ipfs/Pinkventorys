// Product search suggestions for the navbar search box (backed by search_api.php).
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search');
    const suggestionsBox = document.getElementById('suggestions');
    if (!searchInput || !suggestionsBox) return;

    let latestRequest = 0;

    searchInput.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length <= 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        const requestId = ++latestRequest;
        fetch('search_api.php?q=' + encodeURIComponent(query), { credentials: 'same-origin' })
            .then(response => response.ok ? response.json() : [])
            .then(data => {
                if (requestId !== latestRequest) return; // a newer search is in flight
                suggestionsBox.innerHTML = '';
                data.slice(0, 5).forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = item;
                    div.classList.add('suggestion-item');
                    div.onclick = () => {
                        searchInput.value = item;
                        suggestionsBox.style.display = 'none';
                    };
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = data.length ? 'block' : 'none';
            })
            .catch(() => {
                suggestionsBox.style.display = 'none';
            });
    });
});
