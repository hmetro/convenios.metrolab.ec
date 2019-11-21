var root = null;
var useHash = true; // Defaults to: false
var hash = '#!'; // Defaults to: '#'
var router = new Navigo(root, useHash, hash);
// Setear valores de cabecera
window.addEventListener("popstate", function(e) {
    if (!window.location.hash) {
        window.location.reload();
    }
});
// Setear valores de lnks para retroceder
window.addEventListener('click', event => {
    if (event.target.classList[event.target.classList.length - 1] == 'back') {
        event.preventDefault();
        history.back();
    }
});