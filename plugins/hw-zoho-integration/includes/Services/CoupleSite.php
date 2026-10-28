<?php

namespace HWZoho\Services;

use HWZoho\API\CRM\Importers\RoomBlock as RoomBlockImporter;
use HWZoho\API\CRM\Modules\Potentials;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared couple-site creation mechanism.
 *
 * The proven "create subsite + seed options + import Room_Blocks" core, lifted
 * verbatim from ImportPotential::import_potential() so BOTH the wp-admin
 * "Import Wedding" AJAX handler and the Stage 2 REST endpoint call ONE
 * implementation (no rewrite, no behavior change for the admin flow).
 *
 * This class is the MECHANISM only — callers assemble the inputs:
 *   - the admin handler builds them from the review-form POST;
 *   - the REST endpoint builds them from the Zoho wedding (Step 2).
 *
 * Static + context-neutral on purpose: the REST request is not is_admin(), so
 * the admin Pages\ImportPotential instance is never booted during a REST call.
 * The autoloader still resolves this class in any context.
 */
class CoupleSite{

    /**
     * Create the couple subsite, seed its options, and import its Room_Blocks.
     *
     * Identical to the original handler's lines: wpmu_create_blog() ->
     * switch_to_blog() -> import each Room_Block -> restore_current_blog().
     *
     * @param string $domain          full subdomain host, e.g. "smith.hola-weddings.com"
     * @param string $wedding_name    new site title
     * @param array  $options         seeded site options (hw_* keys)
     * @param array  $room_block_ids  Zoho Room_Block record ids to import (may be empty)
     * @param int    $author_id       WP user id that will own the new blog
     * @return int|\WP_Error          new blog id on success, WP_Error on failure
     */
    public static function create($domain, $wedding_name, array $options, array $room_block_ids, $author_id, $style = 'classic'){

        $blog_id = wpmu_create_blog($domain, '', $wedding_name, $author_id, $options);

        if($blog_id instanceof \WP_Error){

            return $blog_id;
        }

        switch_to_blog($blog_id);

        if(!empty($room_block_ids)){

            $roomBlockImpoter = new RoomBlockImporter();

            foreach($room_block_ids as $room_block){
                $roomBlockImpoter->import($room_block);
            }
        }

        // Enrich the imported Room_Blocks so the booking form can render them:
        // match each to a resort-API room (hw_room_id) + seed per-night inventory.
        // The Zoho Room_Block carries rates but NOT the API-room link or inventory
        // (set by hand in the wp-admin flow), so we derive them here.
        self::enrich_room_blocks();

        restore_current_blog();

        // Stage 2 (v1 booking site): turn the bare new subsite into a working
        // booking site by applying the configured template (theme + pages + menu
        // + booking settings). No-op when no template is configured, so legacy
        // behaviour is preserved. A template failure must NOT fail creation — the
        // subsite + Room_Blocks already exist; surface it to the log instead.
        try {
            $template_result = SiteTemplate::apply($blog_id, $style);
            if(is_wp_error($template_result)){
                error_log('[hw-zoho] booking template skipped for blog ' . $blog_id . ': ' . $template_result->get_error_message());
            }
        } catch (\Throwable $e) {
            // A template exception must NOT abort creation (the subsite +
            // Room_Blocks already exist). Log it; the site can be re-templated
            // via resync. (Was uncaught -> half-templated sites on the default theme.)
            error_log('[hw-zoho] booking template threw for blog ' . $blog_id . ': ' . $e->getMessage());
        }

        return $blog_id;
    }

