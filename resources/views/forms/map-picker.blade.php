@php
    $statePath = $getStatePath();
    $containerPath = \Illuminate\Support\Str::beforeLast($statePath, '.');
@endphp

<div
    wire:ignore
    x-data="{
        map: null,
        marker: null,
        hasMarker: false,
        cp: @js($containerPath),
        init() {
            this.$nextTick(() => {
                if (typeof L === 'undefined') return;
                if (this.map) return;
                try {
                    this.map = L.map(this.$refs.map).setView([-38.4161, -63.6167], 4);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);

                    setTimeout(() => this.map.invalidateSize(), 400);
                    const lat = this.$wire.get(`${this.cp}.latitude`);
                    const lng = this.$wire.get(`${this.cp}.longitude`);
                    if (lat && lng) {
                        this.marker = L.marker([parseFloat(lat), parseFloat(lng)], { draggable: true }).addTo(this.map);
                        this.hasMarker = true;
                        this.map.setView([parseFloat(lat), parseFloat(lng)], 15);
                        this.marker.on('dragend', (e) => {
                            const p = e.target.getLatLng();
                            this.setPos(p.lat, p.lng);
                        });
                    }
                    this.map.on('click', (e) => this.setPos(e.latlng.lat, e.latlng.lng));
                } catch (e) {
                    console.error('Map error:', e);
                }
            });
        },
        setPos(lat, lng) {
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', (e) => {
                    const p = e.target.getLatLng();
                    this.setPos(p.lat, p.lng);
                });
            }
            this.hasMarker = true;
            this.$wire.set(`${this.cp}.latitude`, lat.toFixed(6));
            this.$wire.set(`${this.cp}.longitude`, lng.toFixed(6));
        },
    }"
    x-init="init"
    class="fi-mp-map-picker"
>
    <div x-ref="map" style="height: 380px; border-radius: 0.5rem; z-index: 0;"></div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2" x-show="!hasMarker">
        Hacé clic en el mapa para marcar la ubicación exacta.
    </p>
</div>
