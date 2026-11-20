import Alpine from 'alpinejs';

// 送信の前に、確認のモーダルを出す（Rails 版の confirm_controller.js と同じ動き）
// - 囲んだ要素で submit を一度止めて <dialog x-ref="dialog"> を開き、「確定」で送り直す
// - diff: true のときは、data-column の付いた入力欄の「変更前（data-original）→ 変更後」を changes に入れる
//   （画面には x-text で出すので、入力値に HTML が入っていても安全）
Alpine.data('confirm', ({ diff = false } = {}) => ({
    accepted: false,
    busy: false,
    changes: [],

    open(event) {
        // 「確定」を押したあとの送り直しは、そのまま通す
        if (this.accepted) {
            this.accepted = false;
            return;
        }
        event.preventDefault();
        this.form = event.target;
        if (diff) {
            this.changes = [...this.$root.querySelectorAll('[data-column]')]
                .map((f) => ({ column: f.dataset.column, before: f.dataset.original, after: f.value.trim() }))
                .filter((c) => c.before !== c.after);
        }
        this.busy = false;
        this.$refs.dialog.showModal();
    },

    accept() {
        this.accepted = true;
        this.busy = true;
        this.form.requestSubmit();
    },

    cancel() {
        this.$refs.dialog.close();
    },

    // 後ろの暗い部分をクリックしたら閉じる
    backdrop(event) {
        if (event.target === this.$refs.dialog) this.cancel();
    },
}));

window.Alpine = Alpine;
Alpine.start();
