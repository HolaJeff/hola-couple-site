jQuery(function ($) {
    function checkVisibility() {
        $('.revealer').each(function () {
            
            var elementTop = $(this).offset().top;
            var elementHeight = $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportHeight = $(window).height();
            var triggerPoint = viewportTop + viewportHeight / 2;

            if (triggerPoint >= elementTop && triggerPoint <= elementTop + elementHeight) {
                $(this).addClass('animate');
            }

            $(this).on('animationend webkitAnimationEnd', () => { 
                $(this).addClass('animation-end');
            });
        }); 
    }

    $(window).on('scroll resize', checkVisibility);
    checkVisibility(); // Initial check on page load
});
