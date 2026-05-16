/**
 * Esportify - Scripts JavaScript
 * Filtres asynchrones, modal evenement et chat.
 */

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    const eventsContainer = document.getElementById('eventsContainer');

    if (filterForm && eventsContainer) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);

            fetch('api/filter_events.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderEvents(data.events);
                    }
                })
                .catch(error => console.error('Erreur filtre:', error));
        });

        const resetFiltersButton = document.getElementById('resetFilters');
        if (resetFiltersButton) {
            resetFiltersButton.addEventListener('click', function () {
                filterForm.reset();
                filterForm.dispatchEvent(new Event('submit'));
            });
        }
    }

    function renderEvents(events) {
        eventsContainer.innerHTML = '';
        if (events.length === 0) {
            eventsContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Aucun evenement ne correspond a vos criteres.</div></div>';
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
                            ${evt.max_players} joueurs max<br>
                            Debut : ${evt.start_date}<br>
                            Fin : ${evt.end_date}
                        </p>
                        <button class="btn btn-outline-primary btn-sm mt-2 btn-details" data-id="${evt.id}">
                            Voir les details
                        </button>
                    </div>
                </div>
            `;
            eventsContainer.appendChild(col);
        });

        attachDetailButtons();
    }

    const eventModal = document.getElementById('eventModal');
    let modalInstance = null;
    if (eventModal) {
        modalInstance = new bootstrap.Modal(eventModal);
    }

    function attachDetailButtons() {
        document.querySelectorAll('.btn-details').forEach(button => {
            button.addEventListener('click', function () {
                const eventId = this.getAttribute('data-id');
                document.getElementById('modalTitle').textContent = 'Detail de l\'evenement';
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

    const chatForm = document.getElementById('chatForm');
    const chatMessages = document.getElementById('chatMessages');
    const chatEventId = document.getElementById('chatEventId');

    if (chatForm && chatMessages && chatEventId) {
        const eventId = chatEventId.value;

        function loadChat() {
            fetch('api/chat.php?event_id=' + eventId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.messages.length === 0) {
                            chatMessages.innerHTML = '<p class="text-muted text-center">Aucun message pour le moment. Soyez le premier !</p>';
                        } else {
                            chatMessages.innerHTML = data.messages.map(message =>
                                `<div class="mb-2"><strong>${escapeHtml(message.pseudo)}</strong> <small class="text-muted">${message.created_at}</small><br>${escapeHtml(message.message)}</div>`
                            ).join('');
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    } else if (data.error) {
                        chatMessages.innerHTML = `<p class="text-danger text-center">${escapeHtml(data.error)}</p>`;
                    }
                })
                .catch(() => {
                    chatMessages.innerHTML = '<p class="text-danger text-center">Erreur de chargement du chat.</p>';
                });
        }

        loadChat();
        setInterval(loadChat, 5000);

        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) {
                return;
            }

            const formData = new FormData();
            formData.append('event_id', eventId);
            formData.append('message', message);
            formData.append('csrf_token', document.getElementById('chatCsrf').value);

            fetch('api/chat.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        loadChat();
                    } else {
                        alert(data.error || 'Erreur lors de l\'envoi');
                    }
                })
                .catch(() => alert('Erreur reseau'));
        });
    }
});
