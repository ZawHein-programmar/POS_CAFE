</div>
<!--**********************************
            Footer start
        ***********************************-->
<div class="footer">
    <div class="copyright">
        <p>Copyright &copy; Designed & Developed by <a href="https://themeforest.net/user/quixlab">Quixlab</a> 2018</p>
    </div>
</div>
<!--**********************************
            Footer end
        ***********************************-->
</div>
<!--**********************************
        Main wrapper end
    ***********************************-->

<!--**********************************
        Scripts
    ***********************************-->
<script src="plugins/common/common.min.js"></script>
<script src="js/custom.min.js"></script>
<script src="js/settings.js"></script>
<script src="js/gleek.js"></script>
<script src="js/styleSwitcher.js"></script>
<script src="./plugins/highlightjs/highlight.pack.min.js"></script>
<script>
    hljs.initHighlightingOnLoad();
    (function($) {
        "use strict"
        var settings = new quixSettings({
            version: "light", //2 options "light" and "dark"
            layout: "vertical", //2 options, "vertical" and "horizontal"
            navheaderBg: "color_1", //have 10 options, "color_1" to "color_10"
            headerBg: "color_1", //have 10 options, "color_1" to "color_10"
            sidebarStyle: "full", //defines how sidebar should look like, options are: "full", "compact", "mini" and "overlay". If layout is "horizontal", sidebarStyle won't take "overlay" argument anymore, this will turn into "full" automatically!
            sidebarBg: "color_1", //have 10 options, "color_1" to "color_10"
            sidebarPosition: "fixed", //have two options, "static" and "fixed"
            headerPosition: "fixed", //have two options, "static" and "fixed"
            containerLayout: "vertical", //"boxed" and  "wide". If layout "vertical" and containerLayout "boxed", sidebarStyle will automatically turn into "overlay".
            direction: "ltr" //"ltr" = Left to Right; "rtl" = Right to Left
        });

        const themeSwitcher = document.getElementById('theme-switcher');
        const themeIcon = themeSwitcher.querySelector('i');

        themeSwitcher.addEventListener('click', () => {
            if (document.body.classList.contains('dark-theme')) {
                settings.version = "light";
                document.body.classList.remove('dark-theme');
                themeIcon.classList.remove('fa-sun-o');
                themeIcon.classList.add('fa-moon-o');
                localStorage.setItem('theme', 'light');
            } else {
                settings.version = "dark";
                document.body.classList.add('dark-theme');
                themeIcon.classList.remove('fa-moon-o');
                themeIcon.classList.add('fa-sun-o');
                localStorage.setItem('theme', 'dark');
            }
            // Re-initialize the settings to apply the new theme
            new quixSettings(settings);
        });

        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            settings.version = "dark";
            document.body.classList.add('dark-theme');
            themeIcon.classList.remove('fa-moon-o');
            themeIcon.classList.add('fa-sun-o');
            new quixSettings(settings);
        }

    })(jQuery);
</script>
</body>

</html>