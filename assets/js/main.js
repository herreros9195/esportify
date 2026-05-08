/**
 * Esportify - Scripts JavaScript
 * Filtres asynchrones, modal événement, et interactions UI.
 */

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Filtres asynchrones sur la page événements
    const filterForm = document.getElementById('filterForm');
    const eventsContainer = document.getElementById('eventsContainer');

    if (filterForm && eventsContainer) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);

            fetch('api/filter_events.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderEvents(data.events);
                    }
                })
                .catch(err => console.error('Erreur filtre:', err));
        });

        document.getElementById('resetFilters').addEventListener('click', function () {
            filterForm.reset();
            filterForm.dispatchEvent(new Event('submit'));
        });
    }

    function renderEvents(events) {
        eventsContainer.innerHTML = '';
        if (events.length === 0) {
            eventsContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Aucun événement ne correspond à vos critères.</div></div>';
            return;
        }
        events.forEach(evt => {
            const col = document.createElement('div');
            col.className = 'col-md-4 event-card';
            col.setAttribute('data-id', evt.id);
            col.innerHTML = `
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">${escapeHtml(evt.title)}</h5>
                        <p class="card-text text-muted small mb-1">
                            👥 ${evt.max_players} joueurs max<br>
                            📅 ${evt.start_date}<br>
                            👤 ${escapeHtml(evt.organizer_pseudo)}
                        </p>
                        <button class="btn btn-outline-primary btn-sm mt-2 btn-details" data-id="${evt.id}">
                            Voir les détails
                        </button>
                    </div>
                </div>
            `;
            eventsContainer.appendChild(col);
        });
        attachDetailButtons();
    }

    // Modal détails événement
    const eventModal = document.getElementById('eventModal');
    let modalInstance = null;
    if (eventModal) {
        modalInstance = new bootstrap.Modal(eventModal);
    }

    function attachDetailButtons() {
        document.querySelectorAll('.btn-details').forEach(btn => {
            btn.addEventListener('click', function () {
                const eventId = this.getAttribute('data-id');
                document.getElementById('modalTitle').textContent = 'Détail de l\'événement';
                document.getElementById('modalBody').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p>Chargement...</p></div>';
                modalInstance.show();

                fetch('index.php?page=event_detail&id=' + eventId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('modalBody').innerHTML = html;
                    })
                    .catch(() => {
                        document.getElementById('modalBody').innerHTML = '<div class="alert alert-danger">Erreur de chargement.</div>';
                    });
            });
        });
    }

    attachDetailButtons();

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
