<h2>Import Room Block</h2>
<form id="input-room-block-form">
    <div class="mb-3">
        <label for="room-block-id-input" class="form-label d-block">Room Block ID</label>
        <input id="room-block-id-input" data-room-block-id name="room_block_id" placeholder="Insert Room Block ID" class="form-control d-inline-block" style="width: 250px;"/>
    </div>
    <button class="btn btn-primary" type="submit">Import</button>
</form>
<script>
    jQuery(function($){

        $('#input-room-block-form').on('submit', function(e){

            e.preventDefault();
            clear_notices();

            var $form = $(this),
                id = $form.find('[data-room-block-id]').val();

            if(id.trim() === ''){

                add_notice('danger', 'Insert a valid Room Block ID');
                return;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                beforeSend: function(){
                    $form.find(':input').prop('disabled', true);
                },
                data: {
                    action: 'hwzi_import_room_block',
                    id: id.trim()
                }
            })
            .done((response) => {
                add_notice(response.success ? 'success' : 'danger', response.message);
            })
            .fail(() => {
                add_notice('danger', 'An error ocurred while processing the request.')
            })
            .always(() => {
                $form.find(':input').prop('disabled', false);
            })
        });

        function clear_notices(){

            $('#input-room-block-form').find('[data-notice]').remove();
        }

        function add_notice(type = 'info', message = ''){

            if(message === '' || type === ''){
                return;
            }

            let notice = $('<div>').attr('data-notice', '').addClass('alert alert-' + type).html(message);
            
            $('#input-room-block-form').prepend(notice);
        }
    });
</script>