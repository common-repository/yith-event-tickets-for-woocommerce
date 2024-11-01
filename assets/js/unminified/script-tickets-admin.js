/* Yith Event Tickets for WooCommerce*/

jQuery(document).ready(function($){

    var services_index = $('.yith_wcevti_service_row').length;

    /***
     * Main Init
     * ***/
    main_init();

    function main_init() {
        set_general_tab();
        set_fields_tab();
        set_services_tab();
        set_map_tab();
        set_assistant_tab();
        set_email_template_tab();
        set_export_template_tab();
    }

    /***
     *  Start to set General Tab
     *  ***/
    function set_general_tab() {
        $('.pricing').addClass('show_if_ticket-event');
        $('#_tax_status').parents('.options_group').addClass('show_if_ticket-event');
        $( 'select#product-type' ).change();

        $(document.body).bind('woocommerce-product-type-change', function () {
            add_class_attributtes_ticket_event();
        });

        $('#general_product_data').on('click', '.yith_wcevti_toggle_button', function(event){
            event.preventDefault();

            var t = $(this),
                panel = t.data('panel');
            if($(this).hasClass('expand')){
                $('#' + panel).toggle(true);
            } else if ($(this).hasClass('close')){
                $('#' + panel).toggle(false);
            }
        });

        set_date_picker();

        set_time_picker();

        set_reduce_price();

        set_increase_by();

        set_increase_by_stock();

        set_increase_by_time();

        if(yith_wcevti_admin_tickets.remove.taxes_option){
            $('._tax_status_field ').closest('.options_group').remove();
        }
    }
     //Ask for current type selected product and if is ticket event add class attributes_ticket_event and display variations. Else remove class and hide variations.
    function add_class_attributtes_ticket_event() {
        var product_type = $( 'select#product-type' ).val();

        if('ticket-event' == product_type){
            $('#general_product_data>.pricing').show();
        }
    }

    function set_date_picker() {
        $('.yith-wcevti-datepicker').each(function () {

            $( this ).datepicker({
                dateFormat: yith_wcevti_admin_tickets.date_format,
                changeMonth: true,//this option for allowing user to select month
                changeYear: true //this option for allowing user to select from year range
            });
        })
    }

    function set_time_picker() {
        $('.yith-wceti-timepicker').keyup( function (e) {
            var value = $(this).val().replace(':', '');
            if(!isNaN(value)){
                if(2 == $(this).val().length & 8 != e.keyCode){
                    $(this).val($(this).val() + ':');
                }
                if(5 < $(this).val().length){
                    value = $(this).val().slice(0, -1);
                    $(this).val(value);
                }
            } else {
                value = $(this).val().slice(0, -1);
                $(this).val(value);
            }
        });
    }

    function set_reduce_price() {
        var enable_reduce_ticket = $('#_enable_reduce_ticket'),
            reduce_ticket_event_type = $('#_reduce_ticket_event_type'),
            price_reduced_fixed = $('#_price_reduced_fixed'),
            price_relative_fixed = $('#_price_relative_fixed'),
            reduced_panel = $('.yith-woocommerce-event-tickets-panel');

        enable_reduce_ticket.on('change', function () {
            var t = $(this);
            if(t.is(':checked')){
                reduced_panel.show();
            } else {
                reduced_panel.hide();
            }

        }).change();

        reduce_ticket_event_type.on('change', function () {
            var t = $(this);
            if('fixed' == t.val()){
                price_reduced_fixed.parent('p').show();
                price_relative_fixed.parent('p').hide();
            } else {
                price_reduced_fixed.parent('p').hide();
                price_relative_fixed.parent('p').show();
            }

        }).change();
    }

    function set_increase_by(){
        $('.yith_wcevti_increase_by_section').on('click', '.remove_increase_item', function (event) {
            event.preventDefault();

            $(this).closest('tr').remove();
        });

        $('.yith_wcevti_increase_by_section').on('click', '.yith_wcevti_increase_title', function(event){
            event.preventDefault();

            var t = $(this),
                container = t.parent(),
                panel = t.next();

            panel.slideToggle( 300, function(){
                container.toggleClass( 'opened' ).toggleClass( 'closed' );
            } );
        });

        $('.yith_wcevti_increase_by_section').on('change', '.yith-wceti-increase-type', function (event) {
            var $panel = $(this).closest('tr');

            switch ($(this).val()){
                case 'fixed':
                    $panel.find('.yith-wceti-increase-fixed').show();
                    $panel.find('.yith-wceti-increase-percentage').hide();
                    break;
                case 'percentage':
                    $panel.find('.yith-wceti-increase-fixed').hide();
                    $panel.find('.yith-wceti-increase-percentage').show();
                    break;
            }
        });

        $('.yith_wcevti_increase_by_section').find('tbody').sortable();

        $('.yith-wceti-increase-type').trigger('change');
    }

    function set_increase_by_stock() {
        set_manage_stock();
        $('#_add_increase_stock_row').click({rule: "stock"}, add_increase_by_rule);

    }

    function set_manage_stock() {
        $('._manage_stock_field').addClass('show_if_ticket-event');
        $( 'select#product-type' ).change();

        $('.enable_stock_link').click(display_inventory_product_data);

        $('#_manage_stock').change(check_manage_stock);
        $('#_manage_stock').trigger('change');

    }

    function check_manage_stock() {

        if($('#_manage_stock').is(":checked")){
            $('.enable-stock').hide();
            $('.increase_stock_panel, #_add_increase_stock_row').show();

        } else {
            $('.enable_stock_link').show();
            $('.increase_stock_panel, #_add_increase_stock_row').hide();
        }
    }

    function set_increase_by_time() {
        $('#_add_increase_time_row').click({rule: "time"}, add_increase_by_rule);
    }

    function add_increase_by_rule(event) {
        event.preventDefault();
        var rule = event.data.rule;

        $('.yith_wcevti_increase_' + rule + '_section').block({message:null, overlayCSS:{background:"#fff",opacity:.6}});

        var post_data =
        {
            action: 'print_increase_' + rule + '_row_action',
            index: $('.yith_evti_' + rule + '_row').length
        };

        $.post( ajaxurl, post_data ).success(function (data) {
            $('.increase_' + rule + '_panel').find('table').find('tbody').append(data);
            $('.yith_wcevti_increase_' + rule + '_section').unblock();
            $('.yith-wceti-increase-type').trigger('change');
        });


    }
    /***
     *  End to set General Tab
     *  ***/

    /***
     * Start to set Inventory Tab
     * ***/
    function display_inventory_product_data(ev) {
        ev.preventDefault();
        $('.general_tab').removeClass('active');
        $('.inventory_tab').addClass('active');
        $('#general_product_data').hide();
        $('#inventory_product_data').show();
    }
    /***
     *  End to set Inventory Tab
     *  ***/

    /***
     *  Start to set Fields Tab
     *  ***/
    function set_fields_tab() {
        $('#_add_fields_row').click(add_field_row);
        $('#fields_product_data').on('click', '.yith-wceti-remove-field-row button', function (event) {
            event.preventDefault();
            $(this).closest('tr').remove();
        });

        $('#fields_product_data').on('change', '.yith-wceti-field-type', function (event) {
            var $field_row = $(this).closest('.yith_wcevti_field_row');
            var $placeholder_tip = $field_row.find('.option-label>.woocommerce-help-tip');
            var $place_checked = $field_row.find('.option-placeholder>input');
            if('number' == $(this).val() && $place_checked.is(':checked')){
                $placeholder_tip.show();
            } else {
                $placeholder_tip.hide();
            }

            /*if('check' == $(this).val()){
                $field_row.find('.option-required>.checkbox').hide();
            }else {
                $field_row.find('.option-required>.checkbox').show();
            }*/
        });

        $('#fields_product_data').on('click', '.option-placeholder>input', function (event) {
            var $field_row = $(this).closest('.yith_wcevti_field_row');
            var $field_type = $field_row.find('.yith-wceti-field-type');
            var $placeholder_tip = $field_row.find('.option-label>.woocommerce-help-tip');

            if('number' == $field_type.val() && $(this).is(':checked')){
                $placeholder_tip.show();
            } else {
                $placeholder_tip.hide();
            }
        });

        $('.yith-wceti-field-type').trigger('change');
        $('.field-options').find('tbody').sortable();
    }

    function add_field_row(event) {
        event.preventDefault();

        $('#fields_product_data').block({message:null, overlayCSS:{background:"#fff",opacity:.6}});
        var post_data =
        {
            action: 'print_field_row_action',
            index: $('.yith_wcevti_field_row').length
        };

        $.post( ajaxurl, post_data ).success(function (data) {
            $('.fields_panel').find('table.field-options tbody').append(data);
            $('#fields_product_data').unblock();
        });

    }
    /***
     *  End to set Fields Tab
     *  ***/

    /***
     *  Start to set Services Tab
     *  ***/
    function set_services_tab() {

        $('#_show_service_type_selector').click(show_service_selector);
        $('#_cancel_add_service').click(hide_service_selector);
        $('#_add_service_row').click(add_service_row);

        $('#services_product_data').on('click', '.add_select_button', function (event) {
            add_service_select_row(event);
        });
        $('#services_product_data').on('click', '.yith_wcevti_toggle_button', function(event){
            event.preventDefault();

            var t = $(this),
                panel = t.data('panel');
            if($(this).hasClass('expand')){
                $('#' + panel).toggle(true);
            } else if ($(this).hasClass('close')){
                $('#' + panel).toggle(false);
            }
        });
        $('#services_product_data').on('click', '._service_row_buttons>button', function (event) {
            event.preventDefault();
            var $service_row = $(this).closest('.yith_wcevti_service_row');

            if($(this).hasClass('add_select_button')){
                $service_row.find('.add_checkbox_button').prop('disabled', true);
                $service_row.find('.yith_wcevti-service-type').val('select');
                $service_row.find('.yith_wcevti_service_check_panel').hide();
                $service_row.find('.yith_wcevti_service_select_panel').show();
            }
            if($(this).hasClass('add_checkbox_button')){
                $service_row.find('.add_select_button').prop('disabled', true);
                $service_row.find('.yith_wcevti_service_check_panel').show();

                $service_row.find('.yith_wcevti-service-type').val('checkbox');
            }

        });
        $('#services_product_data').on('click', '.remove_service_row', function (event) {
            event.preventDefault();
            if(confirm(yith_wcevti_admin_tickets.message.remove_service)){
                $(this).closest('.yith_wcevti_service_row').remove();
            }
        });
        $('#services_product_data').on('click', '.remove_service_item', function (event) {
            event.preventDefault();

            var $service_select_row = $(this).closest('tr');

            $service_select_row.remove();
        });
        $('#services_product_data').on('click', '.yith-evti-service-handle', function () {
            var t = $(this),
                service_row = t.closest('.yith_wcevti_service_row'),
                service_panel = service_row.find( '.yith-evti-service-panel' );

            service_panel.slideToggle( 300, function(){
                service_row.toggleClass( 'opened' ).toggleClass( 'closed' );
            } );
        });
        $('#services_product_data').on('keyup', '.yith-wceti-service-label', function(){
            var t = $(this),
                row = t.closest('.yith_wcevti_service_row'),
                title_h = row.find('.yith-evti-service-handle').find('h3'),
                target_text = t.val();

            title_h.text( target_text.length ? target_text : '-' );
        });
        $('#services_product_data').on('change', '.service-type-container select', function(){
            var t = $(this),
                val = t.val(),
                panel = t.closest('.yith-evti-service-panel'),
                container = panel.closest('.yith_wcevti_service_row '),
                checkbox_heading = panel.find('.service-checkbox-heading'),
                select_heading = panel.find('.service-select-heading'),
                select_options = panel.find('.yith_wcevti_service_select_panel')
                service_type_label = container.find('.service-type');

            if( val == 'checkbox' ){
                checkbox_heading.css('display', 'inline-block');
                select_heading.hide();
                select_options.hide();
                service_type_label
                    .find('.service-type-select')
                    .hide()
                    .end()
                    .find('.service-type-checkbox')
                    .show();
            }
            else if( val == 'select' ){
                checkbox_heading.hide();
                select_heading.css('display', 'inline-block');
                select_options.show();
                service_type_label
                    .find('.service-type-select')
                    .show()
                    .end()
                    .find('.service-type-checkbox')
                    .hide();
            }
        });

        $('#services_product_data').find('.services_panel').sortable();
        $('.service-options-select').find('tbody').sortable();
        $('.service-type-container select').change();
    }

    function show_service_selector(ev){
        var show_service_type_selector = $('#_show_service_type_selector'),
            service_selector = $('#_service_type_selector_container');

        if( typeof(ev) != 'undefined' ) {
            ev.preventDefault();
        }
        show_service_type_selector.hide();
        service_selector.slideDown();
    }

    function hide_service_selector(ev){
        var show_service_type_selector = $('#_show_service_type_selector'),
            service_selector = $('#_service_type_selector_container');

        if( typeof(ev) != 'undefined' ) {
            ev.preventDefault();
        }
        show_service_type_selector.show();
        service_selector.slideUp();
    }

    function add_service_row(event) {
        event.preventDefault();

        $('#services_product_data').block({message:null, overlayCSS:{background:"#fff",opacity:.6}});

        var index = services_index ++,
            post_data = {
                action: 'print_service_row_action',
                index:  index,
                type:   $('#_service_type_selector').val()
            };

        $.post(ajaxurl, post_data).success(function (data) {
            $('.yith-evti-service-panel').slideUp( 300, function(){
                $(this).parent().removeClass( 'opened' ).addClass( 'closed' );
            } );

            $('.services_panel')
                .prepend( data )
                .find('.yith_wcevti_service_row:first-child')
                .find('.yith-evti-service-panel')
                .slideUp( 300, function(){
                    $(this).parent().addClass( 'opened' ).removeClass( 'closed' );
                } );

            $('.service-type-container select').change();
            $('.service-options-select').find('tbody').sortable();
            $('#services_product_data').unblock();

            hide_service_selector();
        });
    }

    function add_service_select_row(event) {
        event.preventDefault();
        var $service_row = $(event.target).closest('.yith_wcevti_service_row');
        var num_rows = $service_row.find('.yith_wcevti_service_select_panel').data('rows');

        num_rows++;
        $service_row.find('.yith_wcevti_service_select_panel').data('rows', num_rows);
        var row_index = $service_row.data('index');

        $service_row.block({message:null, overlayCSS:{background:"#fff",opacity:.6}});

        var post_data = {
            action: 'print_select_service_row_action',
            index: num_rows,
            row_index: row_index,
            service_label: ''
        };

        $.post(ajaxurl, post_data).success(function (data) {
            $service_row.find('.yith_wcevti_service_select_panel').find('table').find('tbody').append(data);
            $service_row.unblock();
        })

    }
    /***
     *  End to set Services Tab
     *  ***/

    /***
     * Start to set Map Tab
     * ***/
    function set_map_tab() {
        $( document ).on( 'click', '.map_options', function () {
            var container = $('#map_product_data'),
                divMap    = container.find( '.yith_wcevti_address_map' );

            if ( divMap.length > 0 ) {
                var latitude  = container.find( '.yith_wcevti_latitude_event' ).val(),
                    longitude = container.find( '.yith_wcevti_longitude_event' ).val(),
                    latlng    = new google.maps.LatLng( latitude, longitude ),
                    myMap     = load_map_address( divMap[ 0 ], latlng ),
                    marker    = load_marker( latlng, myMap );

                refresh_map( myMap, marker, latlng );

                load_search_box( myMap, container, marker );

                google.maps.event.trigger( myMap, 'resize' );

            }
        } );
        $('#_direction_event_field').on('change', function () {
            if( 0 == $(this).val().length ){
                $('#_display_map_tab').prop('checked', false);
                $('#_display_map_tab').prop('disabled', true);

            } else {
                $('#_display_map_tab').prop('disabled', false);
            }
        });
        $('#_direction_event_field').trigger('change');
    }

    function load_map_address( divMap, latlng ) {

        var mapOptions =
        {
            zoom  : 15,
            center: latlng
        };
        var map        = new google.maps.Map( divMap, mapOptions );

        return map;
    }

    function load_marker( latlng, map ) {
        var marker = new google.maps.Marker( {
            position: latlng,
            map     : map
        } );
        return marker;
    }

    function load_search_box( map, container, marker ) {
        var inputSearch     = container.find( '.yith_wcevti_direction_event' ),
            latitude_field  = container.find( '.yith_wcevti_latitude_event' ),
            longitude_field = container.find( '.yith_wcevti_longitude_event' );
        inputSearch.keyup(function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
        });
        // Create the search box and link it to the UI element.
        var searchBox = new google.maps.places.SearchBox( inputSearch[ 0 ] );

        // Bias the SearchBox results towards current map's viewport.
        map.addListener( 'bounds_changed', function () {
            searchBox.setBounds( map.getBounds() );
        } );

        var markers = [];
        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        searchBox.addListener( 'places_changed', function () {
            var places = searchBox.getPlaces();

            if ( places.length == 0 ) {
                return;
            }

            places.forEach( function ( place ) {
                refresh_map( map, marker, place.geometry.location );
            } );

            latitude_field.val( marker.getPosition().lat() );
            longitude_field.val( marker.getPosition().lng() );
        } );
    }

    function refresh_map( map, marker, latlng ) {
        map.setCenter( latlng );
        marker.setPosition( latlng );
    }
    /***
     *  End to set Map Tab
     *  ***/

    /***
     * Start to set Asistant Tab
     * ***/
    function set_assistant_tab() {
        $('#_display_tab_assistants').on('change', function () {
            var t = $(this);
            if(t.is(':checked')){
                $('._display_organizers_field').show();
            } else {
                $('._display_organizers_field').hide();
            }

        }).change();
    }
    /***
     *  End to set Asistant Tab
     *  ***/

    /***
     * Start to set Email Template Tab
     * ***/
    function set_email_template_tab() {
        $('#_display_barcode').on('change', function () {
            var t = $(this);
            if(t.is(':checked')){
                $('._type_barcode_field').show();
            } else {
                $('._type_barcode_field').hide();
            }

        }).change();

        $('.yith_wcevti_mail_template_default .yith_wcevti_mail_button').click(function (e) {
            e.preventDefault();

            $uri_field = $(this).next('.image_uri');
            $id_field = $($uri_field).next('.image_id');

            var image = wp.media({
                title: 'Upload Image',
                // mutiple: true if you want to upload multiple files at once
                multiple: false
            }).open()
                .on('select', function(e){
                    // This will return the selected image from the Media Uploader, the result is an object
                    var uploaded_image = image.state().get('selection').first();
                    // We convert uploaded_image to a JSON object to make accessing it easier
                    // Output to the console uploaded_image

                    var image_url = uploaded_image.toJSON().url;
                    var id_image = uploaded_image.toJSON().id;
                    // Let's assign the url value to the input field
                    $uri_field.val(image_url);
                    $id_field.val(id_image);
                });

        });

        $('.yith_wcevti_mail_template_default .yith_wcevti_mail_remove_button').click(function (e) {
            e.preventDefault();
            $id_field = $(this).prev('.image_id');
            $uri_field = $($id_field).prev('.image_uri');

            $id_field.val('');
            $uri_field.val('');
        });

    }
    /***
     *  End to set Email Template
     *  ***/

    /***
     * Start to set Export Template Tab
     * ***/
    function set_export_template_tab() {
        $('#_export_csv_button>a').click(function (event) {
           event.preventDefault();
        });

    }
    /***
     *  End to set Export Template
     *  ***/
});