const pad = (n) => String(n).padStart(2, '0');

export function formatDuration(totalSeconds) {
    const s = Math.max(0, Math.floor(totalSeconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h > 0 ? `${h}:${pad(m)}:${pad(s % 60)}` : `${pad(m)}:${pad(s % 60)}`;
}

export function registerTimers(Alpine) {
    // Общий таймер тренировки: считает от started_at (секунды Unix).
    Alpine.data('workoutClock', (startedAt) => ({
        label: '00:00',
        init() {
            const tick = () => (this.label = formatDuration(Date.now() / 1000 - startedAt));
            tick();
            this.interval = setInterval(tick, 1000);
        },
        destroy() {
            clearInterval(this.interval);
        },
    }));

    // Таймер отдыха: запускается событием set-logged, переживает перезагрузку страницы.
    Alpine.data('restTimer', (storageKey) => ({
        endsAt: 0,
        remaining: 0,
        total: 0,
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                if (saved && saved.endsAt > Date.now()) {
                    this.endsAt = saved.endsAt;
                    this.total = saved.total;
                }
            } catch (e) {}
            this.interval = setInterval(() => this.tick(), 250);
            this.tick();
        },
        destroy() {
            clearInterval(this.interval);
        },
        start(seconds) {
            this.total = seconds;
            this.endsAt = Date.now() + seconds * 1000;
            this.save();
            this.tick();
        },
        add(seconds) {
            if (!this.active) return this.start(seconds);
            this.endsAt += seconds * 1000;
            this.total += seconds;
            this.save();
        },
        skip() {
            this.endsAt = 0;
            this.save();
            this.tick();
        },
        save() {
            try {
                localStorage.setItem(storageKey, JSON.stringify({ endsAt: this.endsAt, total: this.total }));
            } catch (e) {}
        },
        tick() {
            const left = Math.ceil((this.endsAt - Date.now()) / 1000);
            if (this.remaining > 0 && left <= 0 && this.endsAt) {
                this.endsAt = 0;
                this.save();
                if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
            }
            this.remaining = Math.max(0, left);
        },
        get active() {
            return this.remaining > 0;
        },
        get label() {
            return formatDuration(this.remaining);
        },
        get progress() {
            return this.total ? (this.remaining / this.total) * 100 : 0;
        },
    }));
}
