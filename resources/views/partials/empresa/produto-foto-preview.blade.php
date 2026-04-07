<script>
(function () {
    var input = document.getElementById('foto');
    var img = document.getElementById('foto-preview');
    var wrap = document.getElementById('foto-preview-wrap');
    var caption = document.getElementById('foto-preview-caption');
    if (!input || !img) return;

    input.addEventListener('change', function () {
        var f = this.files && this.files[0];
        if (!f || !/^image\//.test(f.type)) {
            return;
        }
        var r = new FileReader();
        r.onload = function () {
            img.src = r.result;
            img.classList.remove('d-none');
            if (wrap) wrap.classList.remove('d-none');
            if (caption) caption.textContent = 'Prévia (será gravada ao salvar)';
        };
        r.readAsDataURL(f);
    });
})();
</script>
