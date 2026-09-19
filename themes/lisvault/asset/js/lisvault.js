document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('.menu-toggle');
    var navigation = document.querySelector('#main-nav');
    if (button && navigation) button.addEventListener('click', function () { var open = navigation.classList.toggle('is-open'); button.setAttribute('aria-expanded', open); });
});
