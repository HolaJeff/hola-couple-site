<?php

namespace HW\CLI;

class Migrations {

    /**
     * Backfill hw_owner_user_id on all quotes that don't yet have it,
     * using the quote's post_author as the owner.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Preview how many quotes would be updated without making changes.
     *
     * ## EXAMPLES
     *
     *     wp hw migrate backfill-quote-owner
     *     wp hw migrate backfill-quote-owner --dry-run
     *
     * @subcommand backfill-quote-owner
     */
    public function backfill_quote_owner( array $args, array $assoc_args ): void {

        $dry_run = ! empty( $assoc_args['dry-run'] );

        if ( $dry_run ) {
            \WP_CLI::log( 'Dry-run mode — no changes will be saved.' );
        }

        $sites = is_multisite() ? get_sites( [ 'number' => 9999 ] ) : [ null ];

        foreach ( $sites as $site ) {

            if ( $site ) {
                switch_to_blog( $site->blog_id );
                \WP_CLI::log( sprintf( 'Processing site %s (blog_id %d)', $site->domain, $site->blog_id ) );
            }

            $query = new \WP_Query( [
                'post_type'      => 'quote',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => [ [
                    'key'     => 'hw_owner_user_id',
                    'compare' => 'NOT EXISTS',
                ] ],
            ] );

            $ids   = $query->posts;
            $total = count( $ids );

            \WP_CLI::log( sprintf( 'Found %d quote(s) without hw_owner_user_id.', $total ) );

            if ( $total === 0 ) {
                if ( $site ) restore_current_blog();
                continue;
            }

            $updated = 0;
            $skipped = 0;

            foreach ( $ids as $post_id ) {

                $author_id = (int) get_post_field( 'post_author', $post_id );

                if ( ! $author_id ) {
                    \WP_CLI::warning( sprintf( 'Quote #%d has no post_author — skipped.', $post_id ) );
                    $skipped++;
                    continue;
                }

                if ( ! $dry_run ) {
                    update_post_meta( $post_id, 'hw_owner_user_id', $author_id );
                }

                \WP_CLI::log( sprintf(
                    '%s Quote #%d → owner user #%d',
                    $dry_run ? '[dry-run]' : 'Updated',
                    $post_id,
                    $author_id
                ) );

                $updated++;
            }

            \WP_CLI::success( sprintf(
                '%s %d quote(s), skipped %d.',
                $dry_run ? 'Would update' : 'Updated',
                $updated,
                $skipped
            ) );

            if ( $site ) restore_current_blog();
        }
    }

    /**
     * Move each Customer post's billing into its linked WP user's meta, then trash
     * the Customer post. Run this while the `customers` CPT is still registered,
     * before removing the Customer entity from the codebase.
     *
     * Identity (email / first / last name) is NOT copied — the WP user already
     * owns it. Only phone + address fields move to `hw_billing_*` user meta.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Preview what would be migrated without making changes.
     *
     * ## EXAMPLES
     *
     *     wp hw migrate customers-to-user-meta
     *     wp hw migrate customers-to-user-meta --dry-run
     *
     * @subcommand customers-to-user-meta
     */
    public function customers_to_user_meta( array $args, array $assoc_args ): void {

        $dry_run = ! empty( $assoc_args['dry-run'] );

        if ( $dry_run ) {
            \WP_CLI::log( 'Dry-run mode — no changes will be saved.' );
        }

        $map = [
            'hw_phone'   => 'hw_billing_phone',
            'hw_address' => 'hw_billing_address',
            'hw_city'    => 'hw_billing_city',
            'hw_state'   => 'hw_billing_state',
            'hw_country' => 'hw_billing_country',
            'hw_zip'     => 'hw_billing_zip',
        ];

        $sites = is_multisite() ? get_sites( [ 'number' => 9999 ] ) : [ null ];

        foreach ( $sites as $site ) {

            if ( $site ) {
                switch_to_blog( $site->blog_id );
                \WP_CLI::log( sprintf( 'Processing site %s (blog_id %d)', $site->domain, $site->blog_id ) );
            }

            $query = new \WP_Query( [
                'post_type'      => 'customers',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ] );

            $ids   = $query->posts;
            $total = count( $ids );

            \WP_CLI::log( sprintf( 'Found %d customer(s).', $total ) );

            if ( $total === 0 ) {
                if ( $site ) restore_current_blog();
                continue;
            }

            $migrated = 0;
            $orphans  = 0;

            foreach ( $ids as $customer_id ) {

                $user_id = (int) get_post_meta( $customer_id, 'hw_wp_user_id', true );

                if ( ! $user_id || ! get_userdata( $user_id ) ) {
                    \WP_CLI::warning( sprintf(
                        'Customer #%d has no valid linked user (hw_wp_user_id=%d) — left in place for manual review.',
                        $customer_id,
                        $user_id
                    ) );
                    $orphans++;
                    continue;
                }

                foreach ( $map as $customer_key => $user_key ) {
                    $value = get_post_meta( $customer_id, $customer_key, true );
                    if ( '' === $value || null === $value ) {
                        continue;
                    }
                    if ( ! $dry_run ) {
                        update_user_meta( $user_id, $user_key, $value );
                    }
                }

                if ( ! $dry_run ) {
                    wp_trash_post( $customer_id );
                }

                \WP_CLI::log( sprintf(
                    '%s Customer #%d → user #%d billing meta',
                    $dry_run ? '[dry-run]' : 'Migrated',
                    $customer_id,
                    $user_id
                ) );

                $migrated++;
            }

            \WP_CLI::success( sprintf(
                '%s %d customer(s), %d orphan(s) skipped.',
                $dry_run ? 'Would migrate' : 'Migrated',
                $migrated,
                $orphans
            ) );

            if ( $site ) restore_current_blog();
        }
    }
}
