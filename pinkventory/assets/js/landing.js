// Landing page. Loaded in <head> so the "js" class is set before first paint
// (scroll-reveal content stays visible if JavaScript is unavailable).
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Mobile menu
    var nav = document.getElementById('siteNav');
    var toggle = document.getElementById('menuToggle');
    toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
    nav.querySelectorAll('.nav-links a').forEach(function (link) {
        link.addEventListener('click', function () {
            nav.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    // Border under the nav once the page scrolls
    var onScroll = function () {
        nav.classList.toggle('scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    // Fade sections in as they enter the viewport
    var items = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        items.forEach(function (item) { observer.observe(item); });
    } else {
        items.forEach(function (item) { item.classList.add('in'); });
    }
});
