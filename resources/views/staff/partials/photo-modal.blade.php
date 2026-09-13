<div id="staff-photo-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/75 p-4" role="dialog" aria-modal="true" aria-label="Photo du personnel">
    <div class="relative rounded-2xl bg-white p-2 shadow-2xl" style="width:min(900px,90vw);max-height:90vh;" data-staff-photo-dialog>
        <button type="button" class="absolute -right-3 -top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white text-xl font-bold text-gray-600 shadow-lg hover:bg-gray-100" data-close-staff-photo aria-label="Fermer">&times;</button>
        <img src="" alt="" class="block w-full rounded-xl object-contain" style="max-height:85vh;" data-staff-photo-image>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('staff-photo-modal');
    if (!modal) return;
    const image = modal.querySelector('[data-staff-photo-image]');

    window.openStaffPhoto = (src, alt = '') => {
        image.src = src;
        image.alt = alt;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        image.src = '';
    };

    modal.addEventListener('click', (event) => {
        if (event.target === modal || event.target.closest('[data-close-staff-photo]')) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
})();
</script>