    /**
     * Re-sync an EXISTING couple site from Zoho: re-seed the wedding settings +
     * policies, and REPLACE the room blocks (clear the old posts, re-import the
     * current Zoho set) so a corrected contract flows to a live site WITHOUT
     * recreating it. Intended for pre-booking corrections — it deletes and
     * re-imports room block posts, so run it before guests book against them.
     *
     * @param string $domain          the site's full host (locates the blog)
     * @param array  $options         wedding settings + policies to re-seed
     * @param array  $room_block_ids  current Zoho Room_Block ids to import
     * @return int|\WP_Error          blog id on success
     */
    public static function resync($domain, array $options, array $room_block_ids, $style = 'classic'){

        $blog_id = domain_exists($domain, '/');
        if(!$blog_id){
            return new \WP_Error('SITE_NOT_FOUND',
                'No couple site exists for this wedding yet — create it first.', ['code' => 404]);
        }
        $blog_id = (int) $blog_id;

        switch_to_blog($blog_id);

        // 1. re-seed wedding settings + policies (Zoho is the source of truth)
        foreach($options as $key => $value){
            update_option($key, $value);
        }

        // 2. clear existing room block posts (avoids the duplicate sets a re-push
        // would otherwise accumulate) + their inventory table rows
        $existing = get_posts([
            'post_type'   => 'room_blocks',
            'numberposts' => -1,
            'post_status' => 'any',
            'fields'      => 'ids',
        ]);
        foreach($existing as $pid){
            wp_delete_post($pid, true);
        }

        // 3. re-import the current Zoho room blocks + re-derive inventory/room match
        if(!empty($room_block_ids)){
            $importer = new RoomBlockImporter();
            foreach($room_block_ids as $room_block){
                $importer->import($room_block);
            }
        }
        self::enrich_room_blocks();

        // 4. is this site still on its (style-specific) template theme? If a
        // create-time template failure left it half-built (default theme),
        // re-apply. Skip when already templated so resync never duplicates pages.
        $opt_key = SiteTemplate::TEMPLATE_OPTION[ $style ] ?? SiteTemplate::TEMPLATE_OPTION['classic'];
        $template_blog = (int) get_network_option( null, $opt_key, 0 );
        if ( ! $template_blog && $style !== 'classic' ) {
            $template_blog = (int) get_network_option( null, SiteTemplate::TEMPLATE_OPTION['classic'], 0 );
        }
        $current_theme = (string) get_option( 'stylesheet' );
        restore_current_blog();

        $want_theme = '';
        if ( $template_blog ) {
            switch_to_blog( $template_blog );
            $want_theme = (string) get_option( 'stylesheet' );
            restore_current_blog();
        }
        if ( $want_theme && $current_theme !== $want_theme ) {
            try {
                SiteTemplate::apply( $blog_id, $style );
            } catch ( \Throwable $e ) {
                error_log( '[hw-zoho] resync re-template failed for blog ' . $blog_id . ': ' . $e->getMessage() );
            }
        }

        return $blog_id;
    }

    /**
     * Step 4: write the created subsite URL back to the Zoho wedding's
     * Wedding_Page_URL field (closes the manual-paste gap from the discovery).
     *
     * @param string $wedding_id  Zoho Deal id
     * @param string $url         the new subsite URL
     * @return true|\WP_Error
     */
    public static function write_back_page_url($wedding_id, $url){

        return Potentials::updateRecord((string) $wedding_id, ['Wedding_Page_URL' => $url]);
    }

    /**
     * Match a CRM resort name to a hotel id from the resort-API hotel list.
     *
     * Single source of truth for the resort -> hotel mapping, shared by the
     * wp-admin Import Wedding form and the REST endpoint. Conservative: exact
     * (normalized) match first, then strong token overlap; returns '' when no
     * confident match (caller decides: manual pick, or require an explicit id).
     *
     * @param string $resort_name  the CRM "Resort" value
     * @param array  $hotel_list   [ hotel_id => hotel_name ]
     * @return string              matched hotel id, or '' if none confident
     */
    public static function resolve_hotel_id($resort_name, array $hotel_list){

        $resort_name = (string) $resort_name;
        if ($resort_name === '' || empty($hotel_list)) {
            return '';
        }

        $normalize = function ($s) {
            $s = strtolower(trim((string) $s));
            $translit = iconv('UTF-8', 'ASCII//TRANSLIT', $s); // strip accents (Barceló -> Barcelo)
            $s = $translit !== false ? $translit : $s;
            $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);        // drop punctuation
            $s = preg_replace('/\b(resort|spa|hotel|the|and|all|inclusive|by|a|an)\b/', ' ', $s); // filler
            return trim(preg_replace('/\s+/', ' ', $s));
        };

        $target        = $normalize($resort_name);
        $target_tokens = array_filter(explode(' ', $target));

        $best_id    = '';
        $best_score = 0.0;
        foreach ($hotel_list as $hid => $hname) {
            $cand = $normalize($hname);
            if ($cand === '' || $target === '') {
                continue;
            }
            if ($cand === $target) {
                return (string) $hid; // exact normalized match wins outright
            }
            $cand_tokens = array_filter(explode(' ', $cand));
            $overlap     = count(array_intersect($target_tokens, $cand_tokens));
            $score       = $overlap / max(count($target_tokens), 1);
            if ($overlap >= 2 && $score > $best_score) {
                $best_score = $score;
                $best_id    = $hid;
            }
        }

