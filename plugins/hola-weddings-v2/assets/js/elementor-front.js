/*
 * Wedding Gallery Widget
 */
class WeddingGallery extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings(){
        return {
            selectors: {
                wrapper: '.elementor-wedding-gallery__wrapper',
            },
        };
    }
     
    getDefaultElements(){
        
        const selectors = this.getSettings( 'selectors' );
        return {
            $wrapper: this.$element.find( selectors.wrapper ),
        };
    }
     
    bindEvents(){
        
        this.elements.$wrapper.justifiedGallery({
            rowHeight: 200    
        }).on("jg.complete", function () {
            this.elements.$wrapper.find('a').fancybox({
                groupAll : true
            });
        });
    }
}

/*
 * Hotel Gallery Widget
 */
class HotelGallery extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings(){
        return {
            selectors: {
                wrapper: '.elementor-hotel-gallery__wrapper',
            },
        };
    }
     
    getDefaultElements(){
        
        const selectors = this.getSettings( 'selectors' );
        return {
            $wrapper: this.$element.find( selectors.wrapper ),
        };
    }
     
    bindEvents(){
        
        this.elements.$wrapper.slick({
            arrows: true,
            dots: true
        });
    }
}

jQuery( window ).on( 'elementor/frontend/init', () => {
   addHandler = ( $element ) => {
       elementorFrontend.elementsHandler.addHandler( WeddingGallery, {
           $element,
       } );
   };
   elementorFrontend.hooks.addAction( 'frontend/element_ready/wedding_gallery.default', addHandler );
   
   addHandler = ( $element ) => {
       elementorFrontend.elementsHandler.addHandler( HotelGallery, {
           $element,
       } );
   };
   elementorFrontend.hooks.addAction( 'frontend/element_ready/hotel_gallery.default', addHandler );
} );