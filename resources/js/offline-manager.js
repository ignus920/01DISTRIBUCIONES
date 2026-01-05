document.addEventListener('alpine:init', () => {
    Alpine.store('offline', {
        isOnline: navigator.onLine,
        pendingSync: JSON.parse(localStorage.getItem('pending_sync') || '[]'),

        init() {
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.sync();
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });
        },

        // Guardar una acción para sincronizar después
        queueAction(type, data) {
            this.pendingSync.push({
                id: Date.now(),
                type: type,
                data: data,
                timestamp: new Date().toISOString()
            });
            this.save();

            window.Swal.fire({
                title: 'Modo Offline',
                text: 'Los datos se guardaron localmente y se sincronizarán al recuperar la conexión.',
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },

        save() {
            localStorage.setItem('pending_sync', JSON.stringify(this.pendingSync));
        },

        async sync() {
            if (this.pendingSync.length === 0) return;

            window.Swal.fire({
                title: 'Sincronizando...',
                text: `Subiendo ${this.pendingSync.length} acciones pendientes`,
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });

            // Aquí iría la lógica de envío al servidor para cada tipo de acción
            // Por ahora simulamos el éxito despejando la cola
            // En una implementación real, dispararíamos eventos a Livewire o fetch a la API

            for (const action of this.pendingSync) {
                console.log('Syncing action:', action);
                // Ejemplo: Livewire.emit('syncAction', action);
            }

            this.pendingSync = [];
            this.save();
        }
    });
});