        return $best_score >= 0.6 ? (string) $best_id : '';
    }

    /** Default per-night inventory seeded for an auto-created room block (team reviews/adjusts). */
    const DEFAULT_ROOM_INVENTORY = 10;

    /**
     * Enrich the just-imported Room_Blocks so the booking form can render them:
     * match each to a resort-API room (hw_room_id) and seed per-night inventory
     * for the wedding's booking window. Runs in the NEW blog's context (called by
     * create() between switch_to_blog() and restore_current_blog()).
     */
    private static function enrich_room_blocks(){

        $hotel_id = get_option('hw_hotel_id');
        if(!$hotel_id || !class_exists('\HW\API\Operations\HotelRooms')){
            return;
        }

        $api_rooms = \HW\API\Operations\HotelRooms::get($hotel_id);
        if(!is_array($api_rooms)){
            return;
        }

        $room_list = [];
        foreach($api_rooms as $r){
            $id   = is_array($r) ? ($r['id'] ?? null)   : ($r->id ?? null);
            $name = is_array($r) ? ($r['name'] ?? '')   : ($r->name ?? '');
            if($id){
                $room_list[$id] = $name;
            }
        }

        $inventory = self::build_inventory(get_option('hw_start_date'), get_option('hw_end_date'), self::DEFAULT_ROOM_INVENTORY);

        foreach(get_posts(['post_type' => 'room_blocks', 'numberposts' => -1, 'post_status' => 'any']) as $rb){
            if(!get_post_meta($rb->ID, 'hw_room_id', true)){
                $match = self::match_room($rb->post_title, $room_list);
                if($match !== null){
                    update_post_meta($rb->ID, 'hw_room_id', $match);
                }
            }
            if($inventory && !get_post_meta($rb->ID, 'hw_inventory', true)){
                update_post_meta($rb->ID, 'hw_inventory', $inventory);
            }
        }
    }

    /** Best resort-API room id for a room-block title by token overlap (>=0.5), or null. */
    private static function match_room($title, array $room_list){

        $normalize = function($s){
            $s = strtolower((string) $s);
            $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
            $s = preg_replace('/\s+/', ' ', trim($s));
            return array_values(array_filter(explode(' ', $s)));
        };

        $block = $normalize($title);
        if(!$block){
            return null;
        }

        $best = null;
        $best_score = 0.0;
        foreach($room_list as $id => $name){
            $room = $normalize($name);
            if(!$room){
                continue;
            }
            $overlap = count(array_intersect($block, $room)) / max(count($block), count($room));
            if($overlap > $best_score){
                $best_score = $overlap;
                $best = $id;
            }
        }

        return $best_score >= 0.5 ? $best : null;
    }

    /** Per-night inventory map [ Y-m-d => count ] for a date range (end-exclusive). */
    private static function build_inventory($start, $end, $count){

        if(!$start || !$end){
            return [];
        }
        try {
            $d    = new \DateTime($start);
            $stop = new \DateTime($end);
        } catch(\Exception $e){
            return [];
        }

        $inventory = [];
        while($d < $stop){
            $inventory[$d->format('Y-m-d')] = (string) $count;
            $d->modify('+1 day');
        }
        return $inventory;
    }

    /**
     * Build the create()-ready inputs for a Zoho wedding (Deal) id.
     *
     * Reproduces the wp-admin Import Wedding form's mapping SERVER-SIDE so a
     * headless REST call can create the same couple site from just a wedding id.
     * Mirrors ImportPotential::import_potential()'s $options exactly.
     *
     * @param string $wedding_id  Zoho Deal/Potential id
     * @param array  $overrides   optional: subdomain, hotel_id, transfer_multiplier
     * @return array|\WP_Error    [ domain, wedding_name, options, room_block_ids ] or WP_Error
     */
    public static function build_inputs_from_wedding($wedding_id, array $overrides = []){

        $potential = Potentials::getRecord($wedding_id);

        // getRecord returns a Record on success, false on no-content, or an
        // APIException object on an API error (e.g. an unknown/invalid id).
        // Only a Record has getKeyValue() — anything else is a clean 404.
        if (!is_object($potential) || !method_exists($potential, 'getKeyValue')) {
            $detail = '';
            if (is_object($potential) && method_exists($potential, 'getMessage')) {
                $m = $potential->getMessage();
                $detail = (is_object($m) && method_exists($m, 'getValue')) ? $m->getValue() : (string) $m;
            }
            return new \WP_Error(
                'WEDDING_NOT_FOUND',
                sprintf('No Zoho wedding record for id %s.%s', $wedding_id, $detail ? ' Zoho: ' . $detail : ''),
                ['code' => 404]
            );
        }

        // --- names (lookups -> .name), PHP 7.4: no ?-> operator on the server ---
        $contact_name = '';
        if ($c = $potential->getKeyValue('Contact_Name')) {
            $contact_name = $c->getKeyValue('name');
        }
        $spouse_name = '';
        if ($s = $potential->getKeyValue('Spouse_Name')) {
            $spouse_name = $s->getKeyValue('name');
        }

        $wedding_name = (string) $potential->getKeyValue('Deal_Name');

        // Fall back to splitting the Deal name ("X and Y") into the couple's names
        // when the Contact/Spouse lookups are empty, so the site doesn't show a
        // blank "& <date>".
        if ($contact_name === '' && $spouse_name === '' && $wedding_name !== '') {
            $name_parts   = preg_split('/\s+and\s+/i', $wedding_name, 2);
            $contact_name = trim($name_parts[0]);
            $spouse_name  = isset($name_parts[1]) ? trim($name_parts[1]) : '';
        }

        // --- dates: start/end default to wedding date -/+ 5 days (as the form does) ---
        $wedding_date = $potential->getKeyValue('Wedding_Date');
        $cut_off_date = $potential->getKeyValue('Cut_Off_Date');
        if (empty($wedding_date)) {
            return new \WP_Error('WEDDING_DATE_MISSING', 'The Zoho wedding has no Wedding_Date.', ['code' => 422]);
        }
        $wd         = new \DateTime($wedding_date);
        $start_date = (clone $wd)->modify('-5 days')->format('Y-m-d');
        $end_date   = (clone $wd)->modify('+5 days')->format('Y-m-d');
        $wedding_date = $wd->format('Y-m-d');
        $cut_off_date = $cut_off_date ? (new \DateTime($cut_off_date))->format('Y-m-d') : '';

        // --- hotel: from CRM "Resort" (override wins), matched to the resort-API list ---
        $hotel_id = isset($overrides['hotel_id']) ? (string) $overrides['hotel_id'] : '';
        $crm_resort = (string) $potential->getKeyValue('Resort');
        if ($crm_resort === '' && ($acct = $potential->getKeyValue('Account_Name'))) {
            $crm_resort = (string) $acct->getKeyValue('name');
        }
        if ($hotel_id === '') {
            $hotel_list = hw_api_get_hotel_list();
            if (is_wp_error($hotel_list)) {
                return new \WP_Error('HOTEL_API_ERROR', 'Resort hotel API error: ' . $hotel_list->get_error_message(), ['code' => 502]);
            }
            $hotel_id = self::resolve_hotel_id($crm_resort, $hotel_list);
        }
        if ($hotel_id === '') {
            return new \WP_Error(
                'HOTEL_UNRESOLVED',
                sprintf('Could not match CRM resort "%s" to a hotel. Pass an explicit hotel_id.', $crm_resort),
                ['code' => 422]
            );
        }

        // --- transfer ---
        $transfer_cost       = $potential->getKeyValue('Transfer_Cost');
        $has_transfer        = intval($transfer_cost) > 0 ? 1 : 0;
        $transfer_multiplier = isset($overrides['transfer_multiplier']) ? $overrides['transfer_multiplier'] : 'room';

        // --- deposit / payment plan (Wedding Settings > Payment): the 1st deposit
        // is the minimum to hold a room; the 2nd deposit is the "increase"
        // (date + amount). Mapped from the same Zoho Deal fields the manual
        // Import Wedding form uses. ---
        $first_deposit_amount   = $potential->getKeyValue('st_Deposit_Amount');
        $second_deposit_type    = $potential->getKeyValue('nd_Deposit_Type');
        $second_deposit_amount  = $potential->getKeyValue('nd_Deposit_Amount');
        $second_deposit_date    = $potential->getKeyValue('nd_Deposit_Due_Date');
        $has_increase = (
            is_object($second_deposit_type) && method_exists($second_deposit_type, 'getValue')
            && $second_deposit_type->getValue() !== 'No Deposit'
            && $second_deposit_amount && $second_deposit_date
        );
        $min_deposit    = $first_deposit_amount ? (string) $first_deposit_amount : '';
        $increase_amount = $has_increase ? (string) $second_deposit_amount : '';
        $increase_date   = $has_increase ? (new \DateTime($second_deposit_date))->format('Y-m-d') : '';

        // --- policies: the platform renders each agency's own T&C + cancellation
        // template (per-agency wording) and passes the finished HTML as overrides;
        // use it when present. Fall back to the built-in templates for the legacy
        // wp-admin flow / agencies that haven't authored policies yet. ---
        if ( isset( $overrides['terms'] ) && $overrides['terms'] !== '' ) {
            $terms_conditions = (string) $overrides['terms'];
        } else {
            ob_start();
            hwzi_get_template('admin/pages/import-potential/import/deposit-payment', ['potential' => $potential]);
            $terms_conditions = ob_get_clean();
        }

        if ( isset( $overrides['cancellation_policy'] ) && $overrides['cancellation_policy'] !== '' ) {
            $cancellation_policy = (string) $overrides['cancellation_policy'];
        } else {
            ob_start();
            hwzi_get_template('admin/pages/import-potential/import/cancellation-policy', ['potential' => $potential]);
            $cancellation_policy = ob_get_clean();
        }

        // --- subdomain: override > Wedding_Page_URL prefix > slug of the couple names ---
        $subdomain = isset($overrides['subdomain']) ? (string) $overrides['subdomain'] : '';
        if ($subdomain === '') {
            // Wedding_Page_URL may be stored with or without a scheme
            // ("https://emma.hola-weddings.com" or "emma.hola-weddings.com").
            // Take the HOST's first label, not explode('.')[0] which would keep
            // the scheme ("https://emma") and break site lookup on resync.
            $page_url = (string) $potential->getKeyValue('Wedding_Page_URL');
            if ($page_url !== '') {
                $host = parse_url(strpos($page_url, '//') === false ? '//' . $page_url : $page_url, PHP_URL_HOST);
                $subdomain = $host ? explode('.', $host)[0] : '';
            }
        }
        if ($subdomain === '') {
            $subdomain = sanitize_title(trim($contact_name . ' ' . $spouse_name));
        }
        // DNS is case-insensitive but WordPress host matching is NOT — normalize
        // whichever source won (override / page-url prefix / couple names) to a
        // clean lowercase slug, or mixed-case requests 404 on their own subsite.
        $subdomain = sanitize_title($subdomain);
        if ($subdomain === '') {
            return new \WP_Error('SUBDOMAIN_UNRESOLVED', 'Could not derive a subdomain; pass an explicit subdomain.', ['code' => 422]);
        }

        // Root domain for new couple sites. The REST endpoint may execute in a
        // MAPPED subsite's context (e.g. the clone's coupletest.hola-weddings.com),
        // which would nest new sites under that host (sub.coupletest.…). The
        // network option pins the real root; absent -> current host (prod behaviour).
        $root_domain = (string) get_network_option( null, 'hw_couple_site_root_domain', '' );
        if ( '' === $root_domain ) {
            $root_domain = parse_url(get_site_url(), PHP_URL_HOST);
        }
        $domain      = $subdomain . '.' . $root_domain;

        // --- site style from the Deal's Booking_Model (Classic booking site vs
        // Finest/Excellence promocode site vs full marketing site). The caller
        // (platform) may pass booking_model + promocode as overrides — it knows
        // them from the just-extracted contract, and the Zoho PHP SDK silently
        // drops recently-created custom fields from getRecord, so the override is
        // the reliable source. Fall back to the Deal fields for the wp-admin flow. ---
        $booking_model = isset($overrides['booking_model']) && $overrides['booking_model'] !== ''
            ? (string) $overrides['booking_model']
            : (function($v){ return (is_object($v) && method_exists($v,'getValue')) ? $v->getValue() : (string) $v; })($potential->getKeyValue('Booking_Model'));
        $style = SiteTemplate::style_for_booking_model($booking_model);
        $is_promocode = ($style === 'promocode');
        $promocode = isset($overrides['promocode']) && $overrides['promocode'] !== ''
            ? (string) $overrides['promocode']
            : (string) $potential->getKeyValue('Booking_Number');

        // --- $options: identical shape to ImportPotential::import_potential() ---
        $options = [
            'hw_her_name'            => $spouse_name,
            'hw_his_name'            => $contact_name,
            'hw_date'                => $wedding_date,
            'hw_start_date'          => $start_date,
            'hw_end_date'            => $end_date,
            'hw_date_unavailable'    => $cut_off_date,
            'hw_currency'            => 'USD',
            'hw_currency_symbol'     => '$',
            'hw_hotel_id'            => $hotel_id,
            'hw_has_transfer'        => $has_transfer,
            'hw_service_fee'         => $transfer_cost,
            // payment plan (mapped from Zoho; the template seeds enable/type too)
            'hw_enable_payment'          => 1,
            'hw_deposit_type'            => 'fixed',
            'hw_min_deposit'             => $min_deposit,
            'hw_deposit_increase_amount' => $increase_amount,
            'hw_deposit_increase_date'   => $increase_date,
            // BOTH keys: the booking push reads hwz_wedding_id (Frontend.php)
            // while admin tools read hwzi_wedding_id — the ARCHITECTURE.md §3
            // discrepancy. Seeding only hwzi_ left new sites unable to push
            // bookings to Zoho (bit on the first production site).
            'hwzi_wedding_id'        => (string) $wedding_id,
            'hwz_wedding_id'         => (string) $wedding_id,
            'hw_cart_mode'           => 'room',
            'hw_ages'                => [
                'infant_max'   => 1,
                'children_min' => 2,
                'children_max' => 12,
                'teen_min'     => 13,
                'teen_max'     => 17,
            ],
            'hw_transfer_multiplier' => $transfer_multiplier,
            // final cart caps (the form sets these last, overriding earlier values)
            'hw_cart_max_adults'     => 3,
            'hw_cart_max_teen'       => 2,
            'hw_cart_max_children'   => 2,
            'hw_cancellation_policy' => stripslashes($cancellation_policy),
            'hw_terms'               => stripslashes($terms_conditions),
        ];

        // --- Classic auto-charge provisioning. The First-installment auto-charge
        // (hw-cv-autocharge mu-plugin) is per-site opt-in and needs the CV group
        // id; both were hand-set on the pilot site, so every NEW couple site was
        // born opted out and bookings collected a card but never charged (Norm
        // McDonald, 2026-09-01). The Deal's Booking_Number carries the group
        // ("69006" or "69006-123"): seed both options at creation. ---
        $booking_no = (string) $potential->getKeyValue('Booking_Number');
        $cv_group = trim(explode('-', $booking_no)[0]);
        if ($cv_group !== '' && ctype_digit($cv_group)) {
            $options['hw_cv_group_id'] = $cv_group;
            $options['hw_cv_autocharge'] = 1;
        }

        // Promocode sites: guests book on the RESORT's site with the code, so the
        // internal booking engine (cart, inventory, payment) is off and the promo
        // code is displayed instead.
        if ($is_promocode) {
            $options['hw_enable_payment']   = 0;
            $options['hw_enable_inventory']  = 0;
            $options['hw_promocode']         = $promocode;
            $options['hw_booking_model']     = $booking_model;
        }

        // --- room blocks: related Room_Blocks ids (the importer fetches each from Zoho) ---
        // For promocode sites also derive the group's TRAVEL PERIOD from the blocks:
        // the pusher writes each block's First_Night/Last_Night = the travel dates
        // (the Deal itself has no travel-period field), so the earliest First_Night
        // and latest Last_Night across the blocks is the period shown on the site.
        $room_block_ids = [];
        $travel_start = ''; $travel_end = '';
        $related = Potentials::getRelatedRecords($wedding_id, 'Room_Blocks');
        if (!empty($related) && !is_wp_error($related)) {
            foreach ($related as $record) {
                $room_block_ids[] = $record->getId();
                $fn = (string) $record->getKeyValue('First_Night');
                $ln = (string) $record->getKeyValue('Last_Night');
                if ($fn !== '' && ($travel_start === '' || $fn < $travel_start)) { $travel_start = $fn; }
                if ($ln !== '' && ($travel_end === ''   || $ln > $travel_end))   { $travel_end   = $ln; }
            }
        }
        if ($is_promocode && $travel_start !== '' && $travel_end !== '') {
            $options['hw_start_date'] = (new \DateTime($travel_start))->format('Y-m-d');
            $options['hw_end_date']   = (new \DateTime($travel_end))->format('Y-m-d');
        }

        return [
            'domain'         => $domain,
            'wedding_name'   => $wedding_name,
            'options'        => $options,
            'room_block_ids' => $room_block_ids,
            'style'          => $style,
        ];
    }
}
