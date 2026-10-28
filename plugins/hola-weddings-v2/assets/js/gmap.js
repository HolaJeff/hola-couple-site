
    class Gmap{
            
        constructor( el, options = {} ){
            
            this.el = jQuery(el);
            this.lat = parseFloat(options.lat);
            this.latElId = this.el.data('input-latitude');
            this.long = parseFloat(options.long);
            this.longElId = this.el.data('input-longitude');
            
            if(isNaN(this.lat) || isNaN(this.long)){
                
                throw "Longitude and Latitude are required";   
            }
            
            var self = this;
            var propertyPoint = new google.maps.LatLng( this.lat, this.long );
            
            this.mapOptions = Object.assign(
                {},
                {
                    zoom: 15,
                    center: propertyPoint,
                    mapTypeId: 'roadmap',
                    mapTypeControl: true,
                    navigationControl: true
                },
                options
            );

            //this.geocoder = new google.maps.Geocoder();
		    this.map = new google.maps.Map( this.el[0], this.mapOptions );
            
            this.marker = new google.maps.Marker({
                    map: this.map,
                    draggable: true,
                    animation: google.maps.Animation.DROP,
                    position: propertyPoint
            });
                
            //this.markers.push( marker );
            google.maps.event.addListener( this.marker, 'dragend', () => { self.setCoordinates(); } );
            google.maps.event.addListener( this.marker, 'position_changed', () => { self.setCoordinates(); } );
            
            this.el[0].gmap = this.map;
            this.el[0].gmapmarker = this.marker;
        }
        
        setCoordinates(){
            var point = this.marker.getPosition();
		    this.map.panTo(point);
		    jQuery( this.latElId).val( point.lat().toFixed(5) );
		    jQuery( this.longElId).val( point.lng().toFixed(5) );
        }
    }
