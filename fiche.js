document.addEventListener('DOMContentLoaded', () => {
    const dialogue = document.getElementById('visionneuse');
    const image    = document.getElementById('visionneuse-img');
    const legende  = document.getElementById('visionneuse-legende');
    const fermer   = document.getElementById('visionneuse-fermer');

    document.querySelectorAll('.galerie__vignette').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            image.src = bouton.dataset.full;
            image.alt = bouton.dataset.legende || '';
            legende.textContent = bouton.dataset.legende || '';
            if (typeof dialogue.showModal === 'function') {
                dialogue.showModal();
            }
        });
    });

    fermer.addEventListener('click', () => dialogue.close());

    dialogue.addEventListener('click', (ev) => {
        if (ev.target === dialogue) dialogue.close();
    });
});
