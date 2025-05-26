"use strict";
window.isRtl = window.Helpers.isRtl(), window.isDarkStyle = window.Helpers.isDarkStyle();
let menu, animate, isHorizontalLayout = !1;
document.getElementById("layout-menu") && (isHorizontalLayout = document.getElementById("layout-menu").classList.contains("menu-horizontal")),
    function() {
        setTimeout(function() {
            window.Helpers.initCustomOptionCheck()
        }, 1e3), "undefined" != typeof Waves && (Waves.init(), Waves.attach(".btn[class*='btn-']:not(.position-relative):not([class*='btn-outline-']):not([class*='btn-label-'])", ["waves-light"]), Waves.attach("[class*='btn-outline-']:not(.position-relative)"), Waves.attach("[class*='btn-label-']:not(.position-relative)"), Waves.attach(".pagination .page-item .page-link"), Waves.attach(".dropdown-menu .dropdown-item"), Waves.attach(".light-style .list-group .list-group-item-action"), Waves.attach(".dark-style .list-group .list-group-item-action", ["waves-light"]), Waves.attach(".nav-tabs:not(.nav-tabs-widget) .nav-item .nav-link"), Waves.attach(".nav-pills .nav-item .nav-link", ["waves-light"])), document.querySelectorAll("#layout-menu").forEach(function(e) {
            menu = new Menu(e, {
                orientation: isHorizontalLayout ? "horizontal" : "vertical",
                closeChildren: !!isHorizontalLayout,
                showDropdownOnHover: localStorage.getItem("templateCustomizer-" + templateName + "--ShowDropdownOnHover") ? "true" === localStorage.getItem("templateCustomizer-" + templateName + "--ShowDropdownOnHover") : void 0 === window.templateCustomizer || window.templateCustomizer.settings.defaultShowDropdownOnHover
            }), window.Helpers.scrollToActive(animate = !1), window.Helpers.mainMenu = menu
        }), document.querySelectorAll(".layout-menu-toggle").forEach(e => {
            e.addEventListener("click", e => {
                if (e.preventDefault(), window.Helpers.toggleCollapsed(), config.enableMenuLocalStorage && !window.Helpers.isSmallScreen()) try {
                    localStorage.setItem("templateCustomizer-" + templateName + "--LayoutCollapsed", String(window.Helpers.isCollapsed()));
                    var t, a = document.querySelector(".template-customizer-layouts-options");
                    a && (t = window.Helpers.isCollapsed() ? "collapsed" : "expanded", a.querySelector(`input[value="${t}"]`).click())
                } catch (e) {}
            })
        }), window.Helpers.swipeIn(".drag-target", function(e) {
            window.Helpers.setCollapsed(!1)
        }), window.Helpers.swipeOut("#layout-menu", function(e) {
            window.Helpers.isSmallScreen() && window.Helpers.setCollapsed(!0)
        });
        let e = document.getElementsByClassName("menu-inner"),
            t = document.getElementsByClassName("menu-inner-shadow")[0];
        0 < e.length && t && e[0].addEventListener("ps-scroll-y", function() {
            this.querySelector(".ps__thumb-y").offsetTop ? t.style.display = "block" : t.style.display = "none"
        });
    }();