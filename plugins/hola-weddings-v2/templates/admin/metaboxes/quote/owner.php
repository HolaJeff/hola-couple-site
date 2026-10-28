<?php
/** @var int    $owner_id */
/** @var string $owner_label */
/** @var array  $users      — each item has ID, user_login, user_email */
/** @var string $nonce */
/** @var int    $quote_id */
?>
<?php wp_nonce_field( 'hw_quote_owner_' . $quote_id, 'hw_quote_owner_nonce' ); ?>

<style>
.hw-owner-item {
    display: block;
    width: 100%;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    color: #1d2327;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.hw-owner-item:hover {
    background-color: #f0f0f1;
}
.hw-owner-item.active {
    background-color: #2271b1;
    color: #fff;
}
.hw-owner-item.active em {
    color: #fff;
}
</style>

<input type="hidden" name="hw_owner_user_id" id="hw_owner_user_id_value" value="<?= intval( $owner_id ) ?>">

<div id="hw_owner_wrap" style="position:relative">

    <button type="button" id="hw_owner_btn"
            class="btn border w-100 d-flex justify-content-between align-items-center text-start">
        <span id="hw_owner_label" class="text-truncate"><?= esc_html( $owner_label ) ?></span>
        <i class="fas fa-chevron-down ms-2 flex-shrink-0 small"></i>
    </button>

    <div id="hw_owner_panel" class="border rounded bg-white shadow-sm"
         style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;margin-top:2px">

        <div class="p-1 border-bottom">
            <input type="text"
                   id="hw_owner_search"
                   class="form-control form-control-sm"
                   placeholder="<?= esc_attr__( 'Search users…', 'hola-weddings' ) ?>"
                   autocomplete="off">
        </div>

        <div id="hw_owner_list" style="max-height:200px;overflow-y:auto">
            <button type="button" class="hw-owner-item py-1 px-2" data-id="" data-label="— No owner —">
                <em class="text-muted small">— No owner —</em>
            </button>
            <?php foreach ( $users as $user ) :
                $label = $user->user_login . ' (' . $user->user_email . ')';
            ?>
                <button type="button"
                        class="hw-owner-item py-1 px-2 small<?= $owner_id === (int) $user->ID ? ' active' : '' ?>"
                        data-id="<?= intval( $user->ID ) ?>"
                        data-label="<?= esc_attr( $label ) ?>">
                    <?= esc_html( $label ) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
( function() {
    const btn    = document.getElementById( 'hw_owner_btn' );
    const panel  = document.getElementById( 'hw_owner_panel' );
    const search = document.getElementById( 'hw_owner_search' );
    const list   = document.getElementById( 'hw_owner_list' );
    const hidden = document.getElementById( 'hw_owner_user_id_value' );
    const label  = document.getElementById( 'hw_owner_label' );

    btn.addEventListener( 'click', function() {
        const open = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : '';
        if ( ! open ) { search.focus(); }
    } );

    search.addEventListener( 'input', function() {
        const q = this.value.toLowerCase();
        list.querySelectorAll( '.hw-owner-item' ).forEach( function( item ) {
            item.style.display = ( ! q || item.dataset.label.toLowerCase().includes( q ) ) ? '' : 'none';
        } );
    } );

    list.addEventListener( 'click', function( e ) {
        const item = e.target.closest( '.hw-owner-item' );
        if ( ! item ) return;

        hidden.value      = item.dataset.id;
        label.textContent = item.dataset.label;

        list.querySelectorAll( '.hw-owner-item' ).forEach( i => i.classList.remove( 'active' ) );
        item.classList.add( 'active' );

        panel.style.display = 'none';
        search.value = '';
        list.querySelectorAll( '.hw-owner-item' ).forEach( i => i.style.display = '' );
    } );

    document.addEventListener( 'click', function( e ) {
        if ( ! document.getElementById( 'hw_owner_wrap' ).contains( e.target ) ) {
            panel.style.display = 'none';
        }
    } );
} )();
</script>
