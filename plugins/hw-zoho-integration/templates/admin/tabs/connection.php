<h2>Connection</h2>
<p>
    API Token
    <input id="hwzi_api_token" type="text" name="hwzi_api_token" placeholder="Insert API token" />
</p>
<button id="submit-api" type="button">Update Token</button>
<script>
    jQuery(function($){

        $('#submit-api').on('click', function(){

            var token = $('#hwzi_api_token').val();

            /*if(token === ''){
                alert('Insert token');
                return;
            }*/

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data:{
                    action: 'hwzi_update_token',
                    token: token
                }
            }).done(function(response){

                if(response.success){
                    alert('token updated');
                }else{
                    alert(response.message);
                }
            });
        });
    });
</script>