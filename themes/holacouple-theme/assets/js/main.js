jQuery(function($){
   
   // Add smooth scrolling to all links
    $('a[href^="#"]').on('click', function(event) {
        
        if( $(this).hasClass('no-scroll') ){
            
            return;
        }
  
        // Make sure this.hash has a value before overriding default behavior
        if (this.hash !== "") {
            
            // Prevent default anchor click behavior
            event.preventDefault();
            
            // Store hash
            var hash = this.hash;
            
            // Using jQuery's animate() method to add smooth page scroll
            // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
            console.log($(hash).length);
            if($(hash).length > 0){
                $('html, body').animate({
                
                    scrollTop: $(hash).offset().top - 100
                }, 500, 'linear', function(){
          
                  // Add hash (#) to URL when done scrolling (default click behavior)
                  window.location.hash = hash;
                });
            }
        } // End if
    });
    
    $('.offcanvas-trigger').click(function(){console.log('hola'); });
    $('.offcanvas-trigger').click(toggleNavigation);
    $('#offcanvas-overlay').click(toggleNavigation);
    $('#offcanvas .toggle-close').click(toggleNavigation);
    
});

function toggleNavigation(event) {
    event.preventDefault();
    jQuery('body').toggleClass('offcanvas-open');
}