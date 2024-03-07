document.getElementById('Theme').addEventListener('click', () => {

    const htmlElement = document.querySelector('html');
    const currentTheme = htmlElement.getAttribute('data-bs-theme');

    // Basculer entre les thèmes
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';

    // Appliquer le nouveau thème
    htmlElement.setAttribute('data-bs-theme', newTheme);
});