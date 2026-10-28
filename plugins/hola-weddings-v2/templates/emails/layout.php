<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title><?= esc_html($subject ?? ''); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Basic, email-safe CSS -->
        <style>
            /* Some clients ignore <style>, but most modern ones don’t. Keep it minimal. */
            body { margin:0; padding:0; background:#fafafa; }
            .wrapper { width:100%; background:#fafafa; }
            .container { width:100%; max-width:600px; margin:0 auto; }
            .card {
            background:#ffffff;
            border:1px solid #e5e5e5;
            border-radius:6px;
            overflow:hidden;
            }
            .content { padding:24px 32px; font-family: Arial, sans-serif; color:#333333; line-height:1.5; font-size:15px; }
            .footer {
            font-family: Arial, sans-serif; color:#888888; font-size:12px; line-height:1.4;
            padding:24px 0; text-align:center;
            }
            img.logo { max-width:160px; height:auto; display:block; margin:32px auto 24px auto; }
            @media only screen and (max-width:620px){
            .content { padding:20px 18px !important; }
            }
        </style>
    </head>
    <body>
       <table role="presentation" class="wrapper" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">

                    <!-- Header -->
                    <table role="presentation" class="container" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <?php /* tenant-support */ $hs = function_exists('hw_tenant_support') ? hw_tenant_support() : array('logo_url' => '', 'name' => get_bloginfo('name')); if (!empty($hs['logo_url'])): ?><img src="<?= esc_url($hs['logo_url']) ?>" alt="<?= esc_attr($hs['name']) ?>" class="logo"><?php else: ?><strong style="font-size:20px;"><?= esc_html($hs['name']) ?></strong><?php endif; ?>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td>
                        <table role="presentation" class="card" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                            <td class="content">
                                {{content}}
                            </td>
                            </tr>
                        </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <?= function_exists('hw_tenant_email_footer') ? hw_tenant_email_footer() : esc_html(get_bloginfo('name')) . '<br><a href="mailto:' . esc_attr(get_option('admin_email')) . '">' . esc_html(get_option('admin_email')) . '</a>' ?>
                        </td>
                    </tr>
                    </table>

                </td>
            </tr>
        </table>
    </body>
</html>